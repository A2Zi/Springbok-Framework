<?php
abstract class QFind extends QSelect{
	protected $alias,$joins=array(),$with=array(),$queryResultFields,$objData,$joinData,$objFields,$allFields=array();

	//private $equalsFieldsInConditions;
	//public function &calcFoundRows(){$this->calcFoundRows=true;return $this;}

	public function &alias($alias){ $this->alias=&$alias; return $this; }

	public function &join($modelName,$fields=NULL,$conditions=array(),$options=array()){
		$this->_join(',',$modelName,$fields,$conditions,$options);
		return $this;
	}
	public function &leftjoin($modelName,$fields=NULL,$onConditions=array(),$options=array()){
		$this->_join(' LEFT JOIN ',$modelName,$fields,$onConditions,$options);
		return $this;
	}
	public function &innerjoin($modelName,$fields=NULL,$onConditions=array(),$options=array()){
		$this->_join(' INNER JOIN ',$modelName,$fields,$onConditions,$options);
		return $this;
	}
	public function &rightjoin($modelName,$fields=NULL,$onConditions=array(),$options=array()){
		$this->_join(' RIGHT JOIN ',$modelName,$fields,$onConditions,$options);
		return $this;
	}
	public function &fulljoin($modelName,$fields=NULL,$onConditions=array(),$options=array()){
		$this->_join(' FULL JOIN ',$modelName,$fields,$onConditions,$options);
		return $this;
	}
	/** options: withoutFields, fieldsInModel, dataName */
	private function _join($type,$modelName,$fields,$onConditions,$options){
		$join=array('type'=>$type,'modelName'=>$modelName,'fields'=>$fields,'onConditions'=>$onConditions)+$options
			+array('fieldsInModel'=>false,'dataName'=>lcfirst($modelName),'isCount'=>false);
		if(!isset($join['alias'])) $join['alias']=$modelName::$__alias;
		$this->joins[]=$join; 
	}
	
	public function &setAllWith($with){
		foreach($with as $key=>&$options){
			if(is_numeric($key)){ $key=$options; $options=array();}
			self::_addWith($this->with,$key,$options,$this->modelName);
		};
		return $this;
	}
	public function &with($with,$options=array()){if(!is_array($options)) $options=array('fields'=>$options); self::_addWith($this->with,$with,$options,$this->modelName);return $this;}
	
	public function &withLang($options=array(),$lang=false){
		if($lang===false) $lang=CLang::get();
		if(is_string($options)) $options=array('fields'=>$options);
		$options+=array('fieldsInModel'=>true,'forceJoin'=>true,'onConditions'=>array('lang'=>$lang));
		$mL=$this->modelName.'Lang';
		self::_addWith($this->with,$mL,$options,$this->modelName);
		return $this;
	}	
	public static function _addWith(&$withArray,&$key,&$options,&$modelName){
		/* DEV */if(!isset($modelName::$_relations[$key])) throw new Exception($modelName.' does not have a relation named "'.$key.'"'."\n".'Known relations : '.implode(', ',array_keys($modelName::$_relations))); /* /DEV */
		$relation=&$modelName::$_relations[$key];
		/* DEV */
		if(!is_array($options)) throw new Exception('options is not array : '.print_r($options,true));
		if(!is_array($relation)) throw new Exception('relation is not array : '.print_r($relation,true));
		/* /DEV */
		$foptions=$options+$relation;
	
		if(isset($foptions['fields']) && is_string($foptions['fields'])) $foptions['fields']=explode(',',$foptions['fields']);
		if(isset($foptions['with'])) foreach($foptions['with'] as $kW=>&$opW){ if(is_int($kW)){unset($foptions['with'][$kW]); $kW=$opW;$opW=array();} self::_addWith($foptions['with'],$kW,$opW,$foptions['modelName']); }
		$withArray[$key]=&$foptions;
	}
	
	
	public function &_setWith(&$with){$this->with=&$with;return $this;}
	public function &_setJoin(&$join){$this->join=&$join;return $this;}
	
