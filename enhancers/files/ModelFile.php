<?php
class ModelFile extends PhpFile{
	public static $CACHE_PATH=false;
	public $_className,$_contentInfos,$_classAnnotations;
	
	const REGEXP_FIELDS='/public\s+((?:\/\*\*[^;{]*\*\/\s+\$[A-Za-z0-9\s_]+\s*(?:,\s*)?)+\s*;)/Ums';
	const REGEXP_CLASS='/(?:\/\*\*([^{]*)\*\/\s+)?class ([A-Za-z_0-9]+)([^{]*){/s';
	const REGEXP_CONSTS='/const\s+[^;]+\s*;/i';
	
	
	public static function _getPath($m,&$controllersSrc,$enhanced){
		eval('$eval=array('.$m[1].');');
		if(!isset($eval))
			throw new Exception('Error eval : '.$m[1]);
		$countEval=count($eval);
		if($countEval===2 && ($eval[0]==='core')||($eval[0]==='springbok')){
			array_shift($eval);
			$modelPath=CORE.'models/'.$eval[0].'.php';
			if(!isset($controllersSrc[$countEval.$modelPath]))
				$controllersSrc[$countEval.$modelPath]=file_get_contents($modelPath);
		}else{
			$parentPath=$countEval===2 ? $enhanced->pluginPathFromKey(array_shift($eval)) : $enhanced->getAppDir().'src/';
			$modelPath='models/'.($eval[0]).'.php';
			if(!isset($controllersSrc[$countEval.$modelPath]))
				$controllersSrc[$countEval.$modelPath]=file_get_contents($parentPath.$modelPath);
		}
		return $controllersSrc[$countEval.$modelPath];
	}
	
