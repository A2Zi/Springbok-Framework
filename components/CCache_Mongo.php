<?php
/** File Cache */
class CCache_Mongo extends CCache{
	private $collection;
	private static $db;
	public function __construct($config){
		if(empty($config['db'])) throw new Exception('No db selected');
		if(!isset(self::$db) && self::$db!==false){
			self::_initDB($config['db']);
		}
		if(!empty($config['collection'])){
			$this->setCollection($config['collection']);
		}
	}
	
	/**
	 * @param string
	 * @return current object instance
	 */
	public function setCollection($collectionName){
		if(self::$db!==false)
			$this->collection=self::$db->collection($collectionName);
		return $this;
	}
	
	/**
	 * @internal
	 * Try to access to mongo
	 * 
	 * @return void
	 */
	public static function _initDb($dbName){
		try{
			self::$db=DB::init($dbName);
		}catch(Exception $e){
			usleep(200);
			try{
				self::$db=DB::init($dbName);
			}catch(Exception $e){
				self::$db=false;
			}
		}
	}
	
	
	
	/**
	 * @param string
	 * @return mixed data or null if not present of false if data is empty
	 */
	public function read($key,$fields=array('_id'=>0,'_date'=>0)){
		if(!isset($this->collection)){
			return null;
		}
		$res=$this->collection->findOne(array('_id'=>$key),$fields);
		if(isset($res['data'])){
			return $res['data'];
		}
		else{
			return $res;
		}
			
	}
	
	
	/**
	 * @param string
	 * @param string
	 * @see UFile::writeWithLock
	 */
	public function write($key,$d){
		if(!isset($this->collection)){
			throw new Exception('No Collection selected');
		}
		/*debug(func_get_args());*/
		$data=array('_id'=>$key,'_date'=>new MongoDate(),'data'=>$d);
		
		try{
			$this->collection->update(array('_id'=>$key),$data,array('w'=>0,'upsert'=>true));
		}
		catch(MongoException $e){
			debugVar($data);
			die($e->getMessage());
		}
	}
	
	/**
	 * @param string
	 */
	public function delete($key){
		if(!isset($this->collection)){
			throw new Exception('No Collection selected');
		}
		$this->collection->remove(array('_id'=>$key),array('justOne'=>true));
	}
}