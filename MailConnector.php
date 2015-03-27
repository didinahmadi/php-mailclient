<?php


Yii::import('Zend.Loader.StandardAutoloader');

class MailConnector extends CComponent {
    
    public $host;
    public $port;
    
    public $user;
    public $password;
    
    
    /**
     * 
     * @var string|boolean 
     * can be false|ssl|tls
     */
    public $encrypt = false; 
    
    public $_con;
    
	/**     
     * @see CComponent::__call()
     */
    public function __call($name, $parameters)
    {        
        if(!method_exists($this, $name)){
            return call_user_func_array(array($this->_con,$name), $parameters);
        } else {
            return parent::__call($name, $parameters);
        }
    }
    
    public function __construct($options = array()){
        foreach($options as $optionName => $optionValue){
            if(property_exists($this, $optionName)) {
                $this->{$optionName} = $optionValue;
            }
        }            
    }
    
    public function init(){        
        $this->registerZendComponent();   
    }
    
    /**
     * register Zend autoload component
     */
    public function registerZendComponent(){
        $ZendAutoLoader = new Zend\Loader\StandardAutoloader();
        $ZendAutoLoader->registerNamespace('Zend', Yii::getPathOfAlias('Zend'));
        $ZendAutoLoader->registerPrefix('Zend', Yii::getPathOfAlias('Zend'));
        $ZendAutoLoader->register();
    }
    
    public function getSocket(){
        return $this->_con->protocol;
    }
    
}