	protected function loadContent($srcContent){//TODO mettre en commun le code avec ControllerFile dans PhpFile.
		$controllersSrc=array(); $enhanced=$this->enhanced;
		$srcContent=preg_replace_callback('/\/\*\s+@ImportFields\(([^*]+)\)\s+\*\//',function($m) use($enhanced,&$controllersSrc){
			$path=ModelFile::_getPath($m, $controllersSrc, $enhanced);
			if(!preg_match(ModelFile::REGEXP_FIELDS,$path,$mFields))
				throw new Exception('Import fields : unable to find '.$modelPath);
			return $mFields[0];
		},$srcContent);
		
		$srcContent=preg_replace_callback('/\/\*\s+@ImportConsts\(([^*]+)\)\s+\*\//',function($m) use($enhanced,&$controllersSrc){
			$path=ModelFile::_getPath($m, $controllersSrc, $enhanced);
			if(!preg_match_all(ModelFile::REGEXP_CONSTS,$path,$mConsts))
				throw new Exception('Import consts : unable to find '.$modelPath);
			return implode("\n",$mConsts[0]);
		},$srcContent);
		$this->_srcContent=$srcContent;
	}
	
	
	public function enhancePhpContent($content,$false=false){
		$matches=array();
		//preg_match('/class ([A-Za-z_0-9]+)(?:[^{]*){/',$content,$matches);
		//debug($matches);
		
		if(preg_match('/\*\*([^{]*)\*\/\s+class ([A-Za-z_0-9]+)(?:[^{]*){/',$content,$matches) && !empty($matches[2])
						 && (($isSQL=preg_match('/@TableAlias\(/',$matches[1])) || ($isDb=preg_match('/@Db\(/',$matches[1]))) ){
			
			// SQL MODEL
			//$content=parent::enhancePhp($content,false);
			
			$modelFile=$this;
			if($isSQL){
				$content=preg_replace_callback('/\/\*\*([^;{]*)\*\/\s+public\s+\$([A-Za-z0-9\s_]+);/Ums',array($this,'fields'),$content);
				$content=preg_replace_callback(self::REGEXP_FIELDS,array($this,'mfields'),$content);
	
				$contentInfos=array('primaryKeys'=>array(),'columns'=>array(),'isAI'=>false,'indexes'=>array(),'relations'=>array(),'generate'=>'default');
				$enhanceConfig=&$this->enhanced->config;
				$content=preg_replace_callback(self::REGEXP_CLASS,function($matches) use(&$modelFile,&$content,&$contentInfos,&$enhanceConfig){
					$annotations=empty($matches[1])?array():PhpFile::parseAnnotations($matches[1],true);
					$modelFile->_className=$matches[2];
					$classBeforeContent='';
					
					if(!isset($annotations['TableName'])) $annotations['TableName'][0]=array(UInflector::pluralizeUnderscoredWords(UInflector::underscore(substr($modelFile->_className,0,2)===strtoupper(substr($matches[2],0,2))?substr($matches[2],isset($annotations['Db'])?2:1):$matches[2])));
					if(!isset($annotations['TableAlias'])) throw new Exception('Table Alias is missing for : '.$modelFile->_className);
					$dbName=isset($annotations['Db'])?$annotations['Db'][0][0]:false;
					if(isset($annotations['Generate'])) $contentInfos['generate']=$annotations['Generate'][0][0];
					if(isset($annotations['Engine'])) $contentInfos['Engine']=$annotations['Engine'][0][0];
					$createdField=isset($annotations['CreatedField'])?$annotations['CreatedField'][0][0]:false;
					$updatedField=isset($annotations['UpdatedField'])?$annotations['UpdatedField'][0][0]:false;
					$createdByField=isset($annotations['CreatedByField'])?$annotations['CreatedByField'][0][0]:false;
					$orderByField=isset($annotations['OrderByField'])?$annotations['OrderByField'][0][0]:false;
					$cacheable=isset($annotations['Cacheable'])?$annotations['Cacheable'][0][0]:false;
					
					if(isset($annotations['Comment'])) $contentInfos['comment']=str_replace('\\\'',"'",$annotations['Comment'][0][0]);
					
					
					$indexes=&$contentInfos['indexes'];
					if(isset($annotations['Index'])){
						foreach($annotations['Index'] as $index) $indexes[0][]=$index;
					}
					if(isset($annotations['IndexUnique'])){
						foreach($annotations['IndexUnique'] as $index) $indexes[1][]=$index;
					}
					
					if(isset($annotations['Created'])){
						if(isset($modelFile->_fields['created'])) throw new Exception($modelFile->_className.' already contains a field "created"');
						$modelFile->_fields['created']=array('SqlType'=>array('datetime'),'NotNull'=>false,'NotBindable'=>false,'Index'=>false);
					}
					if(isset($annotations['CreatedBy'])){
						if(isset($modelFile->_fields['created_by'])) throw new Exception($modelFile->_className.' already contains a field "created_by"');
						$modelFile->_fields[$createdByField='created_by']=array('SqlType'=>array('int(10) unsigned'),'Null'=>false,'NotBindable'=>false,'Index'=>false);
					}
					if(isset($annotations['Updated'])){
						if(isset($modelFile->_fields['updated'])) throw new Exception($modelFile->_className.' already contains a field "updated"');
						$modelFile->_fields['updated']=array('SqlType'=>array('datetime'),'Null'=>false,'NotBindable'=>false,'Default'=>array(NULL),'Index'=>false);
					}
					
					
					if(isset($annotations['Parent'])){
						if(isset($modelFile->_fields['_type'])) throw new Exception($modelFile->_className.' already contains a field "_type"');
						$children=$enhanceConfig['modelParents'][$modelFile->_className];
						$modelFile->_fields['_type']=array('SqlType'=>array('tinyint(1) unsigned'),'NotNull'=>false, 'NotBindable'=>false, 'Index'=>false, 'Enum'=>array($children) );
						$_typeRelations=array(); foreach($children as $child) $_typeRelations[$child]=array('foreignKey'=>'p_id');
						$contentInfos['relations']['_type']=array('reltype'=>'belongsToType', 'dataName'=>'child','types'=>$children,'relations'=>$_typeRelations );
					}
					if(isset($annotations['Child'])){
						$idField=isset($modelFile->_fields['id']) ? 'p_id' : 'id';
						$fieldToInsert=array( 'SqlType'=>array(isset($annotations['ParentBigintId'])?'bigint(20) unsigned':'int(10) unsigned'), 'NotNull'=>false, 'NotBindable'=>false,
											'ForeignKey'=>array($annotations['Child'][0][0],'id','onDelete'=>'CASCADE'));
						$modelFile->_fields=array($idField=>$fieldToInsert)+$modelFile->_fields;
						$idField==='id' ? $modelFile->_fields[$idField]['Pk']=false : $modelFile->_fields[$idField]['Unique']=false;
						$contentInfos['relations']['Parent']=array('reltype'=>'belongsTo','modelName'=>$annotations['Child'][0][0],'foreignKey'=>$idField,
										'fieldsInModel'=>$annotations['TableAlias'][0][0],'fields'=>isset($annotations['Child'][0][1]) ? $annotations['Child'][0][1] : null);
						$classBeforeContent.='public function insert(){ $this->data["'.$idField.'"]=$this->insertParent(); $res=parent::insert(); return $res ? $this->data["'.$idField.'"] : $res; }';
						$classBeforeContent.='public function insertIgnore(){ $idParent=$this->insertIgnoreParent(); if($idParent){ $this->data["'.$idField.'"]=$idParent; return parent::insertIgnore();} }';
						$typesParent=$enhanceConfig['modelParents'][$annotations['Child'][0][0]];
						$typeForParent=array_search($modelFile->_className,$typesParent);
						if($typeForParent===false) throw new Exception("Type parent not found: ".print_r($typesParent,true).' ('.$modelFile->_className.')');
						
						$classBeforeContent.='public function insertParent(){ $parent=new '.$annotations['Child'][0][0].';'
													.'$data=$this->data;'.($idField==='id' ? '' : 'unset($data["id"]);').' $data[\'_type\']='.$typeForParent.'; $parent->_copyData($data);'
													.' return $parent->'.($annotations['Child'][0][0]==='SearchablesKeyword'?'findIdOrInsert(\'id\')':'insert()').'; }';
						$classBeforeContent.='public function insertIgnoreParent(){ $parent=new '.$annotations['Child'][0][0].';'
													.'$data=$this->data;'.($idField==='id' ? '' : 'unset($data["id"]);').' $data[\'_type\']='.$typeForParent.'; $parent->_copyData($data);'
													.' return $parent->insertIgnore(); }';
						$classBeforeContent.='public function updateParent(){ $parent=new '.$annotations['Child'][0][0].';'
													.'$data=$this->data;'.($idField==='id' ? '' : '$data["id"]=$data["p_id"]; unset($data["p_id"]);').' $data[\'_type\']='.$typeForParent.'; $parent->_copyData($data);'
													.' return call_user_func_array(array($parent,"update"),func_get_args()); }';
						if(strpos($content,'function QListName(')===false)
							$classBeforeContent.='public static function QListName(){ return $query=parent::QList()->setFields(array("id"))->withParent("name"); }';
						if($idField==='p_id') $classBeforeContent.='public static function getParentId($childId){ return self::QValue()->field("p_id")->byId($childId); }';
						
					}
					
					if(isset($annotations['Seo'])){
						$annotations['Slug']=true;
						$modelFile->_fields['meta_title']=array( 'SqlType'=>array('varchar(100)'), 'Null'=>false);
						$modelFile->_fields['meta_descr']=array( 'SqlType'=>array('varchar(200)'), 'Null'=>false, 'Text'=>false);
						$modelFile->_fields['meta_keywords']=array( 'SqlType'=>array('text'), 'Null'=>false, 'MaxLength'=>array(1000));
					}
	
					if(isset($annotations['Slug']) && !isset($modelFile->_fields['slug']))
						$modelFile->_fields['slug']=array( 'SqlType'=>array($modelFile->_fields[isset($annotations['DisplayField'][0][0])?$annotations['DisplayField'][0][0]:'name']['SqlType'][0]), 'NotNull'=>false, 'MinLenth'=>array(3));
					
					if(isset($annotations['LogChanges'])){
						$classBeforeContent.='protected function afterUpdateCompare($data,$primaryKeys){ModelLogChanges::logUpdate($primaryKeys,$data);parent::afterUpdateCompare($data,$primaryKeys);}'
										.'protected function afterInsert($data=null){ModelLogChanges::logInsert($data);}';
					}
					
					
					$pkAutoGenerated=false;$enums=$specialFields=array();
					foreach($modelFile->_fields as $name=>$field)
						if(isset($field['Pk'])){
							$contentInfos['primaryKeys'][]=$name;
							if(isset($field['Pk'][0])){
								if($pkAutoGenerated=$field['Pk'][0])
									$classBeforeContent.='protected function _beforeInsert(){$this->'.$contentInfos['primaryKeys'][0].'='.($pkAutoGenerated=='UUID'?'UGenerator::uuid()':'')
													.';return parent::_beforeInsert();}';
							}
						
						}
					foreach($modelFile->_fields as $name=>&$field){
						$column=array();
						if(isset($field['Format'])) $field['Format']=$field['Format'][0];
						if(isset($field['Boolean'])){
							$column['type']='char(0)';
							$column['default']=(isset($field['Default']) && $field['Default'][0]?'""':null);
							$column['notnull']=false;
							$specialFields[$name]='Boolean';
						}elseif(isset($field['BooleanInt'])){
							$column['type']='tinyint(1) unsigned';
							$column['default']=isset($field['Default'])?$field['Default'][0]:false;
							$column['notnull']=isset($field['Null'])?false:true;
							$specialFields[$name]='BooleanInt';
						}else{
							if(isset($field['Datetime'])){
								$column['type']='int(11)';
								unset($field['Datetime']);
								$field['Format']=$field['var']='datetime';
							}elseif(isset($field['Price'])){
								$column['type']='decimal('.$field['Price'][0].','.$field['Price'][1].')';
								unset($field['Price']);
								$field['Format']='price';
							}elseif(isset($field['SqlType'])) $column['type']=str_replace('"',"'",$field['SqlType'][0]);
							$column['default']=(isset($field['DefaultValue'])?$field['DefaultValue'][0]:(isset($field['Default'])?$field['Default'][0]:false));
							$column['notnull']=isset($field['Null'])?false:true;
						}
						$column['unique']=isset($field['Unique'])?true:false;
						$column['index']=isset($field['Index'])?true:false;
						$column['comment']=isset($field['Comment'])?str_replace('\\\'',"'",$field['Comment'][0]):false;
						if(isset($field['AutoIncrement'])){ $field['NotBindable']=0; $column['autoincrement']=true; $contentInfos['isAI']=true; }
						else $column['autoincrement']=false;
						if(isset($field['CreatedField']) || (!$createdField && isset($column['type']) && in_array($column['type'],array('DATE','DATETIME','date','datetime'))
									&& in_array($name,array('created','cdate','date_add')))){
							$field['NotBindable']=0;
							if($column['type']==='date'|| $column['type']==='DATE') $field['Format']='date_';
							elseif($column['type']==='datetime'||$column['type']==='DATETIME') $field['Format']='datetime_';
							elseif($column['type']==='int(10)'||$column['type']==='int(11)') $field['Format']='datetime';
							$createdField=$name;
						}
						if(isset($field['UpdatedField']) || (!$updatedField && isset($column['type']) && in_array($column['type'],array('DATE','DATETIME','date','datetime')) 
									&& in_array($name,array('updated','modified','udate','mdate','date_modified','date_updated','date_upd')))){
							$field['NotBindable']=0;
							if($column['type']==='date'|| $column['type']==='DATE') $field['Format']='date_';
							elseif($column['type']==='datetime'||$column['type']==='DATETIME') $field['Format']='datetime_';
							elseif($column['type']==='int(10)'||$column['type']==='int(11)') $field['Format']='datetime';
							$updatedField=$name;
						}
						if(isset($field['CreatedByField'])){
							$field['NotBindable']=0;
							$createdByField=$name;
						}
						if(isset($field['OrderByField']) || (!$orderByField && $name==='position' 
							&& ((substr($column['type'],0,4)==='int(') || substr($column['type'],0,8)==='tinyint(')) ){
							$orderByField=$name;
						}
						if(isset($field['ForeignKey'])) $column['ForeignKey']=$field['ForeignKey'];
						$contentInfos['columns'][$name]=$column;
						
						if(isset($field['Index'])) $indexes[0][]=array($name);
						if(isset($field['Unique'])) $indexes[1][]=array($name);
						if(isset($field['Enum'])){ $enums[$name]=$field['Enum']; $field['Enum']=UInflector::pluralizeUnderscoredWords($name); }
						if(isset($field['Icons'])){ if(count($field['Icons'])===1&&isset($field['Icons'][0])&&is_array($field['Icons'][0])) $field['Icons']=$field['Icons'][0]; }
						if(isset($field['Json'])){ $specialFields[$name]='Json';}
						
						unset($field['Pk'],$field['Boolean'],$field['SqlType'],$field['Null'],$field['NotNull'],$field['DefaultValue'],$field['Default'],$field['AutoIncrement'],
									$field['CreatedField'],$field['UpdatedField'],$field['CreatedByField'],$field['PositionField'],
									$field['ForeignKey'],$field['Index'],$field['Comment']);
						if(!empty($field)) $contentInfos['annotations'][$name]=$field;
					}
					
					if(empty($enums)) $enums='';
					else{
						$res='';
						foreach($enums as $fieldName=>$array){
							if(count($array)===1 && is_array($array[0])) $array=$array[0];
							$res.='public static function '.UInflector::pluralizeUnderscoredWords($fieldName).'List(){return array(';
							foreach($array as $key=>$value)
								$res.=UPhp::exportCode($key).'=>_tF('.UPhp::exportCode($matches[2]).','.UPhp::exportCode($fieldName.'.Enum.'.$value).','.UPhp::exportCode($value).'),';
							$res=(empty($array)?$res:substr($res,0,-1)).');}';
							$res.='public function '.$fieldName.'(){$v=$this->'.$fieldName.';';
							foreach($array as $key=>$value) $res.='if($v==='.UPhp::exportCode($key).')return _tF('.UPhp::exportCode($matches[2]).','.UPhp::exportCode($fieldName.'.Enum.'.$value).','.UPhp::exportCode($value).');';
							$res.='return \'\';}';
							/*foreach($array as $key=>$value){
								$res.='public function is'.ucfirst($fieldName).'{return $this->'.$fieldName.'==='.$key.'}';
							}*/
						}
						$classBeforeContent.=$res;
					}
					
					
					$specialFieldsSetData=$specialFieldsGetData=$specialFieldsBefore='';
					foreach($specialFields as $name=>$type){
						if($type==='Boolean'||$type==='BooleanInt'){
							$specialFieldsBefore.='public function is'.($camelized=UInflector::camelize($name,false)).'(){return '.($type==='Boolean'?'$this->'.$name.'!==null&&$this->'.$name.'!==false&&$this->'.$name.'!==0':'$this->'.$name).';}';
							$specialFieldsBefore.='public function display'.$camelized.'(){ return $this->is'.$camelized.'() ? '."_tC('Yes') : _tC('No')".'; }';
						}elseif($type==='Json'){
							$fieldName=UInflector::camelize($name,true);
							$specialFieldsSetData.='if(isset($data[\''.$name.'\'])) $data[\''.$name.'\']=json_decode($data[\''.$name.'\'],true);'
								.' $this->'.$fieldName.'=&$data[\''.$name.'\'];';
							$specialFieldsGetData.='if(isset($d[\''.$name.'\'])){ unset($d[\''.$name.'\']); $d[\''.$name.'\']=json_encode($data[\''.$name.'\']);}';
							$specialFieldsBefore.='public $'.$fieldName.';';
						}
					}
				
					return 'class '.$matches[2].$matches[3].'{public static $__className=\''.$matches[2].'\',$__modelInfos,$__PROP_DEF,$_relations,'
						.'$__tableName='."'".$annotations['TableName'][0][0]."'".',$__alias='."'".$annotations['TableAlias'][0][0]."'"
						.',$__pluralized='."'".UInflector::pluralizeCamelizedLastWord($matches[2])."'"
						.($dbName?',$__dbName=\''.$dbName.'\',$__modelDb':'')
						.(isset($annotations['DisplayField'][0][0])?',$__displayField=\''.$annotations['DisplayField'][0][0].'\'':'')
						.($orderByField?',$__orderByField=\''.$orderByField.'\'':'')
						.',$__cacheable='.($cacheable?'true':'false')
						.';'
						.(empty($specialFields)?'':
							$specialFieldsBefore
							.(empty($specialFieldsSetData)?'':'public function _setData($data){'.$specialFieldsSetData.'parent::_setData($data);}')
							.(empty($specialFieldsGetData)?'':'public function &_getData(){$data=parent::_getData();$d=$data;'.$specialFieldsGetData.'return $d;}')
						)
						.($createdField||isset($annotations['CreatedBy'])||$createdByField||isset($annotations['Child'])?
							'public static function QInsert(){return new QInsert(self::$__className,'.($stringCreatedField=($createdField?UPhp::exportString($createdField):'null')).($createdByField?','.UPhp::exportString($createdByField):'').');}'
							.'public static function QInsertSelect(){return new QInsertSelect(self::$__className,'.$stringCreatedField.');}'
							.'public static function QReplace(){return new QReplace(self::$__className,'.$stringCreatedField.');}'
						:'')
						.($updatedField||isset($annotations['Child'])?/*'protected function _beforeUpdate(){if(!isset($this->'.$updatedField.')) $this->'.$updatedField.'=date(\'Y-m-d H:i:s\');return parent::_beforeUpdate();}'*/
						'public static function QUpdate(){return new QUpdate(self::$__className,'.($stringUpdatedField=($updatedField?UPhp::exportString($updatedField):'null')).');}'
						.'public static function QUpdateOne(){return new QUpdateOne(self::$__className,'.$stringUpdatedField.');}'
						:'')
						.$classBeforeContent;
						//.implode('',array_map(function(&$field){return 'public function &'.UInflector::camelize($field,false).'($v){$this->_set('.UPhp::exportString($field).',$v);return $this;}';},array_keys($modelFile->_fields)))
						
				},$content,1);
				
				$contentInfos['colsName']=array_keys($contentInfos['columns']);
				
				$relations=&$contentInfos['relations'];
				
				foreach(array('hasMany','belongsTo','hasOne','hasOneThrough','hasManyThrough','belongsToType') as $relType){
					$content=preg_replace_callback('/\s*public\s*(?:static)?\s*\$'.$relType.'\s*=\s*(array\(.*\);)/Us',function($matches2) use(&$relations,&$relType,&$contentInfos){
						$matches2[1]=preg_replace('/\s*\b([A-Z][A-Za-z\_]+)\s*\=\>/','"$1"=>',$matches2[1]);
						$eval=dev_eval('return '.$matches2[1]);
						if(empty($eval) && !empty($matches2[1]) && !is_array($eval))
							throw new Exception('Failed to eval :'."\n".$matches2[1]);
						foreach($eval as $key=>&$relation){
							if(is_numeric($key)){ $key=$relation; $relation=array(); }
							$relation['reltype']=$relType;
							$relations[$key]=$relation;
						}
						if($relType==='belongsTo') $contentInfos['belongsToRelations'][]=$key;
						return '';
					},$content);
				}
			}else{
				// MongoDB
				$contentInfos=array('indexes'=>array());
				$content=preg_replace_callback(self::REGEXP_CLASS,function($matches) use(&$modelFile,&$contentInfos){
					$annotations=empty($matches[1])?array():PhpFile::parseAnnotations($matches[1],true);
					$modelFile->_className=$matches[2];
					$dbName=isset($annotations['Db'])?$annotations['Db'][0][0]:false;
					
					$indexes=&$contentInfos['indexes'];
					if(isset($annotations['Index'])){
						foreach($annotations['Index'] as $index) $indexes[0][]=$index;
					}
					if(isset($annotations['IndexUnique'])){
						foreach($annotations['IndexUnique'] as $index) $indexes[1][]=$index;
					}
					
					return 'class '.$matches[2].$matches[3].'{public static $__className=\''.$matches[2].'\',$__collection'
								.($dbName?',$__dbName=\''.$dbName.'\',$__modelDb':'')
								.';'
					;
				},$content);
			}
			$this->_contentInfos='<?php return '.UPhp::exportCode($contentInfos).';';
			$content.=/*'define(\''.$matches[2].'\',\''.$matches[2].'\');'.*/$matches[2].'::init("'.$matches[2].'");';
		}
		return $this->addExecuteToQueries($content,true);
	}

