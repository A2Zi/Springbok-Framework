<?php
class CPagination_Letters extends CPagination{
	public static function create($query){
		return new self($query);
	}
	
	private $fieldName='name';
	private function __construct($query){
		$this->query=$query;
		if(isset($_REQUEST['page'])) $this->page=$_REQUEST['page'];
		else $this->page='A';
	}
	
	public function &fieldName($fieldName){$this->fieldName=&$fieldName;return $this;}
	public function &pageSize($pageSize){throw new BadMethodCallException('This method is not usable in CPagination_Letters !'); return $this;}
	public function &getPageSize(){throw new BadMethodCallException('This method is not usable in CPagination_Letters !');}
	public function &page($page){$this->page=&$page;return $this;}
	public function &getPage(){return $this->page;}
	public function &getTotalResults(){throw new BadMethodCallException('This method is not usable in CPagination_Letters !');}
	public function &getTotalPages(){$totPage=26;return $totPage;}
	public function &getResults(){return $this->results;}
	public function isEmptyResults(){return empty($this->results);}
	public function hasPager(){ return true;}
	
	public function getAvailableLetters(){
		$modelName=$this->query->getModelName();
		return $modelName::findFirstLetters($this->fieldName);
	}
	
	public function &execute(){
		$this->results=$this->query->addCondition($this->fieldName.' LIKE',$this->page.'%')->execute();
		return $this;
	}
	
	public function refindResults($page){
		$this->results=$this->query->limit($this->fieldName.' LIKE',($this->page=$page).'%')->reexecute();
	}
}