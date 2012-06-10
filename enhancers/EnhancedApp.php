<?php
class EnhancedApp extends Enhanced{
	private $controllers,$controllersDeleted,$md5EnhanceConfig;
	public $appConfig,$devConfig;
	
	public function __construct($type,&$dirname){
		parent::__construct($type,$dirname);
		if(file_exists($this->getAppDir().'src/config/_.php')) $this->appConfig=include $this->getAppDir().'src/config/_.php';
		if(file_exists($this->getAppDir().'src/config/_'.ENV.'.php')) $this->devConfig=include $this->getAppDir().'src/config/_'.ENV.'.php';
		$this->md5EnhanceConfig=empty($this->config['config'])?'':implode('~',$this->config['config']);
	}
	
	public function getTmpDir(){
		return $this->getAppDir().'tmp/';
	}
	
	public function &appConfig($attr){ return $this->appConfig[$attr]; }
	public function appConfigExist($attr){ return isset($this->appConfig[$attr]); }
	
	public function &devConfig($attr){ return $this->devConfig[$attr]; }
	public function devConfigExist($attr){ return isset($this->devConfig[$attr]); }
	
	public function md5EnhanceConfig(){ return $this->md5EnhanceConfig; }
}
