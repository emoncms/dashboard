/*
   All emon_widgets code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    Author: Trystan Lea: trystan.lea@googlemail.com
    If you have any questions please get in touch, try the forums here:
    http://openenergymonitor.org/emon/forum
 */
function addOption(widget, optionKey, optionType, optionName, optionHint, optionData){
  widget["options"    ].push(optionKey);
  widget["optionstype"].push(optionType);
  widget["optionsname"].push(optionName);
  widget["optionshint"].push(optionHint);
  widget["optionsdata"].push(optionData);
}

function cylinder_widgetlist()
{
  var widgets = {
    "cylinder":
    {
      "offsetx":-80,"offsety":-165,"width":160,"height":330,
      "menu":"Widgets",
      "options":[],
      "optionstype":[],
      "optionsname":[],
      "optionshint":[],
      "optionsdata":[]
    }
  }
  var decimalsDropBoxOptions = [
        [-1,   "Automatic"],
        [0,    "0"],
        [1,    "1"],
        [2,    "2"],
        [3,    "3"],
        [4,    "4"],
        [5,    "5"],
        [6,    "6"]
    ];
  var unitDropBoxOptions = [
          [0, "Back"],
          [1, "Front"],
          [2, "No display"]
        ];
    var tempDropBoxOptions = [        // Options for the type combobox. Each item is [typeID, "description"]
        [0,    "ºC"],
        [1,    "ºF"]
    ];
  addOption(widgets["cylinder"], "topfeedid",    "feedid",  _Tr("Feed Top"),      _Tr("Top feed value"),                []);
  addOption(widgets["cylinder"], "botfeedid",    "feedid",  _Tr("Feed Bottom"),   _Tr("Bottom feed value"),             []);
  addOption(widgets["cylinder"], "temptype",     "dropbox", _Tr("Temp unit"),     _Tr("Units of the choosen temp feed"),tempDropBoxOptions);
  addOption(widgets["cylinder"], "decimals",     "dropbox", _Tr("Decimals"),      _Tr("Decimals to show"),               decimalsDropBoxOptions);
  addOption(widgets["cylinder"], "unitend",      "dropbox", _Tr("Unit position"), _Tr("Where should the unit be shown"), unitDropBoxOptions);
  return widgets;
}

  function get_color(temperature,temptype)
  {
    if (temptype === "1") { 
    temperature = (temperature - 32) * (5 / 9); // Fahrenheit to Celsius
    }
    var red = (32+(temperature*3.95)).toFixed(0);
    var green = 40;
    var blue = (191-(temperature*3.65)).toFixed(0);
    return "rgb("+red+","+green+","+blue+")";
  }

  function draw_cylinder(ctx,cyl_bot,cyl_top,width,height,temptype,unitend,decimals)
  {

    // console.log("Draw cylinder");
    if (!ctx) console.log("No CTX");
    if (!ctx) return;

    var midx = width / 2;
    var cyl_width = width - 8;
    var cyl_left = midx - (cyl_width/2);
    var top_pos = midx;
    var bot_pos = height - 4 - (cyl_width/2);

    ctx.clearRect(0,0,width,500);
    cyl_top = cyl_top || 0;
    cyl_bot = cyl_bot || 0;
    ctx.strokeStyle = "#fff";
    ctx.lineWidth = 8;

    ctx.fillStyle = get_color(cyl_top);
    ctx.beginPath();
    ctx.arc(midx,top_pos,cyl_width/2,Math.PI,0,false);
    ctx.closePath();
    ctx.fill();

    var gradient = ctx.createLinearGradient(0, top_pos, 0, bot_pos);
    gradient.addColorStop(0, get_color(cyl_top));
    gradient.addColorStop(1, get_color(cyl_bot));
    ctx.fillStyle = gradient;
    ctx.fillRect(cyl_left, bot_pos, cyl_width, top_pos-bot_pos);

    ctx.fillStyle = get_color(cyl_bot,temptype);
    ctx.beginPath();
    ctx.arc(midx,bot_pos,cyl_width/2,0,Math.PI,false);
    ctx.closePath();
    ctx.fill();

    ctx.beginPath();
    ctx.arc(midx,top_pos,cyl_width/2,Math.PI,0,false);
    ctx.arc(midx,bot_pos,cyl_width/2,0,Math.PI,false);

    ctx.closePath();
    ctx.stroke();

    ctx.fillStyle = "#fff";
    ctx.textAlign    = "center";
    ctx.font = "bold "+((width/168)*30)+"px arial";

    if (isNaN(cyl_top)){
    cyl_top = 0;}
    else if(Number(decimals)>=0){ //specified decimals
    cyl_top = cyl_top.toFixed(decimals);}
    else { //automatic decimals
     if (cyl_top>=100){
     cyl_top = cyl_top.toFixed(0);}
     else if (cyl_top>=10){
     cyl_top = cyl_top.toFixed(1);}
     else if (cyl_top<=-100){
     cyl_top = cyl_top.toFixed(0);}
     else if (cyl_top<=-10){
     cyl_top = cyl_top.toFixed(1);}
     else{
     cyl_top = cyl_top.toFixed(2);}

     cyl_top = parseFloat(cyl_top);
    }

    if (isNaN(cyl_bot)){
    cyl_bot = 0;}
    else if(Number(decimals)>=0){ //specified decimals
    cyl_bot = cyl_bot.toFixed(decimals);}
    else { //automatic decimals
     if (cyl_bot>=100){
     cyl_bot = cyl_bot.toFixed(0);}
     else if (cyl_bot>=10){
     cyl_bot = cyl_bot.toFixed(1);}
     else if (cyl_bot<=-100){
     cyl_bot = cyl_bot.toFixed(0);}
     else if (cyl_bot<=-10){
     cyl_bot = cyl_bot.toFixed(1);}
     else{
     cyl_bot = cyl_bot.toFixed(2);}

     cyl_bot = parseFloat(cyl_bot);
    }
    if (temptype === "0") {
    unit = "ºC";
    } else {
    unit = "ºF";
    }
    if (unitend ==="0"){
      ctx.fillText(cyl_top+unit,midx,top_pos);
      ctx.fillText(cyl_bot+unit,midx,bot_pos+15);}
    if (unitend ==="1"){
      ctx.fillText(unit+cyl_top,midx,top_pos);
      ctx.fillText(unit+cyl_bot,midx,bot_pos+15);}
    if (unitend ==="2"){
      ctx.fillText(cyl_top,midx,top_pos);
      ctx.fillText(cyl_bot,midx,bot_pos+15);}
  }

function cylinder_draw()
{
  $(".cylinder").each(function(index)
  {
    var feedid1 = $(this).attr("topfeedid");
    var feedid2 = $(this).attr("botfeedid");
    if ((associd[feedid1] === undefined) || (associd[feedid2] === undefined)) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    var cyl_top = associd[feedid1]["value"]*1;
    var cyl_bot = associd[feedid2]["value"]*1;
    var unitend = $(this).attr("unitend") || "0";
    var temptype= $(this).attr("temptype") || "0";
    var decimals = $(this).attr("decimals") || "-1";

    var id = "can-"+$(this).attr("id");
    draw_cylinder(widgetcanvas[id],cyl_bot,cyl_top,$(this).width(),$(this).height(),temptype,unitend,decimals);
  });
}

function cylinder_init()
{
  setup_widget_canvas("cylinder");
}

function cylinder_slowupdate()
{
  cylinder_draw();
}

function cylinder_fastupdate()
{
  cylinder_draw();
}
