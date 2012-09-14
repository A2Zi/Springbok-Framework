<?php
class QTable extends QFindAll{
	protected $pagination,
		$allowFilters=false,$FILTERS,
		$allowOrder=true,$defaultOrder,
		$autoRelations=true,$belongsToFields=array(),
		$exportable=false
		;
	
	public function allowFilters(){$this->allowFilters=true; return $this; }
	public function allowAdvancedFilters(){$this->allowFilters='advanced'; return $this; }
	public function disallowOrder(){$this->allowOrder=false; return $this; }
	public function noAutoRelations(){$this->autoRelations=false; return $this;}
	public function autoRelations($params=array()){$this->autoRelations=$params; return $this; }
	public function belongsToFields($params){$this->belongsToFields=$params; return $this; }
	public function exportable($types,$fileName,$title=null){$this->exportable=array($types,$fileName,$title); return $this;}
	public function defaultOrder($defaultOrder){$this->defaultOrder=$defaultOrder; return $this; }
	
	public function isFiltersAllowed(){ return $this->allowFilters!==false; }
	public function isFilterAdvancable(){ return $this->allowFilters==='advanced'; }
	public function isOrderAllowed(){ return $this->allowOrder; }
	public function getPagination(){ return $this->pagination; }
	public function getFilters(){ return $this->FILTERS; }
	public function isExportable(){ return $this->exportable!==false; }
	public function getExportableTypes(){ return explode(',',$this->exportable[0]); }
	public function getBelongsToFields(){ return $this->belongsToFields; }
	
	private $_fieldsForTable;
	public function getFieldsForTable(){ return $this->_fieldsForTable; }
	
	protected function process(){
		$modelName=$this->modelName;
		$fields=$this->getFields();
		if($fields===null) $fields=$modelName::$__modelInfos['colsName'];
		$this->_fieldsForTable=$fields;
		
		$belongsToFields=$this->belongsToFields; $belongsToRel=array();
		if($this->autoRelations!==false){
			if($belongsToFields!==false && empty($this->belongsToFields)){
				$modelRelations=$modelName::$__modelInfos['relations'];
				$parentRelModelName=isset($modelRelations['Parent'])?$modelRelations['Parent']['modelName']:false;
				foreach($modelRelations as $relKey=>&$rel){
					if($rel['reltype']==='belongsTo' && $rel['modelName']!==$parentRelModelName && in_array($rel['foreignKey'],$fields) 
							&& empty($modelName::$__PROP_DEF[$rel['foreignKey']]['annotations']['Enum'])) $belongsToFields[$rel['foreignKey']]=$relKey;}
			}
			foreach($belongsToFields as $field=>$relKey){
				$belongsToRel[$field]=$modelName::$_relations[$relKey];
				$relModelName=$belongsToRel[$field]['modelName'];
				if($relModelName::$__cacheable) $belongsToFields[$field]=$relModelName::findCachedListName();
				elseif(is_array($this->autoRelations) && isset($this->autoRelations[$field]))
					$belongsToFields[$field]=$relModelName::QList()->setFields(array('id',$relModelName::$__displayField))->with($modelName,array('fields'=>false,'type'=>QFind::INNER,'join'=>true));
				else $this->with($relKey,array('fields'=>array($relModelName::$__displayField=>$field),'fieldsInModel'=>true));
			}
		}
		
		$relationsMap=array();
		foreach($this->joins as $alias=>$join){
			if(!empty($join['fields'])) foreach($join['fields'] as $keyField=>$field) $relationsMap[$field]=$alias;
		}
		
		$SESSION_SUFFIX=$this->modelName.CRoute::getAll();
		if($this->isOrderAllowed()){
			if(isset($_GET['orderBy']) && in_array($_GET['orderBy'],$fields)){
				CSession::set('CTableOrderBy'.$SESSION_SUFFIX,$orderByField=$_GET['orderBy']);
				CSession::set('CTableOrderByWay'.$SESSION_SUFFIX,isset($_GET['orderByDesc'])?'DESC':'ASC');
			}else $orderByField=CSession::getOr('CTableOrderBy'.$SESSION_SUFFIX);
			
			if($orderByField !==null){
				if(isset($relationsMap[$orderByField])) $orderByField=$relationsMap[$orderByField].'.'.$orderByField;
				$this->orderBy(array($orderByField=>CSession::get('CTableOrderByWay'.$SESSION_SUFFIX)));
				if($this->defaultOrder!==null && isset($this->defaultOrder[1])) $this->addOrder($this->defaultOrder[1]);
			}
			if($this->defaultOrder!==null){
				foreach($this->defaultOrder as $keyOrder=>$valueOrder){
					if(is_int($keyOrder)){
						if($valueOrder!==$orderByField) $this->addOrder($valueOrder);
					}else{
						if($keyOrder!==$orderByField) $this->addOrderDesc($keyOrder);
					}
				}
			}
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
				$filterFields=empty($relationsMap)?$fields:array_merge($fields,array_keys($relationsMap));
				foreach($filterFields AS $key=>$fieldName){
					if(isset($this->FILTERS[$fieldName]) && (!empty($this->FILTERS[$fieldName]) || $this->FILTERS[$fieldName]==='0')){
						$filter=true;
						
						$postValue=$this->FILTERS[$fieldName];
						if(isset($relationsMap[$fieldName])){
							$condK=($alias=$relationsMap[$fieldName]).'.'.$fieldName;
							$relModelName=$this->joins[$alias]['modelName'];
							$propDef=$relModelName::$__PROP_DEF[$fieldName];
							$type=$propDef['type'];
						}else{
							$condK=$fieldName;
							
							$propDef=$modelName::$__PROP_DEF[$fieldName];
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
			$this->_export($_GET['export'],$this->exportable[1],$this->exportable[2])->displayIfExport();
		}
	}

	private function _export($type,$fileName,$title){
		ob_clean();
		if(empty($fileName)) $fileName=$this->getModelName();
		$table=new CModelTableExport($this);
		return $table->init($type,$fileName,$title);
	}

	public function export($type,$fileName=null,$title=null){
		$this->process();
		return $this->_export($type,$fileName,$title);
	}
	
	public function pagination(){
		$this->process();
		
		$this->pagination=parent::paginate()->pageSize(25);
		$table=new CModelTable($this);
		$this->pagination->setReturn($table);
		return $this->pagination;
	}
	
	public function paginate(){
		return $this->pagination()->execute();
	}
}