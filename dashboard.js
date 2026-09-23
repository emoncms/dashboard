// Every call returns a promise, so nothing here blocks the page.
//
//     dashboard_v2.list().done(function(data){ ... })

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
        const url = path + "dashboard/list.json";
        const options = {};
        const promise = dashboard_v2._fetch(url, options);
        promise.url = url;
        return promise;
    },

    set: function(column, id, value){
        var fields = {}; 
        fields[column] = value;
        return dashboard_v2._fetch({
                url: path + "dashboard/set.json",
                data: {
                    id: id,
                    fields: JSON.stringify(fields)
                }
            });
    },

    // document is the encoded dashboard document, see notes/EDITOR.md.
    // Encoded again because the server decodes the value a second time.
    setcontent: function(id, document, height){
        return dashboard_v2._fetch({
                type: "POST",
                url :  path+"dashboard/setcontent.json",
                data : {
                    id: id,
                    document: encodeURIComponent(document),
                    height: height
                },
                dataType: "json"
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
        if(!arguments[0]) deferred.reject(null, "no url given");

        // if first parameter is string use that as the url
        if (typeof settings === "string") {
            const url = arguments[0] || "";
            const settings = arguments[1] || {};
            jqxhr = $.ajax(url, settings);
        } else {
            jqxhr = $.ajax(settings);
        }


        // on ajax success check response for error message
        jqxhr.done(function(data, status, xhr) {
                // reject if data has property success set to false
                if (!data || data.hasOwnProperty("success") && data.success === false) {
                    deferred.reject(jqxhr, data.message || "error");
                } else {
                    deferred.resolve(data, status, xhr);
                }
            });

        // on ajax error return rejected promise
        jqxhr.fail(function(jqXHR, status, error) {
                deferred.reject(jqXHR, status, error);
            });

        return promise;
    }
};