	private function _addWithInJoin(&$modelName,&$modelAlias,&$key,&$join,$inRecursive=false){
		$joinModelName=$join['modelName'];
		if(!($join['reltype']==='belongsTo' || $join['reltype']==='hasOne' || $join['reltype']==='hasOneThrough' || $join['forceJoin']===true || $join['isCount']===true)
				 || ($modelName::$__dbName!==$joinModelName::$__dbName && !$modelName::$__modelDb->isInSameHost($joinModelName::$__modelDb))) return false;
		//if(!empty($join['with'])) return false; //TODO should be handled someplace else because here generate a lot of requests... 
		if($join['reltype'] === 'hasOneThrough' || $join['reltype'] === 'hasManyThrough'){
			$lastAlias=$modelAlias;$lastModelName=$this->modelName;
			foreach($join['joins'] as $relName=>$options){
				if(is_int($relName)){ $relName=$options; $options=array(); }
				$options+=array('fields'=>false,'forceJoin'=>true);
				/* DEV */if(!isset($lastModelName::$_relations[$relName])) throw new Exception($lastModelName.' does not have a relation named "'.$relName.'"'."\n".'Known relations : '.implode(', ',array_keys($lastModelName::$_relations))); /* /DEV */
				$options+=$lastModelName::$_relations[$relName];
				
				$onConditions=array($lastAlias.'.'.$options['foreignKey'].'='.$options['alias'].'.'.$options['associationForeignKey']);
				if(isset($options['onConditions'])) $options['onConditions']=array_merge($options['onConditions'],$onConditions);
				else $options['onConditions']=$onConditions;
				
				$lastAlias=$options['alias'];$lastModelName=$options['modelName'];
				$this->joins[$lastAlias]=$options;
			}
			
			$options=$join+$lastModelName::$_relations[$key];
			$onConditions=array($lastAlias.'.'.$options['foreignKey'].'='.$options['alias'].'.'.$options['associationForeignKey']);
			if(isset($options['onConditions'])) $join['onConditions']=array_merge($options['onConditions'],$onConditions);
			else $options['onConditions']=$onConditions;
			$options['reltype']=substr($join['reltype'],0,-7);
			unset($options['joins']);
			$this->joins[$options['alias']]=$options;
		}else{
			if($join['foreignKey']!==false){
				$onConditions=array($modelAlias.'.'.$join['foreignKey'].'='.$join['alias'].'.'.$join['associationForeignKey']);
				if(isset($join['onConditions'])) $join['onConditions']=array_merge($join['onConditions'],$onConditions);
				else $join['onConditions']=$onConditions;
			}
			$this->joins[$join['alias']]=$join;
		}
		return true;
	}
	
	private function _recursiveWith(&$with,&$modelName,&$alias){
		foreach($with as $key=>&$join){
			if($this->_addWithInJoin($modelName,$alias,$key,$join)===false) continue;
			unset($with[$key]);
			if(isset($join['with'])) $this->_recursiveWith($join['with'],$join['modelName'],$join['alias'],true);
		}
	}
	
	
	public function reexecute(){
		$this->objFields=$this->queryResultFields=$this->objData=array();
		return $this->execute();
	}
	
