<?php
include_once __DIR__.DS.'AEnhance.php';
include_once __DIR__.DS.'DefaultFolderEnhancer.php';

class EnhanceLibs extends AEnhance{
	/**
	 * @param Folder $dir
	 */
	public function recursiveDir(Folder $dir,$devDir,$prodDir,$class=false){
		$dirs=$dir->listDirs(false);
		$devFolder=new Folder($devDir); $prodFolder=new Folder($prodDir);
		
		foreach(array_diff_key($devFolder->listDirs(false),$dirs) as $d) $d->delete();
		foreach(array_diff_key($prodFolder->listDirs(false),$dirs) as $d) $d->delete();
		
		$defaultClass=$class;
		foreach($dirs as $d){
			$dirname=$d->getName();
			if($dirname[0]==='.') continue;
			
			$newDevDir=$devDir.$dirname.DS; $newProdDir=$prodDir.$dirname.DS;
			
			$srcDir=$this->appDir.'src'; $excludeFiles=false;
			if($defaultClass===false){
				$class='PhpFile';
			}else $class=$defaultClass;
			
			$folderEnhancer=new DefaultFolderEnhancer($this->enhanced,$d, $newDevDir,$newProdDir);
			$folderEnhancer->process($class);
			
			$this->recursiveDir($d, $newDevDir,$newProdDir,$class);
			
		}
	}
}
