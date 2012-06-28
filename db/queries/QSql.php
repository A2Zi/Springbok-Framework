<?php
class QSql{
	protected $_db,$sql,$calcFoundRows;
	private $fields,$returnValue,$isCalcFoundRows=false,$isSelect,$isShow,$isJoined,$isCount,
		$select,$from,$where,$groupBy,$having,$orderBy,$limit;
	
	public function __construct($db,$sql,$returnValue=false){
		$sql=str_replace("\n",' ',rtrim(trim($sql),';'));
		
		if($this->isSelect=(bool)preg_match('/^\s*SELECT/i',$sql)){
			if(!preg_match('/^SELECT\s+(.+)(?:\s+FROM\s+(.+))?(?:\s+WHERE\s+(.+))?(?:\s+GROUP BY\s+(.+))?(?:\s+HAVING\s+(.+))?(?:\s+ORDER BY\s+(.+))?(?:\s+LIMIT\s+(.+))?$/Ui',$sql,$matches))
				throw new DBException('Bad Query');
			
			$this->select=$matches[1];
			$this->from=empty($matches[2])?false:$matches[2];
			$this->where=empty($matches[3])?false:$matches[3];
			$this->groupBy=empty($matches[4])?false:$matches[4];
			$this->having=empty($matches[5])?false:$matches[5];
			$this->orderBy=empty($matches[6])?false:$matches[6];
			$this->limit=empty($matches[7])?false:$matches[7];
			
			$this->isCount=preg_match('/^COUNT\([^)]+\)$/i',$this->select);
			$this->isJoined=strpos($this->from,',') || strpos($this->from,'(') || stripos($this->from,'JOIN');
			
			if($this->isCount) $this->isCalcFoundRows=true;
			elseif($this->isSelect && ($this->where!==false || $this->having!==false || $this->groupBy!==false)){
				$this->isCalcFoundRows=true;
				$sql=preg_replace('/^\s*SELECT/i','SELECT SQL_CALC_FOUND_ROWS',$sql);
			}
		}elseif($this->isShow=(bool)preg_match('/^\s*SHOW/i',$sql)){
			
		}
		$this->_db=$db; $this->sql=$sql; $this->returnValue=$returnValue;
	}
	
	public function isSelect(){return $this->isSelect;}
	public function isJoined(){return $this->isJoined;}
	public function getFields(){return $this->fields;}
	
	public function setFields($fields){ $this->fields=$fields;}
	
	public function execute(){
		if($this->isSelect()){
			if($this->isCount){
				$res=$this->_db->doSelectValue($this->sql);
				$this->fields=array(array('name'=>'Count result','type'=>'int'));
				$this->calcFoundRows=1;
				if(!$this->returnValue) $res=array(array($res));
				return $res;
			}else{
				$res=$this->_db->doSelectSql($this->sql);
				if($this->isCalcFoundRows) $this->calcFoundRows=$this->_db->doSelectValue('SELECT FOUND_ROWS()');
				$this->fields=$res['fields'];
				return $res['res'];
			}
		}
		$res=$this->_db->doUpdate($this->sql);
		return $res;
	}
	
	public function callback($callback,$callback2=null){
		if($this->isSelect()){
			$t=&$this;
			$callback2_=function($fields) use(&$t,&$callback){
				$t->setFields($fields);
				$callback($fields);
			};
			$this->_db->doSelectSqlCallback($this->sql,$callback2,$callback2_);
		}
	}
	
	public function hasCalcFoundRows(){
		return $this->isCalcFoundRows;
	}
	
	public function foundRows(){
		return $this->calcFoundRows;
	}
	public function noCalcFoundRows(){
		if($this->limit===false) $this->limit='';
		return $this;
	}
	
	
	public function createCountQuery(){
		return new QSql($this->_db,'SELECT COUNT(*) FROM '.$this->from.($this->where===false?'':(' WHERE '.$this->where))
			.($this->groupBy===false?'':(' GROUP BY'.$this->groupBy)).($this->having===false?'':(' HAVING '.$this->having)),true);
	}
	
	public function limit($limit,$down=0){
		if($this->limit===false){
			if($down>0) $this->sql.=' LIMIT '.((int)$down).','.((int)$limit);
			else $this->sql.=' LIMIT '.$limit;
		}
		return $this;
	}
}