	public function &_toSQL($currentDb=NULL){
		$modelName=&$this->modelName;
		
		$modelAlias=$this->alias!==NULL?$this->alias:(!empty($this->with) || !empty($this->joins)?$modelName::$__alias:NULL);
		
		if(!empty($this->with)){
			foreach($this->with as $key=>$join){
				if($this->_addWithInJoin($modelName,$modelAlias,$key,$join)===false) continue;
				unset($this->with[$key]);
				if(isset($join['with'])) $this->_recursiveWith($join['with'],$join['modelName'],$join['alias']);
			}
		}

		$fieldPrefix=$modelAlias!==NULL?$modelAlias.'.':'';

		$sql=$this->_SqlStart();
		
		if(isset($this->fields)){
			if($this->fields){
				foreach($this->fields as $field=>$alias){
					if(is_int($field)){ $field=$alias; $alias=false;}
					/*if($aspos=strpos($field,' AS')){
						$fieldAlias=$this->allFields['_'][]=substr($field,$aspos+4);
						$sql.=substr($field,0,$aspos).' '.$this->_db->formatField($fieldAlias);
					}else */
					if(($fpos=strpos($field,'('))!==false){
						$sql.=$field;
					}elseif(substr($field,0,4)==='CASE'){
						$fpos=true;
						$sql.=$field;
					}elseif(substr($field,0,8)==='DISTINCT'){
						$field=substr($field,9);
						$sql.='DISTINCT '.$fieldPrefix.$this->_db->formatField($field);
					}else $sql.=is_numeric($field)?$field:$fieldPrefix.$this->_db->formatField($field);
					if($alias!==false){
						$this->objFields[]=$alias;
						//$this->objData[$alias]=null;
						$this->queryResultFields[]=&$this->objData[$alias];
						if($fpos!==false) $sql.=' AS '.$this->_db->formatField($alias);
					}else{
						$this->objFields[]=$field;
						//$this->objData[$field]=null;
						$this->queryResultFields[]=&$this->objData[$field];
					}
					$sql.=',';
				}
			}
		}else{
			$this->objFields=$modelName::$__modelInfos['colsName'];
			foreach($this->objFields as $field){
				//$this->objData[$field]=null;
				$this->queryResultFields[]=&$this->objData[$field];
			}	
			$sql.=$fieldPrefix.'*,';
		}

		if(!empty($this->joins)){
			$hasCount=false;
			foreach($this->joins as $join){
				$joinModelName=$join['modelName'];
				if(isset($join['fields'])){
					if($join['fields']!==false){
						if(is_string($join['fields'])) $join['fields']=explode(',',$join['fields']);
						foreach($join['fields'] as $field=>$alias){
							if(is_int($field)){ $field=$alias; $alias=false;}
							if($fpos=strpos($field,'(')){
								$sql.=$field;
							}else{
								if(substr($field,0,8)==='DISTINCT'){
									$field=substr($field,9);
									$sql.='DISTINCT '.$join['alias'].'.'.$this->_db->formatField($field);
								}else{
									$sql.=is_int($field)?$field:$join['alias'].'.'.$this->_db->formatField($field);
								}
							}
							if($alias){
								$this->allFields[$join['alias']][]=$alias;
								//$this->joinData[$join['alias']][$alias]=null;
								$this->queryResultFields[]=&$this->joinData[$join['alias']][$alias];
								if($fpos) $sql.=' AS '.$this->_db->formatField($alias);
							}else{
								$this->allFields[$join['alias']][]=$field;
								//$this->joinData[$join['alias']][$field]=null;
								$this->queryResultFields[]=&$this->joinData[$join['alias']][$field];
							}
							$sql.=',';
						}
					}
				}elseif($join['isCount']){
					$hasCount=true;
					$sql.='COUNT(';
					if($join['isDistinct']) $sql.='DISTINCT ';
					if($join['isCount']===true) $sql.=$join['alias'].'.'.$joinModelName::_getPkName();
					else $sql.=$join['isCount'];
					$sql.=') AS '.$this->_db->formatField($join['dataName']).',';
					$this->objFields[]=$join['dataName'];
					//$this->objData[$join['dataName']]=null;
					$this->queryResultFields[]=&$this->objData[$join['dataName']];
				}else{
					$this->allFields[$join['alias']]=$joinModelName::$__modelInfos['colsName'];
					foreach($this->allFields[$join['alias']] as $field){
						//$this->joinData[$join['alias']][$field]=null;
						$this->queryResultFields[]=&$this->joinData[$join['alias']][$field];
					}
					$sql.=$join['alias'].'.*,';
				}
			}
			if($hasCount && empty($this->groupBy)) $this->groupBy=array($modelName::_getPkName());
		}
		
		$sql=substr($sql,0,-1).' FROM '.($currentDb!==NULL && $currentDb->getDbName() !== $modelName::$__modelDb->getDbName()?$modelName::$__modelDb->getDbName().'.':'').$modelName::_fullTableName();
		if($modelAlias!==NULL) $sql.=' '.$modelAlias;
		
		if(!empty($this->joins)){
			foreach($this->joins as $join){
				$sql.=$join['type'].($modelName::$__dbName!==$join['modelName']::$__dbName || ($currentDb!==NULL && $currentDb->getDbName() !== $join['modelName']::$__modelDb->getDbName())?$join['modelName']::$__modelDb->getDbName().'.':'')
					.$join['modelName']::_fullTableName().' '.$join['alias'];
				if(!empty($join['onConditions'])){
					$sql.=' ON ';
					$sql=$this->_condToSQL($join['onConditions'],'AND',$sql);
				}
			}
		}
		
		if(!empty($this->where)){
			$sql.=' WHERE ';
			$sql=$this->_condToSQL($this->where,'AND',$sql,$fieldPrefix);
		}

		$sql=$this->_afterWhere($sql,$fieldPrefix);
		return $sql;
	}

