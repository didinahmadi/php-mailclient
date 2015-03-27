<?php

class MailClient extends CComponent {

    /**
     * @var MailModule
     */
    public $mailModule;

    /**
     * @var IMailConnector
     */
    protected $mailServer;

    public $host;
    public $port;
    public $user;
    public $password;

    const IMAP = 'imap';
    const POP3 = 'pop3';

    public function init(){
       if(null==$this->mailModule){
           $this->mailModule = Yii::app()->getModule('mail');
       }
    }

    /**
     * open connection
     *
     * @param string $dsn data source name information to connect to pop3 or imap server
     * @return MailClient
     * @throws CException
     */
    public function open($dsn){
        $dsn = $this->mailModule->parseDsn($dsn);
        $protocol = isset($dsn['protocol']) ? $dsn['protocol'] : null;
        $options     = array(
            'host'    => $dsn['host'],
            'port'    => $dsn['port'],
            'user'    => $dsn['user'],
            'password'=> $dsn['pass'],
            'encrypt' => $dsn['scheme']
        );
        switch($protocol){
            case self::POP3:
                $this->mailServer = new MailPop3Connector($options);
                break;
            case self::IMAP;
            default:
                $this->mailServer = new MailImapConnector($options);
                break;
        }

        $this->mailServer->init();
        if(!$this->mailServer->isAuthenticated()){
            throw new CException('Authentication failed. please make sure username and password are correct!');
        }

        return $this;
    }

    /**
     * read inbox
     *
     * @param array $params only for IMAP Protocol
     * supported params key:
     * <ul>
     * <li>mailbox : name of folder/ mailbox:</li>
     * <li>searchParams : @see http://tools.ietf.org/html/rfc3501#page-49</li>
     * </ul>
     * example of $params can be:
     * <pre>
     * array(
     * 	'mailbox'      => 'mailbox name'
     * 	'searchParams' => array('search condition here')
     * )
     * </pre>
     *
     * @return MailMessage[]
     */
    public function readInbox($params = array()){
        return $this->mailServer->getEmails($params);
    }

    /**
     * filter message
     *
     * @param MailMessage[] $messages
     * @param array $options search criteria
     * criteria using regular expression without delimiter (/)
     * example of options:
     * array(
     * 	'content' => 'criteria',
     *  'subject' => ...,
     *  'from'    => ...,
     *  'to'      => ...
     * )
     *
     * @return MailMessage[]
     */
    public function filterMessage(array $messages, array $options){
        $filteredResults = array();
        foreach($messages as $message){
            if(!$message instanceof MailMessage) throw new CException("Expected MailMessage instance!");
            if(sizeof($options)>0){
                foreach($options as $field => $pattern){
                    $searchArea = $message->$field;
                    if(is_array($searchArea)){
                        $joinedSearchArea = array();
                        foreach($searchArea as $k => $v){
                            $joinedSearchArea[] = "$k <$v>";
                        }
                        $searchArea = join(", ", $joinedSearchArea);
                    }
                    if(preg_match('/'. $pattern .'/', $searchArea)){
                        $filteredResults[] = $message;
                    }
                }
            } else {
                $filteredResults[] = $message;
            }
        }
        return $filteredResults;
    }



    /**
     * send message
     *
     * @param MailMessage $message
     * @param string $dsn data source name
     * @return boolean
     */
    public function sendMessage(MailMessage $message, $dsn = null)
    {
        $text_content = '';
        $html_content = '';
        $from         = '';

        if(isset($message->headers['Content-Type']) && $message->headers['Content-Type']=='text/plain') {
            $text_content = $message->content;
        } else {
            $html_content = $message->content;
        }

        $dsn = $this->mailModule->parseDsn($dsn);

        if($message->from) {
            $from = $message->from;
        } elseif (isset($dsn['from'])) {
            $from = $dsn['from'];
        }

        if(isset($dsn['security'])){
            if(!in_array(strtolower($dsn['security']), array('tls','ssl'))){
                $dsn['security'] = null;
            }
        }
        $dsn = $this->mailModule->buildDsn($dsn);

        return $this->mailModule->Send($message->to, $message->subject, $html_content, $text_content, $from, $message->files, $message->headers, $dsn);
    }

    /**
     * delete email on server
     *
     * @param int $id
     */
    public function deleteEmail($id){
        $this->mailServer->deleteEmail($id);
    }

    /**
     * checking mail server, if it working fine or not
     *
     * @return boolean
     */
    public function check(){
        try {
            $this->mailServer->getSampleEmail();
            return true;
        } catch(Exception $e){
            return false;
        }
    }

    /**
     * logout and close connection
     */
    public function close(){
        $this->mailServer->terminate();
    }

}