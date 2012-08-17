<?php
class SiteController extends Controller{
	/** */
	function index(){
		 render();
	}
	
	/** */
	function favicon(){
		 renderFile(APP.'web/img/favicon.ico');
	}

	/** */
	function robots(){
		 renderText("User-agent: *\nAllow: /\n");
	}
	
	/** */
	function login(User $user){
		if(empty($_POST)) CSecure::connect();
		elseif($user!==null && CSecure::authenticate($user,true)) exit;
		else CSession::setFlash('Impossible de vous connecter : identifiant ou mot de passe invalide...','user/login');
		self::render();
	}
	
	/** */
	function logout(){
		CSecure::logout();
		self::redirect('/');
	}
	
	/** */
	function captchaImage(){
		CCaptcha::image();
	}
	
	/** @ValidParams @Required('url') */
	function jsError($website,$location,$url,$message,$line){
		JsLog::create($website,$location,$url,$message,$line);
	}
	
}