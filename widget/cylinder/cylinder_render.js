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

  function drawCylinder(ctx,cylBot,cylTop,width,height,temptype,unitend,decimals)
  {

    // console.log("Draw cylinder");
    if (!ctx) console.log("No CTX");
    if (!ctx) return;

    var midx = width / 2;
    var cylWidth = width - 8;
    var cylLeft = midx - (cylWidth/2);
    var topPos = midx;
    var botPos = height - 4 - (cylWidth/2);

    ctx.clearRect(0,0,width,500);
    cylTop = cylTop || 0;
    cylBot = cylBot || 0;
    ctx.strokeStyle = "#fff";
    ctx.lineWidth = 8;

    ctx.fillStyle = get_color(cylTop,temptype);
    ctx.beginPath();
    ctx.arc(midx,topPos,cylWidth/2,Math.PI,0,false);
    ctx.closePath();
    ctx.fill();

    var gradient = ctx.createLinearGradient(0, topPos, 0, botPos);
    gradient.addColorStop(0, get_color(cylTop,temptype));
    gradient.addColorStop(1, get_color(cylBot,temptype));
    ctx.fillStyle = gradient;
    ctx.fillRect(cylLeft, botPos, cylWidth, topPos-botPos);

    ctx.fillStyle = get_color(cylBot,temptype);
    ctx.beginPath();

    ctx.arc(midx,botPos,cylWidth/2,0,Math.PI,false);
    ctx.closePath();
    ctx.fill();

    ctx.beginPath();
    ctx.arc(midx,topPos,cylWidth/2,Math.PI,0,false);
    ctx.arc(midx,botPos,cylWidth/2,0,Math.PI,false);

    ctx.closePath();
    ctx.stroke();

    ctx.fillStyle = "#fff";
    ctx.textAlign    = "center";
    ctx.font = "bold "+((width/168)*30)+"px arial";

    if (isNaN(cylTop)){
    cylTop = 0;}
    else if(Number(decimals)>=0){ //specified decimals
    cylTop = cylTop.toFixed(decimals);}
    else { //automatic decimals
     if (cylTop>=100){
     cylTop = cylTop.toFixed(0);}
     else if (cylTop>=10){
     cylTop = cylTop.toFixed(1);}
     else if (cylTop<=-100){
     cylTop = cylTop.toFixed(0);}
     else if (cylTop<=-10){
     cylTop = cylTop.toFixed(1);}
     else{
     cylTop = cylTop.toFixed(2);}

     cylTop = parseFloat(cylTop);
    }

    if (isNaN(cylBot)){
    cylBot = 0;}
    else if(Number(decimals)>=0){ //specified decimals
    cylBot = cylBot.toFixed(decimals);}
    else { //automatic decimals
     if (cylBot>=100){
     cylBot = cylBot.toFixed(0);}
     else if (cylBot>=10){
     cylBot = cylBot.toFixed(1);}
     else if (cylBot<=-100){
     cylBot = cylBot.toFixed(0);}
     else if (cylBot<=-10){
     cylBot = cylBot.toFixed(1);}
     else{
     cylBot = cylBot.toFixed(2);}

     cylBot = parseFloat(cylBot);
    }
    var unit;
    if (temptype === "0") {
    unit = "ºC";
    } else {
    unit = "ºF";
    }
    if (unitend ==="0"){
      ctx.fillText(cylTop+unit,midx,topPos);
      ctx.fillText(cylBot+unit,midx,botPos+15);}
    if (unitend ==="1"){
      ctx.fillText(unit+cylTop,midx,topPos);
      ctx.fillText(unit+cylBot,midx,botPos+15);}
    if (unitend ==="2"){
      ctx.fillText(cylTop,midx,topPos);
      ctx.fillText(cylBot,midx,botPos+15);}
  }

function cylinder_draw()
{
  $(".cylinder").each(function(index)
  {
    var feedid1 = $(this).attr("topfeedid");
    var feedid2 = $(this).attr("botfeedid");
    var associd = [];
    if ((associd[feedid1] === undefined) || (associd[feedid2] === undefined)) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    var cylTop = associd[feedid1]["value"]*1;
    var cylBot = associd[feedid2]["value"]*1;
    var unitend = $(this).attr("unitend") || "0";
    var temptype= $(this).attr("temptype") || "0";
    var decimals = $(this).attr("decimals") || "-1";

    var id = "can-"+$(this).attr("id");
    drawCylinder(widgetcanvas[id],cylBot,cylTop,$(this).width(),$(this).height(),temptype,unitend,decimals);
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
