<?php
class HSitemapMobile extends HSitemap{
	public function __construct($file='sitemap-mobile.xml',$extensions=array()){
		$extensions[]='mobile';
		parent::__construct($file,$extensions);
	}
	
	public function add($url,$options=array(),$entry='mobile'){
		$options['mobile:mobile']=null;
		parent::add($url,$options,$entry);
	}
}