<?php
class StylusFile extends EnhancerFile{
	public static $CACHE_PATH='styl_8.0.8';
	
	
	public function loadContent($srcContent){
		$currentPath=dirname($this->srcFile()->getPath());
		$includes=array();
		$this->_srcContent=self::includes($srcContent,$currentPath,$includes,$this->enhanced);
	}
	
	
	public static function includes($content,$currentPath,&$includes,&$enhanced){
		$content=preg_replace_callback('/@import?\s+\'([\w\s\._\-\/]+)\'\;/Ui',function($matches) use($currentPath,&$includes,&$enhanced){
			if(!endsWith($matches[1],'.css') && !endsWith($matches[1],'.styl')) $matches[1].='.styl';
			if($matches[1]==='base/buttonsOverride.styl') $matches[1]='base/buttons.styl';
			elseif(isset($includes[$matches[1]])) return '';
			$includes[$matches[1]]=1;
			
			/*if(!empty($matches[1]) && $matches[1]==='Core') */$core=defined('CORE')?CORE:CORE_SRC;
			if(strpos($matches[1],'/')!==false){
				list($first,$fileName)=explode('/',$matches[1],2);
				if($first==='plugin'){
					list($pluginKey,$fileName)=explode('/',$fileName,2);
					$filename=$enhanced->pluginPathFromKey($pluginKey).'web/css/';
					$matches[1]=$fileName;
				}
			}
			if(!isset($filename)){
				if(file_exists($currentPath.'/'.$matches[1])) $filename=$currentPath.'/';
				elseif(file_exists(dirname($core).'/includes/'.$matches[1])) $filename=dirname($core).'/includes/';
				else $filename=$core.'includes/styl/';
			}
			$filename.=$matches[1];
			
			return StylusFile::includes(file_get_contents($filename),$currentPath,$includes,$enhanced);
		},$content);
		return $content;
	}
	
	
	public function enhanceContent(){
		//$this->_srcContent=$this->hardConfig($this->_srcContent);
	}

	public function getEnhancedDevContent(){}
	public function getEnhancedProdContent(){}
	
	public function writeDevFile($devFile){
		$this->callStylus($this->_srcContent,$devFile->getPath(),true);
		return true;
	}
	public function writeProdFile($prodFile){
		$this->getDevFile()->copyTo($prodFile->getPath());
		return true;
	}

	public function callStylus($content,$destination,$debug){
		$dest=$destination?$destination:tempnam($this->enhanced->getTmpDir(),'styldest');
		$res=exec($cmd='cd / && echo '.escapeshellarg($content).' 2>&1 | stylus --include-css -c -I '.escapeshellarg(dirname($this->srcFile()->getPath()))
																		.' -I '.escapeshellarg(CORE.'includes/styl')
				.' 2>&1 | cleancss'.($destination?' > '.escapeshellarg($dest):' 2>&1'),$output,$status);
		debugPrintr($cmd);
		debugVar($res,$output,$status);
		if(!empty($res)){
			throw new Exception("Error in stylus conversion to css : ".$this->fileName()."\n".$res);
		}
		chmod($dest,0777);
		
		if(!$destination) return $res;
	}
}