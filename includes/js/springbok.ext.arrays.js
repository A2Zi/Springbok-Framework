S.extProto(Array,{
	sHas:function(searchElement,i){
		if(this.indexOf) return this.indexOf(searchElement,i);
		/* See jQuery.inArray */
		var t=this,l=t.length;
		i=i ? i < 0 ? Math.max( 0, l + i ) : i : 0;
		for(; i < l; i++ )
			if(i in t && t[i] === searchElement) return i;
		return -1;
	},
	sEach:function(callback){
		return $.each(this,callback);
	},
	sFindBy:function(propName,val){
		var k=this.sFindKeyBy(propName,val);
		if(k===false) return k;
		return this[k];
	},
	sFindKeyBy:function(propName,val){
		var res=false;
		this.sEach(function(k,v){
			if(v[propName] == val){
				res=k;
				return false;
			}
		});
		return res;
	},
	sSortBy:function(propName,asc,sortFunc){
		if(!$.isFunction(sortFunc)) sortFunc=S.arraysort[sortFunc===undefined?'':sortFunc];
		return this.sort(function(a,b){
			if(asc) return sortFunc(a[propName],b[propName]);
			return sortFunc(b[propName],a[propName]);
		});
	},
	sEqTo:function(array){
		if(typeof array !== 'array' || this.length != array.length) return false;
		for (var i = 0; i < array.length; i++) {
	        /*if (this[i].compare) { 
	            if (!this[i].compare(testArr[i])) return false;
	        }*/
	        if(this[i] !== array[i]) return false;
	    }
	    return true;
	},
	sLast:function(){return this[this.length-1]}
});