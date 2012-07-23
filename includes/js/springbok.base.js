/*! Springbok */
/*'use strict';*/

var arraySliceFunction=Array.prototype.slice;

window.S={
	ready:function(callback){ $(document).ready(callback); },
	redirect:function(url){ window.location=url; },
	setTitle:function(title){document.title=title;},
	
	imgLoading:function(){ return $('<span class="img imgLoading"/>') },
	
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
		stripHTML:function(str){return str.replace(/<&#91;^>&#93;*>/g, '');},


		autolinkRegExp:new RegExp("(\\s?)(((http|https|ftp)://[^\\s</]+)[^\\s<]*[^\\s<\.)])", "gim"),
		autolink:function(str, attributes){
			attributes = attributes || {"target":"_blank"};
			var attrs = "";
			for(name in attributes) attrs += " "+ name +'="'+ attributes[name] +'"';
			return str.toString().replace(S.tools.autolinkRegExp, '$1<img class="favicon" src="http://www.google.com/s2/favicons?domain=$3" height="16" alt=""/><a href="$2"'+ attrs +'>$2</a>');
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
	isObject:function(varName){ return typeof(varName)==='object' },
	
	clone:function(object){
		// clone like _.clone : Shallow copy
		//return $.extend({},object);
		return S.extendsObj({},object);
	},
	deepClone:function(object){
		return $.extend(true,{},object);
	},
	extendsClass:function(subclass,superclass,methods){
		var f=function (){};
		f.prototype=superclass.prototype;
		subclass.prototype=new f();
		subclass.prototype.constructor=subclass;
		subclass.superctor=superclass;
		subclass.superclass=superclass.prototype;
		
		if(methods) S.extendsPrototype(subclass,methods);
	},
	/*extendsMClass:function(subclass,superclass,methods){
		superclass=arraySliceFunction.call(arguments,1,-1);
		for(var i in superclass){
			
		}
		if(methods) S.extendsPrototype(subclass,methods);
	},*/
	extendsPrototype:function(targetclass,methods){
		for(var i in methods)
			targetclass.prototype[i]=methods[i];
		return targetclass;
	},
	addSetMethods:function(targetclass,methods){
		for(var i in methods.split(',')){
			var methodName=methods[i];
			targetclass.prototype[methodName]=function(val){ this['_'+methodName]=val; return this; };
		}
	},
	
	extendsObj:function(target,object){
		if(object)
			for(var i in object)
				target[i]=object[i];
		return target;
	},
	
	tableClick:function(){
		S.eltClick('table.pointer tr');
	},
	eltClick:function(selector){
		$(selector).off('click').each(function(i,elt){
			elt=$(elt);
			var trTimeout,href=elt.attr('rel');
			elt.on('click',function(){trTimeout=setTimeout(function(){S.redirect(href)},180);});
			elt.find('a[href]:not([href="#"])').off('click').on('click',function(e){ clearTimeout(trTimeout); e.stopPropagation(); e.preventDefault();
					var a=$(this),confirmMessage=a.data('confirm'); if(!confirmMessage || confirm(confirmMessage=='1' ? i18nc['Are you sure ?'] : confirmMessage)) S.redirect(a.attr('href')); });
			elt.find('a[href="#"]').off('click').on('click',function(e){ clearTimeout(trTimeout); e.stopPropagation(); e.preventDefault(); });
		});
		
	}
};

includeCore('springbok.ext.string');
includeCore('springbok.ext.arrays');

RegExp.sbEscape=function(value){
	return value.replace( /([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi, "\\$1" );
};

(function($){
	$(document).on('focus','input.submit,button,.button',function(){ $(this).delay(1200).blur() });

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
	$.fn.sHide=function(){ this.addClass('hidden'); return this; };
	$.fn.sShow=function(){ this.removeClass('hidden'); return this; };
})(jQuery);

function handleError(e){
	//alert("An error has occurred!\n"+e);
	if(console) console.log(e);
	return true;
}
//window.onerror = handleError;

/*function extendBasic(subclass,superclass,basicsuperclass,varName,extendsPrototype){
	extend(subclass,superclass,extendsPrototype);
	for(var i in basicsuperclass.prototype)
		subclass.prototype[i]=function(){return basicsuperclass.prototype[i].apply(this[varName],arguments);}
}*/
