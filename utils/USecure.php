<?php
class USecure{
	private static $_config;
	
	public static function init(){
		self::$_config=Config::$secure;
	}
	
	public static function hashWithSalt($string){
		return sha1(self::$_config['salt'].$string);
	}
	
	public static function getSalt(){
		return self::$_config['salt'];
	}
	
	public static function hasAltSalt(){
		return isset(self::$_config['salt_alt']);
	}
	public static function hasWithAltSalt($string){
		return sha1(self::$_config['salt_alt'].$string);
	}
	
	
	public static function decryptAES($val,$ky=null){
		if($ky===null) $ky=self::$_config['crypt_key'];
		$val=base64_decode($val);
		//return mcrypt_decrypt(MCRYPT_RIJNDAEL_256,$key,$value,MCRYPT_MODE_CBC);
		$key="\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
		for($a=0;$a<strlen($ky);$a++)
			$key[$a%16]=chr(ord($key[$a%16]) ^ ord($ky[$a]));
		$mode = MCRYPT_MODE_ECB;
		$enc = MCRYPT_RIJNDAEL_128;
		$dec=mcrypt_decrypt($enc,$key,$val,$mode,mcrypt_create_iv(mcrypt_get_iv_size($enc,$mode),MCRYPT_DEV_URANDOM));
		return rtrim($dec,(( ord(substr($dec,strlen($dec)-1,1))>=0 and ord(substr($dec, strlen($dec)-1,1))<=16)? chr(ord( substr($dec,strlen($dec)-1,1))):null)); 
	}
	
	public static function encryptAES($val,$ky=null){
		if($ky===null) $ky=self::$_config['crypt_key'];
		$key="\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
		for($a=0;$a<strlen($ky);$a++)
			$key[$a%16]=chr(ord($key[$a%16]) ^ ord($ky[$a]));
		$mode=MCRYPT_MODE_ECB;
		$enc=MCRYPT_RIJNDAEL_128;
		$val=str_pad($val, (16*(floor(strlen($val) / 16)+(strlen($val) % 16==0?2:1))), chr(16-(strlen($val) % 16)));
		return base64_encode(mcrypt_encrypt($enc, $key, $val, $mode, mcrypt_create_iv( mcrypt_get_iv_size($enc, $mode), MCRYPT_DEV_URANDOM))); 
	}
}
USecure::init();
