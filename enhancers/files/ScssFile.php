<?php
class ScssFile extends EnhancerFile{
	
	public function loadContent($srcContent){
		if(!$this->isCore()){
			if(file_exists($filename=dirname($this->srcFile()->getPath()).'/_mixins.scss'))
				$srcContent=file_get_contents($filename).$srcContent;
			$srcContent=file_get_contents(CORE.'includes/scss/mixins.scss').
						file_get_contents(CORE.'includes/scss/functions.scss').$srcContent;
		}
		
		$currentPath=dirname($this->srcFile()->getPath());
		$includes=array();
		$this->_srcContent=self::includes($srcContent,$currentPath,$includes);
	}
	
	
	public static function &includes($content,$currentPath,&$includes){
		$content=preg_replace_callback('/@include(Core|Lib)?\s+\'([\w\s\._\-\/]+)\'\;/Ui',function($matches) use($currentPath,&$includes){
			if(!endsWith($matches[2],'.css') && !endsWith($matches[2],'.scss')) $matches[2].='.scss';
			if(isset($includes[$matches[1]][$matches[2]])) return '';
			$includes[$matches[1]][$matches[2]]=1;
			
			/*if(!empty($matches[1]) && $matches[1]==='Core') */$core=defined('CORE')?CORE:CORE_SRC;
			if(empty($matches[1])) $filename=$currentPath.'/';
			else{
				$filename=$matches[1]==='Lib' ? dirname($core).'/' : $core;
				$filename.='includes/';
				
				$folderName=$matches[1]==='Lib'?'css/':'scss/';
				if(file_exists($filename.$folderName.$matches[2])) $filename.=$folderName;
			}
			$filename.=$matches[2];
			
			return ScssFile::includes(file_get_contents($filename),$currentPath,$includes);
		},$content);
		return $content;
	}
	
	
	public function enhanceContent(){
		$rules=array(
			'transition'=>array('-moz-transition','-webkit-transition','-o-transition'),
			'border-radius'=>array('-moz-border-radius','-webkit-border-radius','-ms-border-radius'),
			'border-top-right-radius'=>array('-moz-border-radius-topright','-webkit-border-top-right-radius'),
			'border-top-left-radius'=>array('-moz-border-radius-topleft','-webkit-border-top-left-radius'),
			'border-bottom-right-radius'=>array('-moz-border-radius-bottomright','-webkit-border-bottom-right-radius'),
			'border-bottom-left-radius'=>array('-moz-border-radius-bottomleft','-webkit-border-bottom-left-radius'),
			'box-shadow'=>array('-moz-box-shadow','-webkit-box-shadow'),
			'box-sizing'=>array('-moz-box-sizing','-webkit-box-sizing','-ms-box-sizing'),
			'appearance'=>array('-moz-appearance','-webkit-appearance'),
			'backface-visibility'=>array('-moz-backface-visibility','-webkit-backface-visibility')
		);
		foreach($rules as $rule=>$copyRules){
			$this->_srcContent=preg_replace_callback('/'.preg_quote($rule).':\s*([^;]+);/',function(&$m) use(&$rule,&$copyRules){
				$return='';
				foreach($copyRules as $copyRule) $return.=$copyRule.':'.$m[1].';';
				if(in_array($rule,array('border-radius','border-top-right-radius','border-top-left-radius','border-bottom-right-radius',
					'border-bottom-left-radius','box-shadow'))) $return.='@extend .iepie;';
				return $return.$m[0];
			},$this->_srcContent);
		}
	}
	public function getEnhancedDevContent(){return $this->_srcContent; }
	public function getEnhancedProdContent(){return $this->_srcContent; }
	
	public function writeDevFile($devFile){
		$this->callSass($this->getEnhancedDevContent(),$devFile->getPath(),true);
		if(($appDir=$this->enhanced->getAppDir()) && !$this->isCore()){
			if(!file_exists($appDir.'tmp/compiledcss/dev/')) mkdir($appDir.'tmp/compiledcss/dev/',0755,true);
			$devFile->copyTo($appDir.'tmp/compiledcss/dev/'.$devFile->getName());
		}
	}
	public function writeProdFile($prodFile){
		$this->callSass($this->getEnhancedProdContent(),$prodFile->getPath());
		if(($appDir=$this->enhanced->getAppDir())){
			if(!file_exists($appDir.'tmp/compiledcss/prod/')) mkdir($appDir.'tmp/compiledcss/prod/',0755);
			$prodFile->copyTo($appDir.'tmp/compiledcss/prod/'.$prodFile->getName());
		}
	}
	
	private static $sassExecutable='sass';
	public static function findSassPath(){
		if(file_exists('/var/lib/gems/1.8/bin/sass')) self::$sassExecutable='/var/lib/gems/1.8/bin/sass';
	}
	public function callSass($content,$destination){
		$dest=$destination?$destination:tempnam($this->enhanced->getTmpDir(),'scssdest');
		$tmpfname = tempnam($this->enhanced->getTmpDir(),'scss');
		$cmd = self::$sassExecutable.' --trace --compass --scss -t compressed -r '.escapeshellarg(CORE.'includes/scss/module.rb')
										.' '.escapeshellarg($tmpfname).' '.escapeshellarg($dest);
		file_put_contents($tmpfname,$content);
		$res=shell_exec('cd / && '.$cmd.' 2>&1');
		if(!empty($res)){
			throw new Exception("Error in scss conversion to css : ".$this->fileName()."\n".$res);
		}
		unlink($tmpfname);
		chmod($dest,0777);
		CssFile::executeCompressor($this->enhanced->getTmpDir(),file_get_contents($dest),$dest);
		
		if(!$destination){
			$destination=file_get_contents($dest);
			unlink($dest);
			return $destination;
		}
	}
	
	public static function afterEnhanceApp(&$enhanced,&$dev,&$prod){
		CssFile::afterEnhanceApp($enhanced,$dev,$prod);
	}
}
ScssFile::findSassPath();
