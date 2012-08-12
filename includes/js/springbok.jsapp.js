includeLib('jquery-1.7.2.min');
includeCore('springbok.base');

S.loadSyncScript(webUrl+'js/i18n-'+(S.lang=$('meta[name="language"]').attr('content'))+'.js');

(function(){
	var readyCallbacks=$.Callbacks(),loadedRequired={};
	window.App={
		header:'',footer:true,page:0,
		
		jsapp:function(name,version){this.name=name;this.version=version;},
		
		init:function(){
			if(this.footer===true) this.footer=this.name+' | '+S.dates.niceDateTime(this.version*1000)+' | '+S.html.powered();
			$('#container').html('').append($('<header/>').html(this.header),this.page=$('<div id="page"/>'),$('<footer/>').html(this.footer));
		},
		
		ready:function(callback){
			readyCallbacks.add(callback);
		},
		
		run:function(){
			App.init();
			readyCallbacks.fire();
			S.ajax.load(S.history.getFragment());//TODO duplicate if #
		},
		
		require:function(){
			$.each(arguments,function(k,fileName){
				if(!loadedRequired[fileName]){
					loadedRequired[fileName]=true;
					S.loadSyncScript(webUrl+'js/app/'+fileName+'.js'/* DEV */+'?'+(new Date().getTime())/* /DEV */);
				}
			});
		},
		
		api:{
			//List,Retrieve
			get:function(url,data,type){
				var result,headers={};
				if(S.CSecure.isConnected()) headers.SAUTH=S.CSecure._token;
				jQuery.ajax({
					type:type,
					headers:headers,
					url:basedir+'api/'+url,
					data:data,
					success:function(r){result=r;},
					error:function(jqXHR, textStatus, errorThrown){
						console.log('Error:',jqXHR);
						if(jqXHR.status===403){
							if(S.CSecure.isConnected()) S.CSecure.reconnect()
						}
					},
					dataType:'json', cache:false,
					async:false
				});
				return result;
			},
			//Create
			post:function(url,data){
				this.get(url,data,'POST');
			},
			//Replace
			put:function(url,data){
				this.get(url,data,'PUT');
			},
			//Delete
			del:function(url,data){
				this.get(url,data,'DELETE');
			}
		}
	};
	S.ready=App.ready;
}());

function FatalError(error){
	alert(error);
	$('#jsAppLoadingMessage').addClass('message error').text(error);
}

includeCore('jsapp/httpexceptions');
includeCore('jsapp/langs');
includeCore('jsapp/controller');
includeCore('jsapp/model');
includeCore('jsapp/layout');
includeCore('helpers/form');
includeCore('springbok.router');
includeCore('springbok.html');
includeCore('springbok.menu');
includeCore('springbok.forms');
includeCore('springbok.date');
includeCore('springbok.ajax');
includeCore('springbok.storage');

App.load=S.ajax.load=function(url){
	if(url.sbStartsWith(basedir)) url = url.substr(basedir.length);
	try{
		var route=S.router.find(url);
		//console.log(route);
		S.history.navigate(url);
		App.require('c/'+route.controller);
		var c=C[route.controller];
		/* DEV */ if(!c) console.log('This action doesn\'t exists: '+route.action); /* /DEV */
		if(!c) notFound();
		c.dispatch(route);
	}catch(err){
		if(err instanceof S.Controller.Stop) return;
		if(err instanceof HttpException){
			console.log("APP : catch HttpException :",err);
		}
		console.log("APP : catch error :",err);
		throw err;
	}
};
