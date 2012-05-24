<?php
class QTable extends QFindAll{
	protected $pagination,
		$allowFilters=false,$FILTERS,
		$autoRelations=true,$belongsToFields=array(),
		$exportable=false
		;
	
	public function &allowFilters(){$this->allowFilters=true; return $this;}
	public function &noAutoRelations(){$this->autoRelations=false; return $this;}
	public function &exportable($types,$fileName,$title=null){$this->exportable=array(&$types,&$fileName,&$title); return $this;}
	
	public function &isFiltersAllowed(){ return $this->allowFilters; }
	public function &getPagination(){ return $this->pagination; }
	public function &getFilters(){ return $this->FILTERS; }
	public function isExportable(){ return $this->exportable!==false; }
	public function getExportableTypes(){ return explode(',',$this->exportable[0]); }
	
	private $_fieldsForTable;
	public function getFieldsForTable(){ return $this->_fieldsForTable; }
	
	private function process(){
		$modelName=&$this->modelName;
		$fields=$this->getFields();
		if($fields===null) $fields=$modelName::$__modelInfos['colsName'];
		$this->_fieldsForTable=&$fields;
		
		$belongsToFields=&$this->belongsToFields; $belongsToRel=array();
		if($this->autoRelations!==false){
			if($belongsToFields!==false && empty($this->belongsToFields)){
				foreach($modelName::$__modelInfos['relations'] as $relKey=>&$rel)
					if($rel['reltype']==='belongsTo' && in_array($rel['foreignKey'],$fields)) $belongsToFields[$rel['foreignKey']]=$relKey;
			}
			foreach($belongsToFields as $field=>$relKey){
				$belongsToRel[$field]=$modelName::$_relations[$relKey];
				$relModelName=$belongsToRel[$field]['modelName'];
				if($relModelName::$__cacheable) $belongsToFields[$field]=$relModelName::findCachedListName();
				elseif(is_array($this->autoRelations) && isset($this->autoRelations[$field]))
					$belongsToFields[$field]=$relModelName::QList()->setFields(array('id',$relModelName::$__displayField))->with($modelName,array('fields'=>false,'type'=>QFind::INNER,'forceJoin'=>true));
				else $this->with($relKey,array('fields'=>array($relModelName::$__displayField=>$field),'fieldsInModel'=>true));
			}
		}
		
		$SESSION_SUFFIX=$this->modelName.CRoute::getAll();
		if(isset($_GET['orderBy']) && in_array($_GET['orderBy'],$fields)){
			CSession::set('CTableOrderBy'.$SESSION_SUFFIX,$orderByField=$_GET['orderBy']);
			CSession::set('CTableOrderByWay'.$SESSION_SUFFIX,isset($_GET['orderByDesc'])?'DESC':'ASC');
		}else $orderByField=CSession::getOr('CTableOrderBy'.$SESSION_SUFFIX);
		
		if($orderByField !==null){
			if(isset($belongsToFields[$orderByField])){
				$rel=$belongsToRel[$orderByField];
				$relModelName=$rel['modelName'];
				$orderByField=$rel['alias'].'.'.$relModelName::$__displayField;
			}
			$this->orderBy(array($orderByField=>CSession::get('CTableOrderByWay'.$SESSION_SUFFIX)));
		}
		
		
		if($this->isFiltersAllowed()){
			$filter=false;
			if(!empty($_POST['filters'])){
				$this->FILTERS=$_POST['filters'];
				CSession::set('CTableFilters'.$SESSION_SUFFIX,$this->FILTERS);
			}elseif(!empty($_GET['filters'])){
				$this->FILTERS=$_GET['filters'];
				CSession::set('CTableFilters'.$SESSION_SUFFIX,$this->FILTERS);
			}else
				$this->FILTERS=CSession::getOr('CTableFilters'.$this->modelName.CRoute::getAll(),array());
			
			if(!empty($this->FILTERS)){
				foreach($fields AS $key=>$fieldName){
					if(isset($this->FILTERS[$fieldName]) && (!empty($this->FILTERS[$fieldName]) || $this->FILTERS[$fieldName]==='0')){
						$filter=true;
						
						$postValue=$this->FILTERS[$fieldName];
						if(isset($belongsToFields[$fieldName])){
							$rel=$belongsToRel[$fieldName];
							$relModelName=$rel['modelName'];
							$relFieldName=$relModelName::$__displayField;
							$condK=$rel['alias'].'.'.$relFieldName;
							
							$propDef=&$relModelName::$__PROP_DEF[$relFieldName];
							$type=$propDef['type'];
						}else{
							$condK=$fieldName;
							
							$propDef=&$modelName::$__PROP_DEF[$fieldName];
							$type=$propDef['type'];
						}
						$condV=CBinder::bind($type,$postValue);
						
						if(is_int($condV) || is_float($condV)){
							
						}elseif(is_string($condV)){
							if(!isset($this->fields[$fieldName]['filter']) || $this->fields[$fieldName]['filter'] === 'like')
								$condK.=' LIKE';
						}elseif(is_array($condV)){
							notImplemented();
						}
						
						if(is_int($key)) $this->addCondition($condK,$condV);
						else $this->addHavingCondition($condK,$condV);
						
						/*if (is_array($value)){
								if($type=='rangeint' || $type=='rangedecimal'){
									$values=array();
									if(isset($value[0]) && $value[0] !=='') $values[]= $type=='rangeint' ? (int)$value[0] : (float)$value[0];
									if(isset($value[1]) && $value[1] !=='') $values[]= $type=='rangeint' ? (int)$value[1] : (float)$value[1];
									if(!empty($values)){
										$sqlFilter .= ' AND ';
										if(count($values)==1)
											$sqlFilter .= (($key == $this->identifier OR $key == '`'.$this->identifier.'`') ? 'a.' : '').pSQL($key).' = '.current($values).' ';
										else
											$sqlFilter .= (($key == $this->identifier OR $key == '`'.$this->identifier.'`') ? 'a.' : '').pSQL($key).' BETWEEN '.$values[0].' AND '.$values[1].' ';
									}
								}else{ //datetime or date
									if (!empty($value[0])){
										if (!Validate::isDate($value[0])) $this->_errors[] = Tools::displayError('\'from:\' date format is invalid (YYYY-MM-DD)');
										else $sqlFilter .= ' AND '.pSQL($key).' >= \''.pSQL(Tools::dateFrom($value[0])).'\'';
									}
									if (!empty($value[1])){
										if (!Validate::isDate($value[1])) $this->_errors[] = Tools::displayError('\'to:\' date format is invalid (YYYY-MM-DD)');
										else $sqlFilter .= ' AND '.pSQL($key).' <= \''.pSQL(Tools::dateTo($value[1])).'\'';
									}
								}
							}else{
								$sqlFilter .= ' AND ';
								if ($type == 'int' OR $type == 'bool')
									$sqlFilter .= (($key == $this->identifier OR $key == '`'.$this->identifier.'`') ? 'a.' : '').pSQL($key).' = '.intval($value).' ';
								elseif ($type == 'decimal')
									$sqlFilter .= (($key == $this->identifier OR $key == '`'.$this->identifier.'`') ? 'a.' : '').pSQL($key).' = '.floatval($value).' ';
								elseif ($type == 'select')
									$sqlFilter .= (($key == $this->identifier OR $key == '`'.$this->identifier.'`') ? 'a.' : '').pSQL($key).' = '.pSQL($value).' ';
								else
									$sqlFilter .= (($key == $this->identifier OR $key == '`'.$this->identifier.'`') ? 'a.' : '').pSQL($key).' LIKE \'%'.pSQL($value).'%\' ';
							}*/
					}
				}
			}
			if($filter) $this->calcFoundRows();
		}else{
			if($this->autoRelations!==false) foreach($belongsToFields as $field=>$relKey){
				$relModelName=$modelName::$_relations[$relKey]['modelName'];
				$this->with($relKey,array('fields'=>array($relModelName::$__displayField=>$field),'fieldsInModel'=>true));
			}
		}
		
		if($this->exportable!==false && isset($_GET['export']) ? true : false){
			$this->export($_GET['export'],$this->exportable[1],$this->exportable[2]);
		}
	}

	public function export($type,$fileName=null,$title=null,$exportPath=null,$transformerClass=null){
		ob_clean();
		if(empty($fileName)) $fileName=$this->getModelName();
		$table=new CModelTable($this);
		$table->export($type,$fileName,$title,$exportPath,$transformerClass);
		if($exportPath!==null) return; else exit;
	}
	
	public function &pagination(){
		$this->process();
		
		$this->pagination=CPagination::create($this)->pageSize(25);
		$table=new CModelTable($this);
		$this->pagination->setReturn($table);
		return $this->pagination;
	}
	
	public function paginate(){
		return $this->pagination()->execute();
	}
}