<?php

class MailMessage extends CComponent 
{
    
    public $id;
    public $headers = array();
    public $subject;
    public $to;    
    public $from;    
    public $content;
    public $files = array();
    
    /**
     * main boundary 
     * @var string
     */    
    public $boundary;
    
    /**     
     * @var MailMessage
     */
    public $message;

    /**
     * @var MEticketEmail
     */ 
    public $email;
    
    public function __construct($email)
    {

        if ($email instanceof MEticketEmail){
            $this->email = $email;

            $datas = new Zend\Mail\Storage\Message(array(
                        'content' => $this->email->body,
                        'headers'  => $this->email->header
            ));
        } else {
            $datas = $email;
        }
        
        if($datas instanceof Zend\Mail\Storage\Message){            
            $this->message  = $datas;
            foreach($datas->getHeaders() as $header){                
                $this->headers[$header->getFieldName()] = $header->getFieldValue();
                $attribute = strtolower($header->getFieldName());                                
                if(property_exists($this, $attribute)){
                    if(in_array($attribute,array('to','from'))){
                        $this->$attribute = $this->handleEmailData($header);                        
                    } else {                    
                        $this->$attribute = $header->getFieldValue();
                    }
                }
            }            
            $this->content  = $this->decodeContent($this->message);
            $this->id       = $this->message->getMessageNum();     
            try {          
                $this->boundary = $this->message->getHeaderField('content-type','boundary');            
            } catch (Exception $e) {
                $this->boundary = null;
            }
        } elseif(is_array($datas)){
            foreach($datas as $attribute => $value){
                if(property_exists($this, $attribute)){
                    $this->$attribute = $value;
                }
            }
        }
    }
    
    /**
     * handle email data
     * 
     * @param mixed $input
     * @return string|array
     */
    public function handleEmailData($input){
        if($input instanceof Zend\Mail\Header\AbstractAddressList){
            $data = array();
            foreach($input->getAddressList() as $list){
                $data[$list->getEmail()] = $list->getName();                
            }
            return $data;
        } else {
            return $input;
        }
    }
    
    /**
     * add header
     * 
     * @param string $name
     * @param string $value
     * @return null
     */
    public function addHeader($name,$value){
        $this->headers[$name] = $value;
    }
    
    /**     
     * get content type encoder of message
     *
     * @return string
     */
    public function getContentEncoder($Message){
        $headers = $Message->getHeaders()->toArray();
        if(!isset($headers['Content-Transfer-Encoding'])){
            return 'quoted-printable';
        } else {
            return $headers['Content-Transfer-Encoding'];
        }
    }
    
    /**  
     * decode content message
     * 
     * @param Zend\Mail\Storage\Message $data
     * @return string decoded message body 
     */
    public function decodeContent(Zend\Mail\Storage\Message $data){
        $encode  = $this->getContentEncoder($data);
        $content = $data->getContent();     
        switch($encode){            
            case 'base64':
                $content = base64_decode($content);
                break;
            default:
            case 'quoted-printable':
                $content = quoted_printable_decode($content);               
                break;
        }
        return $content;
    }
    
    /**
     * get decoded content
     * 
     * @return string
     */
    public function getDecodedContent(){
        return $this->decodeContent($this->message);
    }

    /**
     * delete current email
     * 
     * @return null
     */
    public function delete(){
        Yii::app()->getModule('mail')->mailClient->deleteEmail($this->id);
    }

}