S.dialogs={
	alert:function(title,message){
		var div=$('<div/>'),buttons={};
		buttons[i18nc['Close']]=function(){$(this).dialog( "close" );};
		S.isString(message) ? div.text(message) : div.html(message);
		div.dialog({
		    autoOpen: true,
		    title:title,
		    position:['center',150],
		    width:450,
		    modal:true,
		    buttons:buttons,
		    close:function(){ div.remove(); },
		    zIndex:9000 //fancybox : 8030
		});
	},
	
	prompt:function(title,message,okButtonName,defaultVal,callback){
		var div=$('<div/>'),buttons={};
		if($.isFunction(defaultVal)){
			callback=defaultVal;
			defaultVal='';
		}
		S.isString(message) ? div.text(message) : div.html(message);
		div.append($('<input type="text" class="wp100"/>').val(defaultVal).keydown(function(e){
			if(e.keyCode == '13'){
				e.preventDefault();
				e.stopImmediatePropagation();
				div.dialog( "close" );
				callback($(this).val());
				return false;
			}
		}));
		
		buttons[i18nc.Cancel]=function(){$(this).dialog( "close" );};
		buttons[okButtonName]=function(){
			div.hide();
			callback(div.find('input').val());
			div.dialog( "close" );
		};
		
		div.dialog({
		    autoOpen: true,
		    title:title,
		    position: ['center',150],
		    width:450,
		    modal:true,
		    buttons:buttons,
		    close:function(){ div.remove(); },
		    zIndex:9000 //fancybox : 8030
		});
	},
	
	form:function(title,content,okButtonName,callback){
		var div=$('<div/>'),buttons={};
		div.html(content);
		
		buttons[i18nc.Cancel]=function(){div.dialog( "close" );};
		buttons[okButtonName]=function(){
			div.hide();
			callback();
			div.dialog( "close" );
		};
		
		div.dialog({
		    autoOpen: true,
		    title:title,
		    position: ['center',150],
		    width:450,
		    modal:true,
		    buttons:buttons,
		    close:function(){ div.remove(); },
		    zIndex:9000 //fancybox : 8030
		});
	}
};
