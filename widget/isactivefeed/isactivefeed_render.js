/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
    Widget initially created by Aymeric Thibaut
 */
	var shapeOptionsIsActive = [
    [2, _Tr("Circle")],
    [1, _Tr("Triangle &#9650;")],
    [5, _Tr("Triangle &#9654;")],
    [6, _Tr("Triangle &#9660;")],
    [7, _Tr("Triangle &#x25C0;")],
    [0, _Tr("Square")],
    [3, _Tr("Star 5 spikes")],
    [4, _Tr("Star 6 spikes")]
    ];

function isactivefeed_widgetlist()
{
  var widgets = {
    "isactivefeed":
    {
      "offsetx":0,"offsety":0,"width":80,"height":160,
      "menu":"Widgets",
      "options":    [],
      "optionstype":[],
      "optionsname":[],
      "optionshint":[],
      "optionsdata":[]
    }
  };
  addOption(widgets["isactivefeed"], "feedid",      "feedid",         _Tr("Feed"),       _Tr("Feed value"),                                         []);
  addOption(widgets["isactivefeed"], "threshold1",  "value",          _Tr("Threshold1"), _Tr("Threshold1 in seconds"),                              []);
  addOption(widgets["isactivefeed"], "threshold2",  "value",          _Tr("Threshold2"), _Tr("Threshold2 in seconds"),                              []);
  addOption(widgets["isactivefeed"], "colour1",     "colour_picker",  _Tr("Colour1"),    _Tr("Colour for range below Threshold1"),                  []);
  addOption(widgets["isactivefeed"], "colour2",     "colour_picker",  _Tr("Colour2"),    _Tr("Colour for range between Threshold1 and Threshold2"), []);
  addOption(widgets["isactivefeed"], "colour3",     "colour_picker",  _Tr("Colour3"),    _Tr("Colour for range above Threshold2"),                  []);
  addOption(widgets["isactivefeed"], "shapetype",   "dropbox",        _Tr("Shape"),      _Tr("Shape"),                                              shapeOptionsIsActive);
  return widgets;
}
function drawStar(ctx, cx, cy, spikes, outerRadius, innerRadius, colour) {
    var rot = Math.PI / 2 * 3;
    var x = cx;
    var y = cy;
    var step = Math.PI / spikes;
    var i;

    ctx.strokeSyle = "#000";
    ctx.beginPath();
    ctx.moveTo(cx, cy - outerRadius);

    for (i = 0; i < spikes; i++) {
        x = cx + Math.cos(rot) * outerRadius;
        y = cy + Math.sin(rot) * outerRadius;
        ctx.lineTo(x, y);
        rot += step;

        x = cx + Math.cos(rot) * innerRadius;
        y = cy + Math.sin(rot) * innerRadius;
        ctx.lineTo(x, y);
        rot += step;
    }
    ctx.lineTo(cx, cy - outerRadius);
    ctx.closePath();
    ctx.lineWidth=1;
    ctx.strokeStyle= colour;
    ctx.stroke();
    ctx.fillStyle= colour;
    ctx.fill();

}

function draw_isactivefeed(shape,feedstatus, colour3, colour2, colour1, shapetype){
  if (!shape) {return;}
  var width = shape.canvas.width;
  var height = shape.canvas.height;
  var borderx = Math.min(40, Math.floor(width/2));
  var bordery = Math.min(40, Math.floor(height/2));
  var dimension = Math.max(10, Math.min(width-borderx, height-bordery));
  var offsetx = Math.floor((width - dimension) / 2.0);
  var offsety = Math.floor((height - dimension) / 2.0);
  shape.clearRect(0,0,width,height);
  var fillcolor;
      if (feedstatus===0) {
      fillcolor= colour3;
    } else if (feedstatus===1) {
      fillcolor= colour2;
    } else if (feedstatus===2) {
      fillcolor= colour1;
    } else {
      fillcolor= "#000000";
    }
if (shapetype==="2"){ // Circle

      shape.beginPath();
      shape.fillStyle=fillcolor;
      shape.arc(width/2, height/2, dimension/2, 0, 2 * Math.PI);
      shape.fill();
}

   if (shapetype==="1"){ //Triangle 1

      shape.beginPath();
      shape.fillStyle=fillcolor;
      shape.moveTo(offsetx, offsety+dimension);
      shape.lineTo(offsetx+dimension, offsety+dimension);
      shape.lineTo(offsetx+dimension/2,offsety);
      shape.fill();}

  if (shapetype==="5"){ //Triangle 2

      shape.beginPath();
      shape.fillStyle=fillcolor;
      shape.moveTo(offsetx,offsety);
      shape.lineTo(offsetx+dimension, offsety+dimension/2);
      shape.lineTo(offsetx,offsety+dimension);
      shape.fill();}

  if (shapetype==="6"){ //Triangle 3

      shape.beginPath();
      shape.fillStyle=fillcolor;
      shape.moveTo(offsetx, offsety);
      shape.lineTo(offsetx+dimension, offsety);
      shape.lineTo(offsetx+dimension/2,offsety+dimension);
      shape.fill();}

  if (shapetype==="7"){ //Triangle 4

      shape.beginPath();
      shape.fillStyle=fillcolor;
      shape.moveTo(offsetx+dimension,offsety);
      shape.lineTo(offsetx, offsety+dimension/2);
      shape.lineTo(offsetx+dimension,offsety+dimension);
      shape.fill();}

  if (shapetype==="0"){ //Square

      shape.fillStyle=fillcolor;
      shape.fillRect(offsetx,offsety,dimension,dimension);}

  if (shapetype==="3"){ //Star 5 spikes

      drawStar(shape, width/2, height/2, 5, dimension/2,dimension/4, fillcolor);}

  if (shapetype==="4"){ //Star 6 spikes

      drawStar(shape, width/2, height/2, 6, dimension/2,dimension/4, fillcolor);}
}

