S.HEltFInputHidden=function(form,name,value){
	this._form=form;
	this.elt=$('<input type="hidden">')/*.data('sElt',this)*/.attr({name:name,value:value});
};
S.extendsClass(S.HEltFInputHidden,S.HElt,{
	toElt:function(){ return this.elt; },
	end:function(){ this._form.append(this.toElt()); return this._form; },
	
});