	/*
	protected function createEqualsFields(){
		$this->equalsFieldsInConditions=array();
	}
	*/
	
	public function &_createObject(&$row){
		$data=array();
		$pdoI=0;
		if($this->objFields){
			foreach($this->objFields as &$fieldName) $data[$fieldName]=$row[$pdoI++];
			/*if($this->fields !== NULL && $this->addByConditions !== false){
				foreach($this->addByConditions as $fieldName=>&$value) $data[$fieldName]=$value;
			}*/
		}
		$obj=CBinder::_bindObjectFromDB($this->modelName,$data);
		foreach($this->allFields as $alias=>&$fields){
			$join=$this->joins[$alias];
			$data=array();
			foreach($fields as &$fieldName) $data[$fieldName]=&$row[$pdoI++];
			$data=CBinder::_bindObjectFromDB($join['modelName'],$data);
			if($join['fieldsInModel']){
				foreach($data as $fieldname=>$v) $obj->_set($fieldname,$v);
			}else $obj->$join['dataName']=$data;
		}
		return $obj;
	}
	
	public function &_createObj(){
		$type=&$this->modelName;
		$obj=new $type();
		if($this->objData){
			$data=array();
			foreach($this->objData as $key=>$val) $data[$key]=$val;//copy
			$obj->_copyData($data);
		}
		if($this->joinData !== NULL) foreach($this->joinData as $alias=>&$joinData){
			$join=&$this->joins[$alias];
			if($join['fieldsInModel']){
				foreach($joinData as $key=>$val) $obj->$key=$val;//copy
			}else{
				$data=array();
				foreach($joinData as $key=>$val) $data[$key]=$val;//copy
				$type=&$join['modelName'];
				$joinObj=new $type();
				$joinObj->_copyData($data);
				$obj->$join['dataName']=$joinObj;
			}
		}
		return $obj;
	}
	
	public function &getModelFields(){
		$modelFields=array();$modelName=&$this->modelName;
		if($this->objFields===NULL) foreach($modelName::$__modelInfos['colsName'] as $field) $modelFields[$field]=&$modelName;
		elseif($this->objFields!==false) foreach($this->objFields as $field) $modelFields[$field]=&$modelName;
		foreach($this->allFields as $alias=>&$fields){
			$join=$this->joins[$alias];
			foreach($fields as $field) $modelFields[$field]=&$join['modelName'];
		}
		return $modelFields;
	}
	
	
	protected function _afterQuery_obj(&$obj){
		self::AfterQuery_obj($this->with,$obj);
	}
	
	
	public static function createWithQuery(&$obj,&$w){
		switch($w['reltype']){
			case 'belongsTo':
			case 'hasOne':
				$objField =& $w['foreignKey'];
				$resField =& $w['associationForeignKey'];
				
				return self::_createBelongsToAndHasOneQuery($w,$obj->_get($objField),$resField);
			case 'hasMany':
				$objField =& $w['foreignKey'];
				$resField =& $w['associationForeignKey'];
				
				return self::_createHasManyQuery($w,$obj->_get($objField),$resField);
			case 'hasOneThrough':
				$withMore=array(); reset($w['joins']);
				self::_recursiveThroughWith($withMore,$w['joins'],$w);
				$rel=$obj::$_relations[$w['relName']];
				
				$objField =& $rel['foreignKey'];
				$resField =& $rel['associationForeignKey'];
				
				return self::_createBelongsToAndHasOneQuery($w,$obj->_get($objField),$resField,false,$withMore['with'],$rel['alias']);
			case 'hasManyThrough':
				$withMore=array(); reset($w['joins']);
				self::_recursiveThroughWith($withMore,$w['joins'],$w);
				$rel=$obj::$_relations[$w['relName']];
				
				$objField =& $rel['foreignKey'];
				$resField =& $rel['associationForeignKey'];
				
				return self::_createHasManyQuery($w,$obj->_get($objField),$resField,false,$withMore['with'],$rel['alias']);
			default:
				throw new Exception('Unknown relation; '.$w['reltype']);
		}
	}
	
