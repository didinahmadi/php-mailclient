<?php

use Zend\Mail\Storage\Imap;

class MailImapConnector extends MailConnector implements IMailConnector {
    
    public function __construct($options) {
        parent::__construct($options);                
    }
    
    public function init(){
        $this->_con = new Zend\Mail\Storage\Imap(array(
        	'host' => $this->host,
            'port' => $this->port,
            'ssl'  => $this->encrypt,
            'user' => $this->user,
            'password' => $this->password            
        ));
        parent::init();
    }
    
    public function getSampleEmail(){
        return $this->_con->protocol->search(array('SEEN'));
    }
    
    /**
     * 
     * get email from mailbox     
     * @param array $params
     * @return Zend\Mail\Storage\Message[]
     */
    public function getEmails(array $params = array()){        
        // set default mailbox
        $mailbox       = isset($params['select']) ? $params['select'] : 'INBOX';
        $searchParams  = isset($params['search']) ? $params['search'] : array('ALL UNSEEN');
        $this->_con->selectFolder($mailbox);
        $searchResults = $this->_con->protocol->search($searchParams);
        $results = array();        
        $datas = $this->_con->protocol->fetch(array('FLAGS', 'RFC822.HEADER'), $searchResults);        
        foreach($datas as $id => $data){
            $header = $data['RFC822.HEADER'];    
            $flags = array();
            foreach ($data['FLAGS'] as $flag) {
                $flags[] = $this->_con->knownFlags($flag);
            }    
            $messageClass = $this->_con->getMessageClass();   
            $message      = new $messageClass(array('handler' => $this->_con, 'id' => $id, 'headers' => $header, 'flags' => $flags));                                      
            $results[$id] = new MailMessage( $message );
        }
        return $results;
    }
    
    
    /**     
     * delete email
     * 
     * @param int|null $id message number
     */
    public function deleteEmail($id){
        if (!$this->isAuthenticated()) {
            $this->init();
        }
        $this->_con->removeMessage($id);
    }
    
    /**
     * 
     * get mailbox's status
     * @param string $mailbox
     * @return array
     */
    public function status($mailbox='INBOX'){
        return $this->_con->protocol->requestAndResponse('STATUS '. $mailbox, array('(UIDNEXT MESSAGES)'));
    }
    
    
    /**
     * logout and close imap connection
     * @see IMailConnector::terminate()
     */
    public function terminate(){
        $result = $this->_con->close();
        unset($this->_con);
        return $result;
    }
    
    /**
     * 
     * check whether state were authenticated or not
     * @return boolean
     */
    public function isAuthenticated(){
        $capabilities = $this->_con->countMessages();
        if (!$capabilities) {
            return false;
        }
        return true;
    }
}