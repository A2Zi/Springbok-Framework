<?php
class STransformer{
	
	public static function getDisplayableValue(&$field,&$value,&$obj){
		if(isset($field['callback'])){
			if($value===null) $value=false;
			return call_user_func($field['callback'],$value);
		}elseif(isset($field['function'])){
			if($value===null) $value=false;
			return call_user_func($field['function'],$obj,$value);
		}elseif(isset($field['tabResult'])){
			if($value===null) $value=false;
			if(isset($field['tabResult'][$value])) return $field['tabResult'][$value];
		}
		return $value;
	}
	
	public static function getValueFromModel(&$model,&$field,&$i){
		return isset($field['key']) ? $model->_get($field['key']) : false;
	}
	
	public function startHead(){}
	public function endHead(){}
	public function startBody(){}
}
