<?php
class UselessFile extends EnhancerFile{
	public static $CACHE_PATH=false;
	
	protected function loadContent($srcContent){ $this->_srcContent=''; }
	public function enhanceContent(){}
	public function getEnhancedDevContent(){ return $this->_srcContent; }
	public function getEnhancedProdContent(){ return $this->_srcContent; }
	
	public function writeDevFile($devFile){}
	public function writeProdFile($prodFile){}
}