	private static function AfterQuery_obj(&$with,&$obj){
		foreach($with as $key=>&$w){
			$res=self::createWithQuery($obj,$w)->execute();
			$obj->_set($w['dataName'],$res);
			unset($with[$key]);
		}
	}

	private static function _recursiveThroughWith(&$with,&$joins,$w=array()/*,&$lastModelName*/){
		$relName=key($joins); $options=current($joins);
		if(is_int($relName)){ $relName=$options; $options=array(); }
		$options+=array('fields'=>false,'forceJoin'=>true);
		/* DEV *///if(!isset($lastModelName::$_relations[$relName])) throw new Exception($lastModelName.' does not have a relation named "'.$relName.'"'."\n".'Known relations : '.implode(', ',array_keys($lastModelName::$_relations))); /* /DEV */
		//$options+=$lastModelName::$_relations[$relName];
		if(isset($w['withOptions'][$relName])) $options=$w['withOptions'][$relName]+$options;// can override 'fields'
		$with['with']=array($relName=>$options);
		if(next($joins)===false) return;
		self::_recursiveThroughWith($with['with'][$relName],$joins/*,$lastModelName::$_relations[$relName]['modelName']*/);
	}
	
	protected function _afterQuery_objs(&$objs){
		self::AfterQuery_objs($this->with,$objs);
	}
	
	private static function AfterQuery_objs(&$with,&$objs){
		if(empty($objs)) return;
		foreach($with as $key=>&$w){
			switch($w['reltype']){
				case 'belongsTo':
				case 'hasOne':
					$objField =& $w['foreignKey'];
					$resField =& $w['associationForeignKey'];
					
					$values=self::_getValues($objs,$objField);
					if(!empty($values)){
						$listRes = self::_createBelongsToAndHasOneQuery($w,$values,$resField,true)->execute();
						
						if($listRes) foreach($objs as &$obj){
							foreach($listRes as &$res)
								if ($res->_get($resField) == $obj->_get($objField)){
									$obj->_set($w['dataName'],$res);
									break;
								}
						}
					}
					unset($with[$key]);
					break;
				case 'hasMany':
					$objField =& $w['foreignKey'];
					
					$values=self::_getValues($objs,$objField);
					if(!empty($values)){
						$resField =& $w['associationForeignKey'];
						
						$oneField=count($w['fields'])===1?$w['fields'][0]:false;
						$listRes=self::_createHasManyQuery($w,$values,$resField,true)->execute();
						if($listRes) foreach($objs as $key=>&$obj){
							$listObjsRes=array();
							foreach($listRes as &$res){
								if($res->_get($resField) == $obj->_get($objField)){
									if($oneField===false)
										$listObjsRes[] =& $res;
									else
										$listObjsRes[]=$res->_get($oneField);
								}
							}
							$obj->_set($w['dataName'],$listObjsRes);
						}
					}
					break;
					
				case 'hasManyThrough':
					reset($objs);
					$obj=current($objs);
					$rel=$obj::$_relations[$w['relName']];
					
					$objField =& $rel['foreignKey'];
					
					$values=self::_getValues($objs,$objField);
					if(!empty($values)){
						$withMore=array(); reset($w['joins']);
						self::_recursiveThroughWith($withMore,$w['joins'],$obj::$__className);
						
						$resField =& $rel['associationForeignKey'];
						$oneField=count($w['fields'])===1?$w['fields'][0]:false;
						
						/* DEV */if(empty($w['fields'])) throw new Exception('You must specify fields...');/* /DEV */
						$w['fields']['('.$rel['alias'].'.`'.$resField.'`)']=$resField;
						
						if(isset($w['groupBy'])) $w['groupBy']=$rel['alias'].'.'.$resField.','.$w['groupBy'];
						
						$listRes=self::_createHasManyQuery($w,$values,$resField,false,$withMore['with'],$rel['alias'])->execute();
						if($listRes!==false){
							foreach($objs as $key=>&$obj){
								$listObjsRes=array();
								foreach($listRes as &$res){
									if($res->_get($resField) == $obj->_get($objField)){
										if($oneField===false) $listObjsRes[] =& $res;
										else $listObjsRes[]=$res->_get($oneField);
									}
								}
								$obj->_set($w['dataName'],$listObjsRes);
							}
							unset($obj);
						}
					}
					
					break;
					
				default:
					throw new Exception('Unknown relation; '.$w['reltype']);
			}
		}
	}

