<?php
abstract class COAuth2Connect extends COAuth2{
	protected $me=null;

	public function __construct($tokens,$retrieveMe=false){
		parent::__construct($tokens);
		if($retrieveMe===true) $this->retrieveMe();
	}
	
	public function me($name, $default = null){
		return isset($this->me[$name])?$this->me[$name]:$default;
	}
	
	public function retrieveMe(){
		return $this->me=CSimpleHttpClient::getJson($this->getApiUrl().'/me?access_token='.$this->accessToken);
	}
	
	public function sayHello(){
		if($this->me===null) $this->retrieveMe();
		return 'Hello '.$this->me('name','');
	}
	
	public function isValidMe(){
		//CLogger::get('debug-isValidMe')->log($this->accessToken);
		return !empty($this->me) && !isset($this->me['error']) && !empty($this->me['id']);
	}

    /**
     * @return string
     */
    abstract protected function getApiUrl();
    /**
     * @return string
     */
    abstract protected function getTokenUrl();
    /**
     * @return string
     */
    abstract protected function getOAuthUrl();
}