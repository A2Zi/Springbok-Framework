includeCore('ui/inputbox');

(function(){
	var inputBoxHandler=S.ui.InputBox.extend({
		initDiv:function(){
			var dataBox=this.input.data('box'),firstChar=dataBox.charAt(0);
			this.div=firstChar=='#'||firstChar=='.' ? $(dataBox) : this.createDiv().text(dataBox);
			this.input.removeAttr('data-box');
		}
	});
	/* DEV */window.inputDataBoxHandlerIncluded=true;/* /DEV */
	$document.on('focus','input[data-box]',function(e){
		new inputBoxHandler($(this));
	});
})();