	public static function findWith(&$model,&$key,&$options){
		$with=array();
		self::_addWith($with,$key,$options,$model::$__className);
		self::AfterQuery_obj($with,$model);
	}
	
	public static function &findWithPaginate($paginateClass,&$model,&$key,&$options){
		$w=array();
		self::_addWith($w,$key,$options,$model::$__className);
		$res=$paginateClass::create(self::createWithQuery($model,$w[$key]));
		$model->_setRef($w[$key]['dataName'],$res); //not executed, but should be a reference to the variable
		unset($w[$key]);
		return $res;
	}

	public static function findMWith(&$model,&$mwith){
		$with=array();$modelName=&$model::$__className;
		foreach($mwith as $key=>&$options){
			if(is_numeric($key)){ $key=$options; $options=array();}
			self::_addWith($with,$key,$options,$modelName);
		}
		self::AfterQuery_obj($with,$model);
	}
	
	private static function _getValues(&$objs,&$objField){
		$values=array();
		foreach($objs as &$obj){
			$value=$obj->_get($objField);
			if($value !== NULL) $values[]=$value;
		}
		return array_unique($values);
	}
	
	private static function &_createBelongsToAndHasOneQuery(&$w,$values,&$resField,$addResField=false,&$moreWith=NULL,&$fieldTableAlias=NULL){
		$query=new QFindOne($w['modelName']);
		$query->setFields($addResField ? self::_addFieldIfNecessary($w['fields'],$resField) : $w['fields']);
		if(isset($w['where'])) $where=&$w['where']; else $where=array();
		if($fieldTableAlias !== NULL) $resField=$fieldTableAlias.'.'.$resField;
		$where[$resField]=&$values;
		$query->where($where);
		if(isset($w['with'])) $query->_setWith($w['with']);
		if($moreWith!==NULL) $query->setAllWith($moreWith);
		return $query;
	}
	
	private static function &_createHasManyQuery(&$w,$values,$resField,$addResField=false,&$moreWith=NULL,&$fieldTableAlias=NULL){
		if($addResField===false && count($w['fields'])===1 && !isset($w['with'])) $query=new QFindValues($w['modelName']);
		else $query = new QFindAll($w['modelName']);
		$query->setFields($addResField ? self::_addFieldIfNecessary($w['fields'],$resField) : $w['fields']);
		if(isset($w['where'])) $where=&$w['where']; else $where=array();
		if($fieldTableAlias !== NULL) $resField=$fieldTableAlias.'.'.$resField;
		$where[$resField]=$values;
		$query->where($where);
		if(isset($w['orderBy'])) $query->orderBy($w['orderBy']);
		if(isset($w['groupBy'])) $query->groupBy($w['groupBy']);
		if(isset($w['with'])) $query->_setWith($w['with']);
		if(isset($w['limit'])) $query->limit($w['limit']);
		if($moreWith!==NULL) $query->setAllWith($moreWith);
		if(isset($w['groupResBy'])) $query->groupResBy($w['groupResBy']);
		return $query;
	}
	
	private static function &_addFieldIfNecessary(&$fields,$field){
		if(empty($fields) || in_array($field,$fields)) return $fields;
		$fields[]=&$field;
		return $fields;
	}
}