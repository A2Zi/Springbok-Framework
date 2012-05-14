<?php
abstract class AFolderEnhancer{
	private $dir,$devDir,$prodDir,$enhanced;
	
	public function __construct(&$enhanced,&$dir,&$devDir,&$prodDir){
		$this->enhanced=&$enhanced; $this->dir=&$dir;$this->devDir=&$devDir;$this->prodDir=&$prodDir;
	}
	
	
	public static function registerFileEnhancers(){}
	public static function findEnhancer(&$filename,&$ext){
		foreach(static::$fileEnhancers as &$fileEnhancer){
			if(!((is_string($fileEnhancer['ext']) && $ext==$fileEnhancer['ext']) || (is_array($fileEnhancer['ext']) && in_array($ext,$fileEnhancer['ext'])))) continue;
			$justSrc=$fileEnhancer['_justsrc'] ? substr($filename,0,1)=='_' : false;
			$copy=$fileEnhancer['copy']?true:false;
			$destFilename=false;
			if($fileEnhancer['destExt']!==false && $fileEnhancer['destExt']!==$ext) $destFilename=substr($filename,0,-strlen($ext)).$fileEnhancer['destExt'];
			return array($fileEnhancer['class'],$justSrc,$destFilename,$copy);
		}
		return false;
	}
	public static function registerEnhancer($class,$ext,$_justsrc=false,$destExt=false,$copy=false){
		static::$fileEnhancers[]=array('class'=>&$class,'ext'=>&$ext,'_justsrc'=>$_justsrc,'destExt'=>$destExt,'copy'=>$copy);
	}
	
	
	public function process($class='PhpFile',$exclude=false,$allowUnderscoredFiles=true){
		$dir=&$this->dir;$devDir=&$this->devDir;$prodDir=&$this->prodDir;
		
		if(substr($dir->getName(),0,1)==='.') return;
		$devFolder=new Folder($devDir,0775);
		$prodFolder=new Folder($prodDir,0775);
		
		$files=$dir->listFiles(false);
/*
		if($exclude!==true){
			foreach(array_diff_key($devFolder->listFiles(false),$files) as $f){
				if($exclude && in_array($f->getName(),$exclude)) continue;
				$f->delete();
				if($class !== 'PhpFile') $class::fileDeleted($f);
			}
			foreach(array_diff_key($prodFolder->listFiles(false),$files) as $f) if(!$exclude || !in_array($f->getName(),$exclude)) $f->delete();
		}*/

		foreach($files as $file){
			$filename=$file->getName();
			$ext=$file->getExt();
			
			$found=$this->findEnhancer($filename,$ext);
			if($found===false){
				$justSrc=$justDev=$destFilename=false;
				$copy=$ext!=='php';
				if(!$allowUnderscoredFiles && $this->enhanced instanceof EnhancedApp) $justSrc=$filename[0]==='_';
			}else{
				$justDev=false;
				list($class,$justSrc,$destFilename,$copy)=$found;
			}
			
			if($justSrc) continue;
			if($destFilename===false) $destFilename=$filename;
			
			if($copy){
				$srcMD5=md5_file($file->getPath());
				
				if(!(file_exists($devDir.$destFilename) && file_exists($prodDir.$destFilename)
						&& isset($this->enhanced->oldDef['files'][$file->getPath()])
						&& $this->enhanced->oldDef['files'][$file->getPath()]==$srcMD5)){
					//debugVar('file changed :',$file->getPath(),file_exists($devDir.$filename),file_exists($prodDir.$filename),isset($this->oldDef['files'][$file->getPath()]),!isset($this->oldDef['files'][$file->getPath()])?null:$this->oldDef['files'][$file->getPath()]==$srcMD5);
					$this->enhanced->newDef['changes']['all'][]=array('path'=>$file->getPath());
					copy($file->getPath(),$devDir.$destFilename);
					copy($file->getPath(),$prodDir.$destFilename);
					$this->enhanced->newDef['enhancedFiles'][$file->getPath()]=array('class'=>false,'dev'=>$devDir.$destFilename,'prod'=>$prodDir.$destFilename);
				}
				$this->enhanced->newDef['files'][$file->getPath()]=$srcMD5;
				continue;
			}
			
			
			
			
			/*
			if($ext==='css' || $ext==='sbcss'){
				if(substr($filename,0,1)=='_') $justDev=true;
				$class='CssFile';
				if($ext==='sbcss') $destFilename=substr($filename,0,-6).'.css';
			}elseif($ext==='scss'){
				if(substr($filename,0,1)=='_') $justDev=true;
				$class='ScssFile';
			}elseif(in_array($ext,array('jpg','jpeg','png','gif'))){
				if(substr($filename,0,1)=='_') $justDev=true;
				$class='ImgFile';
			}elseif($ext==='js'){
				if(substr($filename,0,1)=='_') $justDev=true;
				$class='JsFile';
			}elseif($ext!=='php'){
				
			}
			*/
			if($class==='ConfigFile' && ($filename==='enhance.php'||$filename==='_.php'||startsWith($filename,'routes-langs'))) continue;
			
			if($class==='ControllerFile'){
				if(($entrance=basename(dirname($file->getPath()))) != 'controllers') $key=$entrance.DS;
				else $key='';
				$this->controllers[$key][]=substr($filename,0,-4);
				if($filename[0]==='_') $justDev=true; 
			}
			
			$nf=new $class($this->enhanced,$file->getPath());
			$srcMD5=$nf->getMd5Content();
			$in=false;
			//$t=microtime(true);
			if(!(file_exists($devDir.$destFilename) && ($justDev || file_exists($prodDir.$destFilename))
					&& isset($this->enhanced->oldDef['files'][$file->getPath()])
					&& $this->enhanced->oldDef['files'][$file->getPath()]==$srcMD5)){
				//debugVar('file changed :',$file->getPath(),file_exists($devDir.$destFilename),file_exists($prodDir.$destFilename),isset($this->oldDef['files'][$file->getPath()]),!isset($this->oldDef['files'][$file->getPath()])?null:$this->oldDef['files'][$file->getPath()]==$srcMD5);
				if($issetCurrentFileEnhanced=isset(App::$currentFileEnhanced)) App::$currentFileEnhanced=$file->getPath();
				$nf->processEhancing($devDir.$destFilename,$prodDir.$destFilename,$justDev);
				if($issetCurrentFileEnhanced) App::$currentFileEnhanced='';
				$this->enhanced->newDef['changes']['all'][]=array('path'=>$file->getPath());
				$this->enhanced->newDef['changes'][substr($class,0,-4)][]=$file->getPath();
				
				if($nf->hasWarnings()) $this->enhanced->addWarnings($file->getPath(),$nf->getWarnings());
				if($nf->hasErrors()) $this->enhanced->addErrors($file->getPath(),$nf->getErrors());
				
				$this->enhanced->newDef['enhancedFiles'][$file->getPath()]=array('class'=>$class,'dev'=>$devDir.$destFilename,'prod'=>$justDev?false:$prodDir.$destFilename);
			}
			$this->enhanced->newDef['files'][$file->getPath()]=$srcMD5;
			/*$t=(microtime(true) - $t);
			if($t > 1) debugVar($file->getPath() .' : '.$t,$in,
				!file_exists($devDir.$destFilename) || !($justDev || file_exists($prodDir.$destFilename))
					|| !isset($this->oldDef['files'][$file->getPath()])
					||$this->oldDef['files'][$file->getPath()]!=$srcMD5,
					file_exists($devDir.$destFilename) && ($justDev || file_exists($prodDir.$destFilename)),
					isset($this->oldDef['files'][$file->getPath()]),
					isset($this->oldDef['files'][$file->getPath()]) && $this->oldDef['files'][$file->getPath()]==$srcMD5
			);*/
		}
	}
}