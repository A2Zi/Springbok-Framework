<?php
trait BSlugRedirectable{
	protected function _setOldSlug(){
		if(!empty($this->slug)){
			$oldSlug=self::QValue()->field('slug')->byId($this->id)->execute();
			if(!empty($oldSlug) && $oldSlug!=$this->slug) $this->oldSlug=$oldSlug;
		}
		return true;
	}
	protected function _addSlugRedirect(){
		if(!empty($this->slug)){
			if(!empty($this->oldSlug)) SlugRedirect::add(static::$__className,$this->oldSlug,$this->slug);
			SlugRedirect::slugAdded(static::$__className,$this->slug);
		}
	}
}