	private function writeInfos(){
		if(empty($this->_className)) return;
		$dirname=$this->currentDestFile->getPath();
		while(basename(($dirname=dirname($dirname))) != 'models');
		
		$file=new File($filename=$dirname.'/infos/'.$this->_className);
		$file->mkdirs();
		$file->write($this->_contentInfos);
	}

	public function getEnhancedDevContent(){
		$this->writeInfos();
		return parent::getEnhancedDevContent();
	}

	public function getEnhancedProdContent(){
		$this->writeInfos();
		return parent::getEnhancedProdContent();
	}
	
	
	public $_fields=array();
	public $_pks=array();
	private function fields($matches){
		$fieldName=$matches[2];
		
		$annotations=PhpFile::parseAnnotations($matches[1],false,null,true);
		$this->_fields[$fieldName]=$annotations;

		return empty($matches[3])?'':$matches[0];
	}
	private function mfields($matches){
		$matches2=array();
		preg_match_all('/\/\*\*([^;{]*)\*\/\s+\$([A-Za-z0-9\s_]+)[\s|,|;]/Ums',$matches[1],$matches2);
		foreach($matches2[1] as $key=>$comm) $this->fields(array(1=>$comm,$matches2[2][$key]));
	}
	
	
	public static function initFolder($folder,$config){
		$d=new Folder($folder->getPath().'models/infos');
		//if($d->exists()) $d->moveTo($tmpFolder.'models/infos');
		if(!$d->exists()) $d->mkdirs(0775);
	}
	
	/*public static function afterEnhanceApp($hasOldDef,$newDef,$appDir,$dev,$prod){
		if($hasOldDef){
			$changes=empty($newDef['changes']) ? false : $newDef['changes'];
			
			// MODELS
			$modelChanges=array();
			if($changes){
				if(!empty($changes['Model'])){
					$modelChanges=array();
					foreach($changes['Model'] as $mfile){
						$mfile=new File($mfile);
						$modelChanges[]=substr($mfile->getName(),0,-4);
					}
				}
			}
			
			$path_part2='models/infos/';
			foreach(array($tmpDev=>$dev->getPath(),$tmpProd=>$prod->getPath()) as $src=>$dest){
				$f=new Folder($src.$path_part2);
				if(!$f->exists()) continue;
				foreach($f->listAll() as $file){
					$filename=$file->getName();
					$destFile=new File($dest.$path_part2.$filename);
					if(!$destFile->exists() && !in_array(($filename=rtrim($filename,'_')),$modelChanges) && file_exists($dest.'models/'.$filename.'.php'))
						$file->moveTo($destFile->getPath());
				}
			}
		}
	}*/
}