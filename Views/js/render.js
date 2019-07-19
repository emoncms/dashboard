/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:  http://openenergymonitor.org

  render.js goes through all the dashboard html elements that specify the dashboard widgets
  and inserts the dials, visualisations to be displayed inside the element.
  see designer.js for more information on the html element widget box model.

  render.js calls the render scripts of all the widgets which is where all the 
  individual widget render code is located.
*/

// Global page vars definition

// Array for all feed details by feed id
var associd = [];
var assocfeed = [];
// Array for smooth change values - creation of smooth dial widget
var assoc_curve = [];

var widgetcanvas = {};

var dialrate = 0.15;
var browserVersion = 999;
var fast_update_fps = 25;

var Browser = {
  Version : function()
  {
    var version = 999;
    if (navigator.appVersion.indexOf("MSIE") != -1)
      version = parseFloat(navigator.appVersion.split("MSIE")[1]);
    return version;
  }
}

// populate widgets variable with *_widgetlist from all dashboards
function render_widgets_init(widget){
  for (z in widget){
    var fname = widget[z]+"_widgetlist";
    var fn = window[fname];
    $.extend(widgets,fn());
  }
}

//start dashboard init and update processes
function render_widgets_start(){
  update(true);

  browserVersion = Browser.Version();
  if (browserVersion < 9) dialrate = 0.4;

  for (z in widget){
    var fname = widget[z]+"_init";
    var fn = window[fname];
    fn();
  }

  setInterval(function() { update(false); }, 5000);
  gpu_fast_update();
  //setInterval(function() { fast_update(); }, 100);
}

// GPU friendly fast update loop
function gpu_fast_update() { 
  setTimeout( 
   function() {
      window.requestAnimationFrame(gpu_fast_update);
      fast_update();
    }
  , 1000/fast_update_fps);
};

// update function
function update(first_time){
  var query = path + "feed/list.json?userid="+userid;
  if (apikey) query += "&apikey="+apikey;
  $.ajax(
  {
    type: "GET",
    url : query,
    dataType : 'json',
    async: !first_time,
    success : function(data){ 
      for (z in data){
        associd[data[z]['id']] = data[z];
        assocfeed[data[z]['tag']+":"+data[z]['name']] = data[z]['id'];
      }
      if (!first_time){
        for (z in widget){
          var fname = widget[z]+"_slowupdate";
          var fn = window[fname];
          fn();
        }
      }
    },
    error : function(){
          for (z in widget){ 
          var fname = widget[z]+"_isnonetwork";
          var fn = window[fname];
             if(typeof(fn) == 'function') {
             fn();
             }
         }
    }
  });
}

function fast_update(){
  if (redraw){ 
    for (z in widget){
      var fname = widget[z]+"_init";
      var fn = window[fname];
      fn();
    }
  }

  for (z in widget){
    var fname = widget[z]+"_fastupdate";
    var fn = window[fname];
    fn();
  }
  redraw = 0;
}
/**
 * returns a modified feed value. if values not found 0 is returned
 * @requires {Array} associd
 * @param {Number} feedid int a feed id
 * @param {Number} rate a rate value
 * @return {Number}
 */
function curve_value(feedid,rate){
    var val = 0;
    rate = rate || 1;
    if (typeof feedid !== 'undefined') {
        if (isNaN(assoc_curve[feedid]) || typeof assoc_curve[feedid] === 'undefined') {
            assoc_curve[feedid] = 0;
        }
        if (associd[feedid] && typeof associd[feedid] !== 'undefined') {
            let parsedAssocid = 0;
            if(associd[feedid]['value']) {
                parsedAssocid = parseFloat(associd[feedid]['value'])
            }
            assoc_curve[feedid] = assoc_curve[feedid] + ((parsedAssocid - assoc_curve[feedid]) * rate);
        }
    }
    if(typeof assoc_curve[feedid] !== 'undefined') {
        val = assoc_curve[feedid];
    }
    return val;
}

function setup_widget_canvas(elementclass){
  $('.'+elementclass).each(function(index){
    var widgetId = $(this).attr("id");
    var width = $(this).width();
    var height = $(this).height();
    var canvas = $(this).children('canvas');
    var canvasid = "can-"+widgetId;

    // 1) Create canvas if it does not exist
    if (!canvas[0]){
      $(this).html('<canvas id="'+canvasid+'"></canvas>');
    }

    // 2) Resize canvas if it needs resizing
    if (canvas.attr("width") != width) canvas.attr("width", width);
    if (canvas.attr("height") != height) canvas.attr("height", height);

    var canvas = document.getElementById(canvasid);
    if (browserVersion != 999) {
      canvas.setAttribute('width', width);
      canvas.setAttribute('height', height);
      if ( typeof G_vmlCanvasManager != "undefined") G_vmlCanvasManager.initElement(canvas);
    }
    // 3) Get and store the canvas context
    widgetcanvas[canvasid] = canvas.getContext("2d");
  });
}
