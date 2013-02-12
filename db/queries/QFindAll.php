<?php
class QFindAll extends QFind{
	private $tabResKey,$groupResBy,$res;
	
	public function execute(){
		//$res=$this->_db->doSelectRows_($query);
		if($this->tabResKey !== null) $this->res=$res=$this->_db->doSelectAssocObjects($this->_toSQL(),$this,$this->queryResultFields,$this->tabResKey);
		else $this->res=$res=$this->_db->doSelectObjects($this->_toSQL(),$this,$this->queryResultFields);
		
		if($res){
			if($this->groupResBy!==null){
				$grbf=$this->groupResBy;
				$finalRes=array();
				if(is_array($grbf)) foreach($res as $key=>&$row) $finalRes[$row->{$grbf[0]}][$key]=$row->{$grbf[1]};
				else foreach($res as $key=>&$row) $finalRes[$row->$grbf][$key]=$row;
				$res=$finalRes;
			}
		}
		
		if($this->calcFoundRows===true) $this->calcFoundRows=(int)$this->_db->doSelectValue('SELECT FOUND_ROWS()');
		
		if($res) $this->_afterQuery_objs($res);
		return $res;
	}
	
	public function callback($callback,$callback2=null){
		$sql=$this->sqlBigResult()->sqlNoCache()->_toSQL();
		if($callback2!==null){
			$callback($this->getModelFields());
			$callback=$callback2;
		}elseif(is_string($callback)){
			$callback=create_function('$m','$m->'.$callback.';');
			/* DEV */if($callback===false) throw new Exception('Failed to create lambda function : $m->'.$callback.';'); /* /DEV */
		}
		$this->_db->doSelectObjectsCallback($sql,$this,$this->queryResultFields,$callback);
	}
	
	public function iterator($size=50,$limit=false){
		return new QFindAllIterator($this,$size,$limit);
	}
	
	
	public function toArray(){
		return SModel::mToArray($this->execute());
	}
	public function paginate(){
		return CPagination::_create($this);
	}
	
	public function createCountQuery(){
		$countQuery = new QCount($modelName=$this->modelName);
		$this->_copyJoinsAndConditions($countQuery);
		if($this->groupBy) $countQuery->setCountField((!empty($this->where)&&($this->joins/*||$with*/)
					 && strpos($this->groupBy[0],'.')===false?$modelName::$__alias.'.':'').$this->groupBy[0],true);
		return $countQuery;
	}
	
	public function calcFoundRows(){
		$this->calcFoundRows=true;
		return $this;
	}
	public function noCalcFoundRows(){
		$this->calcFoundRows=null;
		return $this;
	}
	
	public function hasCalcFoundRows(){
		return $this->calcFoundRows;
	}
	
	public function foundRows(){
		return $this->calcFoundRows;
	}
	
	public function tabResKey($field='id'){
		$this->tabResKey=$field;
		return $this;
	}
	
	public function groupResBy($field){
		$this->groupResBy=$field;
		return $this;
	}
}