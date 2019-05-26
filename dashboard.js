var dashboard = {

  'add':function(){
	  $.ajax({ url: path+"dashboard/create.json", data: "", async: false, success: function(data){} });
  },

  'remove':function(id){
    $.ajax({ url: path+"dashboard/delete.json", data: "id="+id, async: false, success: function(data){} });
  },
  
  'list':function(){
    var result = {};
    $.ajax({ url: path+"dashboard/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  },

  'set':function(id, fields){
    var result = {};
    $.ajax({ type: "POST", url: path+"dashboard/set.json", data: "id="+id+"&fields="+JSON.stringify(fields), async: false, success: function(data){result = data;} });
    return result;
  },

  'setcontent':function(id, content,height){
    var result = {};
    $.ajax({
        type: "POST",
        url :  path+"dashboard/setcontent.json",
        data : "&id="+id+'&content='+encodeURIComponent(content)+'&height='+height,
        dataType: 'json',
        async: false,
        success : function(data) { result = data; }
    });
    return result;
  },

  'clone':function(id){
    var result = {};
    $.ajax({ url: path+"dashboard/clone.json", data: "id="+id, async: false, success: function(data){result = data;} });
    return result;
  }

}



// ASYNC : TRUE 
// ----------------------------------------------------
// Should return a promise object for callee to run .done(),.fail() etc
// the v1 version was not asyncronus and blocked page rendering
/*
eg:
calling this from a page is non-blocking:
```
    dashboard_v2.list().done(function(data){
        alert('found %s results'.replace('%s',data.length))
    })
```
however
calling the v1 version would block the page render until complete:
`   alert('found %s results'.replace('%s', dashboard.list().length))

*/

var dashboard_v2 = {

    add: function(){
        return dashboard_v2._fetch(path + "dashboard/create.json");
    },
  
    remove: function(id){
        return dashboard_v2._fetch({
            url: path + "dashboard/delete.json",
            data: {id: id}
        });
    },

    list: function(){
        return dashboard_v2._fetch(path + "dashboard/list.json");
    },

    set: function(column, id, value){
        var fields = {} 
        fields[column] = value;
        return dashboard_v2._fetch({
            url: path + "dashboard/set.json",
            data: {
                id: id,
                fields: JSON.stringify(fields)
            }
        });
    },

    setcontent: function(id, content, height){
        // call the dashboard/setcontent api endpoint and return the callback queue
        return dashboard_v2._fetch({
            type: "POST",
            url :  path+"dashboard/setcontent.json",
            data : {
                id: id,
                content: encodeURIComponent(content),
                height: height
            },
            dataType: 'json'
        });
    },
  
    clone: function(id) {
        return dashboard_v2._fetch({
            url: path + "dashboard/clone.json",
            data: {id: id}
        });
    },

    // AJAX UTILITIES
    // ---------------------

    /**
     * Send and test the response of an $.ajax request for error messages
     * 
     * Wrapper for $.ajax & tests for {success: false} in response
     * 
     * return standard $.ajax response
     * return failed $.ajax response if error message exists
     * 
     * @param: _fetch(settings)
     * @param: _fetch(url,[settings])
     * @see: https://api.jquery.com/jQuery.ajax/ for settings
     * @author: emrys@openenergymonitor.org
     */
    _fetch: function() {
        // call an api endpoint and return the callback queue
        var deferred = $.Deferred();
        var promise = deferred.promise();

        // if single object passed use that. else supply url and options
        var settings = arguments[0] || {};
        var jqxhr = null;
        
        // return rejected promise if no url passed
        if(!arguments[0]) deferred.reject(null, 'no url given');

        // if first parameter is string use that as the url
        if (typeof settings === 'string') {
            let url = arguments[0] || '';
            let settings = arguments[1] || {};
            jqxhr = $.ajax(url, settings);
        } else {
            jqxhr = $.ajax(settings);
        }

        // on ajax success check response for error message
        jqxhr.success(function(data, status, xhr) {
            // reject if data has property success set to false
            if (!data || (data.success && !data.success)) {
                deferred.reject(jqxhr, data.message || 'error');
            } else {
                deferred.resolve(data, status, xhr);
            }
        });

        // on ajax error return rejected promise
        jqxhr.error(function(jqXHR, status, error) {
            deferred.reject(jqXHR, status, error);
        });
        return promise;
    },


    _resizePageContainer: function(){
        return;
        var maxBottom = 0;
        // Loop through elements children to find & set the biggest height
        
        $("#page *").each(function(){
            // If this elements height is bigger than the maxBottom
            let $this = $(this);
            let offset = $this.offset();
            let offset_top = offset.top + $this.outerHeight() + 40;
            if (offset_top > maxBottom ) {
                // Set the maxBottom to this offset
                maxBottom = offset_top;
            }
        });
        
        // Set the container height
        $('#footer').css({
            position: 'absolute',
            top: maxBottom,
            width: '100%'
        })
    }
}