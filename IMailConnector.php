<?php

interface IMailConnector {
    
    public function init();
    
    public function getEmails();
    
    public function terminate();
    
    public function isAuthenticated();
    
}