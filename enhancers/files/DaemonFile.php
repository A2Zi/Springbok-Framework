<?php
class DaemonFile extends PhpFile{
	public static $CACHE_PATH=FALSE;//'daemon_8.0';
	
	public function enhancePhpContent($phpContent,$false=false){
		preg_match('/class ([A-Za-z_]+)Daemon/',$phpContent,$matches);//debug($matches);
		if(empty($matches[1])) return parent::enhancePhpContent($phpContent);
		$className=$matches[1];
		$val=false;
		self::$_daemonsConfig[$className]=$val;
		self::$_changes=true;
		return parent::enhancePhpContent($phpContent);
	}
	
	private static $_daemonsConfig,$_changes=false;
	public static function initFolder($folder,$config){
		$f=new File($folder->getPath().'config/daemons.php');
		if($f->exists()){
			//$f->moveTo($tmpFolder.'daemons.php');
			self::$_daemonsConfig=include $f->getPath();
		}else self::$_daemonsConfig=array();
	}
	public static function fileDeleted($file){
		self::$_changes=true;
		$daemonName=substr($file->getName(),0,-4);
		unset(self::$_daemonsConfig[$daemonName]);
	}
	
	public static function afterEnhanceApp(&$enhanced,&$dev,&$prod){
		if(self::$_changes){
			$content='<?php return '.UPhp::exportCode(self::$_daemonsConfig).';';
			file_put_contents($dev->getPath().'config/daemons.php',$content);
			file_put_contents($prod->getPath().'config/daemons.php',$content);
		}/*elseif($hasOldDef){
			$f=new File($tmpDev.'daemons.php'); if(!$f->exists())return; $f->moveTo($dev->getPath().'config/daemons.php');
			$f=new File($tmpProd.'daemons.php'); $f->moveTo($prod->getPath().'config/daemons.php');
		}*/
	}
}
