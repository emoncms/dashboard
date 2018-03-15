/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    Author: Trystan Lea: trystan.lea@googlemail.com
    If you have any questions please get in touch, try the forums here:
    http://openenergymonitor.org/emon/forum
 */
function addOption(widget, optionKey, optionType, optionName, optionHint, optionData)
{
    widget["options"    ].push(optionKey);
    widget["optionstype"].push(optionType);
    widget["optionsname"].push(optionName);
    widget["optionshint"].push(optionHint);
    widget["optionsdata"].push(optionData);
}
	var StyleOptions = [
    [1, _Tr("With colour gradients")],
    [0, _Tr("Without colour gradients")]
    ];

function led_widgetlist()
{
  var widgets = {
    "led":
    {
      "offsetx":-40,"offsety":-40,"width":80,"height":80,
      "menu":"Widgets",
      "options":    [],
      "optionstype":[],
      "optionsname":[],
      "optionshint":[],
      "optionsdata":[]
    }
  };
  addOption(widgets["led"], "feedid",      "feedid",         _Tr("Feed"),       _Tr("Feed value"),          []);
  addOption(widgets["led"], "ledstyle",       "dropbox",     _Tr("Style"),      _Tr("Display style"),       StyleOptions);
  return widgets;
}

function draw_led(circle,status,ledstyle){
  if (!circle) return;

  var width = circle.canvas.width;
  var height = circle.canvas.height;
  var borderx = Math.min(40, Math.floor(width/2));
  var bordery = Math.min(40, Math.floor(height/2));
  var dimension = Math.max(10, Math.min(width-borderx, height-bordery));
  var offsetx = Math.floor((width - dimension) / 2.0);
  var offsety = Math.floor((height - dimension) / 2.0);

  circle.clearRect(0,0,width,height);
  if (ledstyle ==="1"){
  var radgrad = circle.createRadialGradient(width/2,height/2,0,width/2,height/2,dimension/2);
    if (status==0) {                   // red
      radgrad.addColorStop(0, "#F75D59");
      radgrad.addColorStop(0.9, "#C11B17");
    } else if (status>0 && status <=1) {            // green
     radgrad.addColorStop(0, "#A7D30C");
      radgrad.addColorStop(0.9, "#019F62");
    } else if (status>1 && status <=2) {           // grey
      radgrad.addColorStop(0, "#736F6E");
      radgrad.addColorStop(0.9, "#4A4344");
    } else if (status>2 && status <=3) { 		  //Blue
      radgrad.addColorStop(0, "#00C9FF");
      radgrad.addColorStop(0.9, "#00B5E2");
    } else if (status>3 && status <=4) {		  // Purple
      radgrad.addColorStop(0, "#FF5F98");
      radgrad.addColorStop(0.9, "#FF0188");
    } else if (status>4 && status <=5)   {         // yellow
      radgrad.addColorStop(0, "#F4F201");
      radgrad.addColorStop(0.9, "#E4C700");
    } else {					  // Black
      radgrad.addColorStop(0, "#000000");
      radgrad.addColorStop(0.9, "#000000");
    }

    radgrad.addColorStop(1, 'rgba(1,159,98,0)');
    // draw shapes
    circle.fillStyle = radgrad;
    circle.fillRect(offsetx,offsety,dimension,dimension);
  }
  
  if (ledstyle ==="0"){
      if (status==0) {			// red
      circle.fillStyle = "#C11B17";
    } else if (status==1) {			// green
      circle.fillStyle = "#019F62";
    } else if (status==2) {			// grey
      circle.fillStyle = "#4A4344";
    } else if (status==3) {			//Blue
     circle.fillStyle = "#00B5E2";
    } else if (status ==4) {		// Purple
     circle.fillStyle = "#FF0188";
    } else if (status==5)  {		// yellow
      circle.fillStyle = "#E4C700";
    } else {				// Black
      circle.fillStyle = "#000000";
    }
    circle.beginPath();
    circle.arc(width/2,height/2,dimension/2, 0,Math.PI * 2);
    circle.closePath();
    circle.fill()
  }
}

function led_draw() {
  $(".led").each(function(index)
  {
    var feedid = $(this).attr("feedid");
    if (assocfeed[feedid]!=undefined) feedid = assocfeed[feedid]; // convert tag:name to feedid
    if (associd[feedid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    var val = associd[feedid]["value"] * 1;
    var ledstyle = $(this).attr("ledstyle")|| "1";
    var id = "can-"+$(this).attr("id");
    draw_led(widgetcanvas[id],val,ledstyle);
  }
  );
}

function led_init()
{
  setup_widget_canvas("led");
}

function led_slowupdate() {
  led_draw();
}

function led_fastupdate() {}