function isactivefeed_isnonetwork()
{
  $(".isactivefeed").each(function(index)
  {
    var id = "can-"+$(this).attr("id");
    var colour3 = $(this).attr("colour3")|| "#F70511";
    var colour2 = $(this).attr("colour2")|| "#FF8425";
    var colour1 = $(this).attr("colour1")|| "#019F62";
    if (colour3.indexOf("#") === -1){// Fix missing "#" on colour if needed
    colour3 = "#" + colour3;}
    if (colour2.indexOf("#") === -1){
    colour2 = "#" + colour2;}
    if (colour1.indexOf("#") === -1){
    colour1 = "#" + colour1;}
    var shapetype = $(this).attr("shapetype")|| "2";
    var width = widgetcanvas[id].canvas.width;
    var height = widgetcanvas[id].canvas.height;
    var borderx = Math.min(40, Math.floor(width/2));
    var bordery = Math.min(40, Math.floor(height/2));
    var dimension = Math.max(10, Math.min(width-borderx, height-bordery));
    var offsetx = Math.floor((width - dimension) / 2.0);
    var offsety = Math.floor((height - dimension) / 2.0);

    draw_isactivefeed(widgetcanvas[id], 0, colour3, colour2, colour1, shapetype);

    widgetcanvas[id].font = "bold "+ dimension +"px Arial";
    widgetcanvas[id].fillStyle = "#000000";
    widgetcanvas[id].fillText("!", (offsetx+dimension/2)*0.85, offsety+dimension*7/8);

  });
}

function isactivefeed_draw() {
  $(".isactivefeed").each(function(index)
  {
    var feedid = $(this).attr("feedid");
    if (assocfeed[feedid]!=undefined) feedid = assocfeed[feedid]; // convert tag:name to feedid
    var limit1 = $(this).attr("threshold1")*1 || 60;
    var limit2 = $(this).attr("threshold2")*1 || 120;
    var colour3 = $(this).attr("colour3")|| "#F70511";
    var colour2 = $(this).attr("colour2")|| "#FF8425";
    var colour1 = $(this).attr("colour1")|| "#019F62";
    var shapetype = $(this).attr("shapetype")|| "2";
    var val=0; 
    var delay=1000;

    if (associd[feedid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    delay =  Math.round(Date.now() /1000 - offsetofTime - associd[feedid]["time"]);

    if (delay > limit2){
        val=0;}
    else if (delay > limit1){
        val=1;}
    else {
        val=2;}
    if (colour3.indexOf("#") === -1){// Fix missing "#" on colour if needed
    colour3 = "#" + colour3;}
    if (colour2.indexOf("#") === -1){
    colour2 = "#" + colour2;}
    if (colour1.indexOf("#") === -1){
    colour1 = "#" + colour1;}
    var id = "can-"+$(this).attr("id");

    draw_isactivefeed(widgetcanvas[id], val, colour3, colour2, colour1, shapetype);
  }
  );
}
function isactivefeed_init()
{
  setup_widget_canvas("isactivefeed");
}

function isactivefeed_slowupdate() {isactivefeed_draw();}

function isactivefeed_fastupdate() {}
