<?php
if(!defined('STDIN')) exit;
/* DEV */ini_set('display_errors',1);/* /DEV */
/* PROD */ini_set('display_errors',0);/* /PROD */
error_reporting(E_ALL | E_STRICT);

set_time_limit(0);

define('BASE_URL',''); define('APP_VERSION',''); define('WEB_FOLDER','');
include CORE.'springbok.php';


class App{
	public static function configArray($name,$withSuffix=false){
		return include APP.'config'.DS.$name.($withSuffix ? '_'.ENV : '').'.php';
	}
	
	public static function run($action,$argv){
		self::configArray('',true);
		Config::$autoload_default=APP.'models/';
		Config::$models_infos=APP.'models/infos/';
		//if(isset(Config::$base))
		//	foreach(Config::$base as $name) include CORE.'base/'.$name.'.php';
		try{
			if(file_exists($filename=APP.'cli/'.$action.'.php'))
				include $filename;
			else include CORE.'cli/'.$action.'.php';
		}catch(Exception $exception){
			if(!($exception instanceof HttpException)){
				$e=new HttpException(500,'Internal Server Error');
			}else $e=$exception;
			
			Springbok::handleException($exception);
			
			echo ''.$e->getHttpCode().' '.$e->getMessage()."\n";
			echo ''.$e->getDescription().'';
		}
		if(ob_get_length() > 0) echo PHP_EOL;
		ob_end_flush();
	}
	
	public static function shutdown(){}
	
	/**
	 * @param Exception $exception
	 */
	public static function displayException($exception,$forceDefault){
		echo ''.get_class($exception)."\n";
		echo ''.$exception->getMessage()/* DEV */.' ('.str_replace(array(APP,CORE),array('APP/','CORE/'),$exception->getFile()).':'.$exception->getLine().')'/* /DEV */.'';
		/* DEV */
		echo 'Backtrace : '.prettyBackTrace(0,$exception->getTrace()).'';
		/* /DEV */
	}

	public static function displayError($forceDefault,$code, $message, $file, $line){
		echo "PHP Error [".Springbok::getErrorText($code)."]\n";
		echo "$message"/* DEV */." ($file:$line)"/* /DEV */."\n";
		/* DEV */
		echo 'Backtrace :'.prettyBackTrace().'';
		/* /DEV */
	}
}

set_exception_handler('Springbok::handleException');
set_error_handler('Springbok::handleError',E_ALL | E_STRICT);
register_shutdown_function('Springbok::shutdown');
App::run($action,$argv);