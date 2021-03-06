<?php
/** Transform GET and POST parameters into class and typed variables. */
class CBinder{
	/**
	 * take a value and return a real value
	 * A string can be transformed into an int
	 * A array can be transformed into an object
	 * 
	 * @param string the type of the final value
	 * @param mixed the original value
	 * @param array A list of annotations
	 * @param bool If true, do the validation
	 * @return mixed
	 * 
	 */
	public static function bind($type,&$val,$annotations=array(),$withValidation=false){
		$methodName='bind'.ucfirst($type);
		if($val===NULL){
			if($withValidation && $annotations && isset($annotations['Required'])) CValidation::required($withValidation,false);
			return NULL;
		}
		// direct binding
		if(method_exists(__CLASS__,$methodName)){
			$val=self::$methodName($val);
			if($withValidation && $annotations) ACValidation::valid($withValidation,$annotations,$val);
			return $val;
		}
		if(!empty($val)){
			if($type=='array') return $val;
			if(substr($type,0,5)=='array'){//ex : array[]int
				$typeArray=substr($type,7);
				if(!empty($val)){
					if(is_array($val)) foreach($val as $key=>$value) $val[$key]=self::bind($typeArray,$value);
					else $val=array(self::bind($typeArray,$val));
				}
				if($withValidation && $annotations) ACValidation::valid($withValidation,$annotations,$val);
				return $val;
			}
			if(is_array($val) && class_exists($type)){
				$val=self::_bindObject($type,$val,$withValidation && isset($annotations['Valid']) ? $withValidation : false,isset($annotations['Valid']) ? $annotations['Valid'] : null);
				if($withValidation && $annotations) ACValidation::valid($withValidation,$annotations,$val);
				return $val;
			}
		}
		if($withValidation && $annotations && isset($annotations['Required'])) CValidation::required($withValidation,false);
		return false;
	}

	/**
	 * Bind a simple value : string, int, float, double, boolean, datetime
	 * 
	 * @param string string, int, float, double, boolean, datetime
	 * @param mixed
	 * @param array
	 * @return mixed
	 */
	public static function bindSimple($type,&$val,$annotations=array()){
		if($type==='string') return self::bindString($val);
		if($val==='') return null;
		switch($type){ 
			case 'int': return (int)$val;
			case 'float': return (float)$val;
			case 'double': return (double)$val;
			case 'bool': case 'boolean':
				if($val==='' || $val==='0' || $val==='off' || $val===chr(0x00)) return false;
				if($val==='1' || $val==='on') return true;
				return (bool)$val;
			case 'datetime': return strtotime($val);
			default:
				die($type.' is not bindable !');
		}
	}
	
	/**
	 * @see UEncoding::convertToUtf8
	 * @param mixed
	 * @return string
	 */
	public static function bindString($val){ return UEncoding::convertToUtf8((string)$val); }
	/**
	 * @param mixed
	 * @return int or null if empty
	 */
	public static function bindInt($val){ return $val===''?null:(int)$val; }
	/**
	 * @param mixed
	 * @return float or null
	 */
	public static function bindFloat($val){ if($val==='') return null; $val=self::parseDecimalFormat($val); return (float)$val; }
	/**
	 * @param mixed
	 * @return double or null
	 */
	public static function bindDouble($val){ if($val==='') return null; $val=self::parseDecimalFormat($val); return (double)$val; }
	/**
	 * @param mixed
	 * @return string
	 */
	public static function parseDecimalFormat($val){
		$config=App::getLocale()->data('decimalFormat');
		if($config['thousandsSep'] !== '') $val=str_replace($config['thousandsSep'],'',$val);
		if($config['decimalSep'] !== '.') $val=str_replace($config['decimalSep'],'.',$val);
		return $val;
	}
	/**
	 * @param mixed
	 * @return bool
	 */
	public static function bindBool($val){ return self::bindBoolean($val); }
	/**
	 * @param mixed
	 * @return bool
	 */
	public static function bindBoolean($val){
			if($val==='' || $val==='0' || $val==='off' || $val===chr(0x00)) return false;
			if($val==='1' || $val==='on') return true;
			return (bool)$val;
	}
	/**
	 * @param string
	 * @return int
	 */
	public static function bindDatetime($val){
		return strtotime($val);
	}
	/**
	 * @param string
	 * @return int
	 */
	public static function bindDate($val){
		return strtotime($val);
	}
	//public static function bindLong($val){ return (long)$val; }

	/**
	 * Bind an array into a real object
	 * 
	 * @param string the name of the class
	 * @param array
	 * @param bool do a validation
	 * @param array list of the properties. If null, take all from __PROP_DEF
	 */
	public static function _bindObject($type,$val,$withValidation=false,$validProperties=null){
		$obj=new $type();
		$propertiesDef= property_exists($type,'__PROP_DEF') ? $type::$__PROP_DEF : array();
		if(!empty($validProperties)) $validProperties=array_intersect($validProperties,array_keys($propertiesDef));
		else $validProperties=null;
		
		foreach($val as $key=>$value){
			if(isset($propertiesDef[$key]['annotations']['NotBindable'])) continue;
			//if(isset($propertiesDef[$key]['annotations']))
			$obj->$key=!isset($propertiesDef[$key])?$value:
				self::bind($propertiesDef[$key]['type'],$value,isset($propertiesDef[$key]['annotations'])?$propertiesDef[$key]['annotations']:null,$withValidation && ($validProperties===null || in_array($key,$validProperties))?$withValidation.'.'.$key:false);
		}

		if($validProperties===null) $validProperties=array_keys($propertiesDef);

		foreach($validProperties as $key){
			if(isset($val[$key]) || isset($propertiesDef[$key]['annotations']['NotBindable'])) continue;
			if($withValidation && (isset($propertiesDef[$key]['annotations']['Required']) || isset($propertiesDef[$key]['annotations']['Valid'])))
				CValidation::required($withValidation.'.'.$key,false);
		}
		return $obj;
	}
	
	public static function _bindObjectFromDB($type,&$val){
		$obj=new $type();
		$propertiesDef=$type::$__PROP_DEF;
		foreach($val as $key=>$value){
			//debug($key.': '.(!isset($propertiesDef[$key])?'!isset':$propertiesDef[$key]['type']));
			$obj->$key=!isset($propertiesDef[$key])?$value:
				self::bindSimple($propertiesDef[$key]['type'],$value,isset($propertiesDef[$key]['annotations'])?$propertiesDef[$key]['annotations']:null);
		}
		return $obj;
	}
}
