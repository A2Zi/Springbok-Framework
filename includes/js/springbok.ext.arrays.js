extendPrototype(Array,{
	sbInArray:function(val){
		return $.inArray(val,this);
	},
	sbEach:function(callback){
		return $.each(this,callback);
	},
	sbFindBy:function(propName,val){
		var k=this.sbFindKeyBy(propName,val);
		if(k===false) return k;
		return this[k];
	},
	sbFindKeyBy:function(propName,val){
		var res=false;
		this.sbEach(function(k,v){
			if(v[propName] == val){
				res=k;
				return false;
			}
		});
		return res;
	},
	sbSortBy:function(propName,asc,sortFunc){
		if(!$.isFunction(sortFunc)) sortFunc=$$.arraysort[sortFunc===undefined?'':sortFunc];
		return this.sort(function(a,b){
			if(asc) return sortFunc(a[propName],b[propName]);
			return sortFunc(b[propName],a[propName]);
		});
	},
	sbEqualsTo:function(array){
		if(typeof array !== 'array' || this.length != array.length) return false;
		for (var i = 0; i < array.length; i++) {
	        /*if (this[i].compare) { 
	            if (!this[i].compare(testArr[i])) return false;
	        }*/
	        if(this[i] !== array[i]) return false;
	    }
	    return true;
	},
	sbLast:function(){return this[this.length-1]}
});