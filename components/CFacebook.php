<?php
/*include CLIBS.'facebook/facebook.php';*/
class CFacebook{
	/*private $facebook;
	
	public function __construct(){
		$this->facebook=new Facebook(array('appId'=>Config::$facebook_appId,'secret'=>Config::$facebook_secret));
	}
	
	public function fql($query){
		return $this->facebook->api(array('method' => 'fql.query','query' => $fql));
	}
	
	public function getFriends(){
		return $this->fql('SELECT name from user where uid = ' . $user_id);
	}
	*/
	
	public static function redirectForConnection($url,$state,$scope){
		Controller::redirect('https://www.facebook.com/dialog/oauth?client_id=220086468082002&redirect_uri='.urlencode($url).'&state='.$state.(empty($scope)?'':'&scope='.$scope));
	}
	
	public static function getAccessToken($url,$code){
		$token_url='https://graph.facebook.com/oauth/access_token?client_id='.Config::$facebook_appId.'&redirect_uri='.urlencode($url).'&client_secret='.Config::$facebook_secret.'&code='.$code;
		$response=file_get_contents($token_url);
		parse_str($response,$params);
		return $params['access_token'];
	}
	
	
	private $accessToken,$me=null;
	public function __construct($accessToken,$retrieveMe=false){
		$this->accessToken=&$accessToken;
		if($retrieveMe===true) $this->retrieveMe();
	}
	
	public function retrieveMe(){
		$graph_url = "https://graph.facebook.com/me?access_token=".$this->accessToken;
		$this->me=json_decode(CSimpleHttpClient::get($graph_url),true);
		return $this->me;
	}
	
	public function isValidMe(){
		return !empty($this->me) && !isset($this->me['error']) && !empty($this->me['id']);
	}
	
	public function &me($name){
		return $this->me[$name];
	}
	
	public function sayHello(){
		if($this->me===null) $this->retrieveMe();
		return 'Hello '.$this->me['name'];
	}
	
	public function createUser(){
		$user=new User();
		$facebookUser=new UserFacebook();
		if($this->updateUserInfo($user,$facebookUser)){
			$user->insert();
			$facebookUser->user_id=$user->id;
			$facebookUser->insert();
			return true;
		}
		return false;
	}
	
	public function updateUserInfo(&$user,$facebookUser){
		if($this->me===null) $this->retrieveMe();
		if(!$this->isValidMe()) return false;
		$user->first_name=$this->me['first_name'];
		$user->last_name=$this->me['last_name'];
		$facebookUser->access_token=$this->accessToken;
		$facebookUser->facebook_id=$this->me['id'];
		$facebookUser->facebook_username=$this->me['username'];
		$facebookUser->link=$this->me['link'];
		if(isset($this->me['email'])) $user->email=$facebookUser->email=$this->me['email'];
		$facebookUser->facebook_verified=$this->me['verified'];
		return true;
	}
}
