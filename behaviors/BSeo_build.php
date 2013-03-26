<?php
class BSeo_build{
	public static function onBuild($modelFile,&$contentInfos,$annotations,$enhanceConfig,&$classBeforeContent){
		foreach(array('meta_title','meta_descr','meta_keywords') as $fieldName)
			if(isset($modelFile->_fields[$fieldName])) throw new Exception($modelFile->_className.' already contains a field "'.$fieldName.'"');
		
		if(isset($annotations['Translatable'])) return;
		
		$modelFile->_fields['meta_title']=array( 'SqlType'=>array('varchar(100)'), 'Null'=>false );
		$modelFile->_fields['meta_descr']=array( 'SqlType'=>array('varchar(200)'), 'Null'=>false, 'Text'=>false );
		$modelFile->_fields['meta_keywords']=array( 'SqlType'=>array('text'), 'Null'=>false, 'MaxLength'=>array(1000) );
	}
}