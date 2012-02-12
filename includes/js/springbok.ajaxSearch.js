/* https://github.com/jquery/jquery-ui/blob/master/ui/jquery.ui.autocomplete.js */
(function($){
	$.fn.ajaxSearch=function(url,minLength,destContent,display){
		var xhr,input=this,lastVal='',currentTimeout;
		this.keyup(function(){
			var val=input.val();
			if(val != '' && val.length >= minLength && val!=lastVal){
				$$.history.navigate(url+'/'+val);
				lastVal=val;
				if(xhr){xhr.abort(); xhr=null;}
				if(currentTimeout) clearTimeout(currentTimeout);
				currentTimeout=setTimeout(function(){
					xhr=$.ajax({
						url:url,
						data:{term:val},
						dataType: 'json',
						success:function(data){
							if(display) result=display(data);
							else{
								var result=$('<ul/>'),li;
								$.each(data,function(i,v){
									li=$('<li/>');
									if(typeof(v) ==='string') li.html(v);
									else li.html($('<a/>').attr('href',v.url).text(v.text));
									result.append(li);
								});
							}
							destContent.html(result);
						},
						error:function(){
							destContent.html('');
						}
					});
				},160);
			}
		});
		return this;
	};
})(jQuery);
