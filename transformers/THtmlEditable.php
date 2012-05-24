<?php
class THtmlEditable extends THtml{
	
	protected $form,$pkValue,$modelName;
	
	public function __construct(&$component){
		$this->form=new HForm($this->modelName=$component->getModelName(),null,'get','div',false,false);
		echo HHtml::jsInline('window.editableTable=new S.HTableEditable('.json_encode(HHtml::url($component->editableUrl)).')');
		parent::__construct($component);
	}
	
	public function displayValue(&$field,&$value,&$obj){
		if(isset($field['editable']) && $field['editable']) $field['escape']=false;
		if($field['key']===$this->component->editablePkField) $this->pkValue=$value;
		parent::displayValue($field,$value,$obj);
	}
	
	public function getDisplayableValue(&$field,&$value,&$obj){
		if(isset($field['editable']) && $field['editable']){
			$modelName=&$this->modelName;
			$jsonPkValue=json_encode($this->pkValue);
			
			//<input type="text" value="'.h($value).'" style="width:98%" onchange=""/>
			$def=$modelName::$__PROP_DEF[$name=$field['key']];
			$infos=$modelName::$__modelInfos['columns'][$name];
			
			$attributes=array('onchange'=>'editableTable.updateField(\''.$name.'\','.$jsonPkValue.',this)','value'=>&$value);
			$containerAttributes=array('sytle'=>'width:100%;position:relative');
			
			if(substr($name,-3)==='_id' && Controller::_isset($vname=UInflector::pluralize(substr($name,0,-3))))
				return $this->form->select($name,Controller::get($vname));
			elseif($def['type']==='boolean'){
				$attrs=$attributes;
				if($value==='') $attrs['checked']=true;
				return $this->form->checkbox($name,false,$attrs,$containerAttributes);
			}elseif(isset($def['annotations']['Enum'])) return $this->form->select($name,call_user_func($modelName.'::'.$def['annotations']['Enum'].'List'),
																	array('onchange'=>$attributes['onchange'],'selected'=>&$value),$containerAttributes);
			elseif(isset($def['annotations']['Text'])) return $thisform->textarea($name,$attributes,$containerAttributes);
			else return $this->form->input($name,$attributes+array('style'=>'width:98%'),$containerAttributes,1.4);
		}
		return parent::getDisplayableValue($field,$value,$obj);
	}
}