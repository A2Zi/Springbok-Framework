<?php
class SJsAppController extends Controller{
	public static function beforeDispatch(){
		if(!CHttpRequest::isAjax()) self::renderStartPage();
	}
	
	protected static function renderStartPage(){
		echo '<!DOCTYPE html><html><head>'
			.'<meta charset="UTF-8">'
			.'<title>'.Config::$projectName.' - '.($loading=_tC('Loading...')).'</title>';
		HHtml::cssLink();
		echo HHtml::jsInline(
			'var i18n_lang="'.CLang::get().'";'
			.'window.onload=function(){'
				.'var s=document.createElement("script");'
				.'s.type="text/javascript";'
				.'s.src="'.HHtml::staticUrl('/jsapp'.'.js','js').'";'
				.'document.body.appendChild(s);'
			.'};'
		);
		echo '</head><body>'
			.'<div id="container"><div class="startloading"><b>'.Config::$projectName.'</b><div id="jsAppLoadingMessage">'.($loading).'</div></div></div>'
			.'</body></html>';
		exit;
	}
}
