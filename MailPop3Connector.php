<?php

use Zend\Mail\Storage\Pop3;

class MailPop3Connector extends MailConnector implements IMailConnector{
    
    public function __construct($options){
        parent::__construct($options);
    }
    
    public function terminate(){
        $this->_con->close();
        unset($this->_con);
        return true;
    }
    
    /**
     * read all email
     * pop3 doesn't support remote search
     * @see IMailConnector::getEmails()
     */
    public function getEmails(){        
        $results = array();        
        $totalMessage = $this->_con->countMessages();        
        for($i=$totalMessage;$i>=1;$i--){
            try {
                $results[$i] = new MailMessage($this->_con->getMessage($i));
            } catch(Exception $e){
                throw new CException($e->getMessage());
            }                                    
        }
        return $results;
    }
    
    public function getSampleEmail(){
        return $this->_con->countMessages();
    }
    
    
    public function isAuthenticated(){
        $status = null;
        try{
            $total = $this->_con->countMessages();            
            return isset($total) ? true : false;
        } catch(Exception $e){
            return false;
        }
    }
    
    /**
     * 
     * delete email
     * @param int|null $id message number     
     * @return boolean
     */
    public function deleteEmail($id){
        if (!$this->isAuthenticated()) {
            $this->_con->logout();
            $this->init();
        }
        return $this->_con->removeMessage($id);
    }
        
    public function init(){
        $this->_con = new Zend\Mail\Storage\Pop3(array(
        	'host' => $this->host,
            'port' => $this->port,
            'ssl'  => $this->encrypt,
            'user' => $this->user,
            'password' => $this->password            
        ));
    }
    
}