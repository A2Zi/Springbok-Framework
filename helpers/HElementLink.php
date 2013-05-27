<?php
class HElementLink extends HElementWithContent{
	public function icon($icon,$text){
		return $this->iconHtml($icon,h($text));
	}
	public function iconHtml($icon,$html){
		/* DEV */ if(!empty($this->content)) throw new Exception('$this->content is not empty'); /* /DEV */
		$this->content='<span class="icon '.h($icon).'"></span>'.$html;
		$this->contentEscape=false;
		/* DEV */ if(isset($this->attributes['class'])) throw new Exception('specify your attr "class" after calling icon() or iconHtml()'); /* /DEV */
		$this->attrClass('aicon');
		return $this;
	}
	
	public function href($href){ $this->attributes['href']=$href; return $this; }
	public function url($url,$entry=null,$full=null,$escape=false,$cache=false,$https=null){
		$this->attributes['href']=HHtml::url($url,$entry,$full,$escape,$cache,$https);
		return $this;
	}
	
	
	public function __toString(){ return $this->_render('a'); }
}