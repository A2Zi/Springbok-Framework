<?php
class EnhancedApp extends Enhanced{
	private $controllers,$controllersDeleted;
	public $appConfig,$devConfig;
	
	public function __construct(&$dirname){
		parent::__construct($dirname);
		if(file_exists($this->getAppDir().'src/config/_.php')) $this->appConfig=include $this->getAppDir().'src/config/_.php';
		if(file_exists($this->getAppDir().'src/config/_'.ENV.'.php')) $this->devConfig=include $this->getAppDir().'src/config/_'.ENV.'.php';
	}
	
	public function &appConfig($attr){ return $this->appConfig[$attr]; }
	public function appConfigExist($attr){ return isset($this->appConfig[$attr]); }
	
	public function &devConfig($attr){ return $this->devConfig[$attr]; }
	public function devConfigExist($attr){ return isset($this->devConfig[$attr]); }
}
