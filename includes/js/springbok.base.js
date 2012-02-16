/*! Springbok */
'use strict';
var $$={
	action:function(controller, action, options){
		var action=basedir+controller+(action ? '/'+action : '');
		if(options){
			action+='?';
			var opts='';
			for(key in options)	action+=key+'='+options[key]+'&';
				return action.substring(0,action.length-1);
		}
		return action;
	},
	redirect:function(url){ window.location=url; },
	setTitle:function(title){document.title=title;},
	
	loadScript:function(url){
		var script = document.createElement("script");
		script.type = "text/javascript";
		script.src = url;
		document.body.appendChild(script);
	},
	loadSyncScript:function(url){
		return $.ajax({
			url:url,
			global:false, async:false, cache:true,
			dataType:'script'
		});
	},
	syncGet:function(url, data, type){
		/*// shift arguments if data argument was omitted
		if ( jQuery.isFunction( data ) ) {
			type = type || callback;
			callback = data;
			data = undefined;
		}*/
		
		var result;
		jQuery.ajax({
			url: url,
			data: data,
			success: function(r){result=r;},
			dataType: type,
			async:false
		});
		return result;
	},
	
	syncJson:function(url,data){
		return this.syncGet(url,data,'json');
	},
	
	
	tools:{
		preg_replace:function(array_pattern, array_pattern_replace, my_string){
			var new_string = String (my_string);
			for (i=0; i<array_pattern.length; i++){
				var reg_exp= RegExp(array_pattern[i], "gi");
				var val_to_replace = array_pattern_replace[i];
				new_string = new_string.replace (reg_exp, val_to_replace);
			}
			return new_string;
		},
		nl2br:function(str){return str.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ '<br />' +'$2');},
		br2nl:function(str){return str.replace(/<br\s*\/?>/mg,'\n');},
		stripHTML:function(str){return str.replace(/<&#91;^>&#93;*>/g, '');},


		autolinkRegExp:new RegExp("(\\s?)(((http|https|ftp)://[^\\s</]+)[^\\s<]*[^\\s<\.)])", "gim"),
		autolink:function(str, attributes){
			attributes = attributes || {"target":"_blank"};
			var attrs = "";
			for(name in attributes) attrs += " "+ name +'="'+ attributes[name] +'"';
			return str.toString().replace($$.tools.autolinkRegExp, '$1<img class="favicon" src="$3/favicon.ico" height="16" alt=""/><a href="$2"'+ attrs +'>$2</a>');
		}
	},
	
	preventMiddleClick:function(){
		$(document).bind('click',function(e){
			if(e.which==2){
				e.preventDefault();
				return false;
			}
		});
	},
	
	isString:function(varName){ return typeof(varName)==='string'; },
	isArray:Array.isArray || $.isArray,
	isObject:function(){ return typeof(varName)==='object' }
	
};

includeCore('springbok.ext.string');
includeCore('springbok.ext.arrays');

RegExp.prototype.sbEscape=function(value){
	return value.replace( /([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi, "\\$1" );
};

(function($){
	/* https://github.com/bgrins/bindWithDelay/blob/master/bindWithDelay.js */
	$.fn.delayedBind=function(delay,eventType,eventData,handler,throttle){
		if($.isFunction(eventData)){
			throttle = handler;
			handler = eventData;
			eventData = undefined;
		}
		handler.guid = handler.guid || $.guid++;
		
		return this.each(function(){
			var wait = null;
			
			function cb() {
				var ctx = this;
				var throttler = function() {
					wait = null;
					handler.apply(ctx,arguments);
				};
				if (!throttle) { clearTimeout(wait); wait = null; }
				if (!wait) { wait = setTimeout(throttler,delay); }
			}
			cb.guid = handler.guid;
			$(this).bind(eventType,eventData,cb);
		});
	};
	
	$.fn.delayedKeyup=function(delay,handler){
		if($.isFunction(delay)){
			handler=delay;
			delay=200;
		}
		return $(this).delayedBind(delay,'keyup',undefined,handler);
	};
})(jQuery);

function handleError(e){
	//alert("An error has occurred!\n"+e);
	if(console) console.log(e);
	return true;
}
//window.onerror = handleError;


function extend(subclass,superclass,extendsPrototype){
	var f=function (){},i;
	f.prototype=superclass.prototype;
	subclass.prototype=new f();
	subclass.prototype.constructor=subclass;
	subclass.superconstructor=superclass;
	subclass.superclass=superclass.prototype;
	
	extendPrototype(subclass,extendsPrototype);
}
function extendPrototype(targetclass,p){
	for(var i in p)
		targetclass.prototype[i]=p[i];
}

/*function extendBasic(subclass,superclass,basicsuperclass,varName,extendsPrototype){
	extend(subclass,superclass,extendsPrototype);
	for(var i in basicsuperclass.prototype)
		subclass.prototype[i]=function(){return basicsuperclass.prototype[i].apply(this[varName],arguments);}
}*/