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

// Convenience function for shoving things into the widget object
// I'm not sure about calling optionKey "optionKey", but I don't want to just use "options" (because that's what this whole function returns), and it's confusing enough as it is.
function addOption(widget, optionKey, optionType, optionName, optionHint, optionData){
  widget["options"    ].push(optionKey);
  widget["optionstype"].push(optionType);
  widget["optionsname"].push(optionName);
  widget["optionshint"].push(optionHint);
  widget["optionsdata"].push(optionData);
}

function dial_widgetlist(){
  var widgets =
  {
    "dial":
    {
      "offsetx":-80,"offsety":-80,"width":160,"height":160,
      "menu":"Widgets",
      "options":    [],
      "optionstype":[],
      "optionsname":[],
      "optionshint":[],
      "optionsdata":[]

    }
  };

  var typeDropBoxOptions = [        // Options for the type combobox. Each item is [typeID, "description"]
          [10,   _Tr("Black <-> White, Zero at left")],
          [1,    _Tr("Red <-> Green, Zero at center")],
          [2,    _Tr("Green <-> Red, Zero at left")],
          [3,    _Tr("Green <-> Red, Zero at center")],
          [4,    _Tr("Red <-> Green, Zero at left")],
          [5,    _Tr("Red <-> Green, Zero at center")],
          [6,    _Tr("Green center <-> orange edges, Zero at center")],
          [9,    _Tr("Red <-> Dark Red, Zero at left")],
          [11,   _Tr("Blue <-> Red, Zero at upper-left")],
          [8,    _Tr("Light blue <-> Red, Zero at mid-left")],
          [12,   _Tr("Light <-> dark red, Zero at left")],
          [13,   _Tr("Light <-> dark orange, Zero at left")],
          [14,   _Tr("Light <-> dark yellow, Zero at left")],
          [0,    _Tr("Light <-> dark green, Zero at left")],
          [18,   _Tr("Light <-> dark lime, Zero at left")],
          [19,   _Tr("Light <-> dark mint, Zero at left")],
          [15,   _Tr("Light <-> dark cyan, Zero at left")],
          [7,    _Tr("Light <-> dark blue, Zero at left")],
          [20,   _Tr("Light <-> dark royal blue, Zero at left")],
          [16,   _Tr("Light <-> dark purple, Zero at left")],
          [17,   _Tr("Light <-> dark pink, Zero at left")],
          [21,   _Tr("Rainbow!, Zero at left")],
          [22,   _Tr("Reverse Rainbow!, Zero at left")],
          [23,   _Tr("Light <-> dark grey, alternating, Zero at left")]
        ];

  var graduationDropBoxOptions = [
          [1, _Tr("Yes")],
          [0, _Tr("No")]
        ];
  var decimalsDropBoxOptions = [
        [-1,   _Tr("Automatic")],
        [0,    "0"],
        [1,    "1"],
        [2,    "2"],
        [3,    "3"],
        [4,    "4"],
        [5,    "5"],
        [6,    "6"]
    ];
  var unitDropBoxOptions = [
          [0, _Tr("Back")],
          [1, _Tr("Front")]
        ];

  var displayminmaxDropBoxOptions = [
          [0, _Tr("No")],
          [1, _Tr("Yes")]
        ];

  addOption(widgets["dial"], "feedid",        "feedid",  _Tr("Feed"),         _Tr("Feed value"),                                                            []);
  addOption(widgets["dial"], "max",           "value",   _Tr("Max value"),    _Tr("Max value to show"),                                                     []);
  addOption(widgets["dial"], "scale",         "value",   _Tr("Scale"),        _Tr("Value is multiplied by scale before display"),                           []);
  addOption(widgets["dial"], "units",         "value",   _Tr("Units"),        _Tr("Units to show"),                                                         []);
  addOption(widgets["dial"], "decimals",      "dropbox", _Tr("Decimals"),     _Tr("Decimals to show"),                                                      decimalsDropBoxOptions);
  addOption(widgets["dial"], "offset",        "value",   _Tr("Offset"),       _Tr("Static offset. Subtracted from value before computing needle position"), []);
  addOption(widgets["dial"], "type",          "dropbox", _Tr("Type"),         _Tr("Type to show"),                                                          typeDropBoxOptions);
  addOption(widgets["dial"], "graduations",   "dropbox", _Tr("Graduations"),  _Tr("Should the graduation limits be shown"),                                 graduationDropBoxOptions);
  addOption(widgets["dial"], "unitend",       "dropbox", _Tr("Unit position"),_Tr("Where should the unit be shown"),                                        unitDropBoxOptions);
  addOption(widgets["dial"], "displayminmax", "dropbox", _Tr("Min / Max ?"),  _Tr("Display Min. and Max. ?"),                                               displayminmaxDropBoxOptions);
  addOption(widgets["dial"], "minvaluefeed",  "feedid",  _Tr("Min. feed"),    _Tr("The feed for the minimum value"),                                        []);
  addOption(widgets["dial"], "maxvaluefeed",  "feedid",  _Tr("Max. feed"),    _Tr("The feed for the maximum value"),                                        []);

  return widgets;
}

function deg_to_radians(deg){
  return deg * (Math.PI / 180);
}
function polar_to_cart(mag, ang, xOff, yOff){
  ang = deg_to_radians(ang);
  var x = mag * Math.cos(ang) + xOff;
  var y = mag * Math.sin(ang) + yOff;
  return [x, y];
}
  function round1decimal(x) {
    var Ergebnis = Math.round(x * 10) / 10 ;
    return Ergebnis;
  }
  
  function round2decimal(x) {
    var Ergebnis = Math.round(x * 100) / 100 ;
    return Ergebnis;
  }
// X, Y are the center coordinates of the canvas
function draw_gauge(ctx,canvasid,x,y,width,height,position,maxvalue,units,decimals,type,offset,graduationBool,unitend,displayminmax,minvaluefeed,maxvaluefeed){
  if (!ctx) {return;}

  // if (1 * maxvalue) == false: 3000. Else 1 * maxvalue
  maxvalue = 1 * maxvalue || 3000;
  // if units == false: "". Else units
  units = units || "";
  displayminmax = displayminmax || "0";
  offset = 1*offset || 0;
  var val = position;
  var val2 = minvaluefeed;
  var val3 = maxvaluefeed;
  position = position-offset;
  minvaluefeed = minvaluefeed-offset;
  maxvaluefeed = maxvaluefeed-offset;

  var size = 0;
  if (width<height) {size = width/2;}
  else {size = height/2;}
  if(displayminmax==="1"){size = size - (size*0.13/2);}
  else {size = size - (size*0.058/2);}

  x = width/2;
  y = height/2;

  ctx.clearRect(0,0,width,height);

  if (!position) {position = 0;}

  var angleOffset = 0;
  var segment = ["#c0e392","#9dc965","#87c03f","#70ac21","#378d42","#046b34"];

  type = type || "0";

  // Depending on type the start value is calculated.
  // The maximum value is defined via settings and is defined as the value to reach needle's end limit.
  // Depending on dial's type start limit is either 0 or a negative value.
  // Note: Should consider a type which enables defiable min value in futur extension.  
  
  if (type === "0"){ // Standard dial from 0 to maxvalue if offset is not set.
    // TODO: seperate between needle position at maximum/minimum and the value displayed.
    // TODO: Do we need to limit the value being displayed? Only needle position should be limited.
  }
  else if (type === "1"){
    angleOffset = -0.75;
    segment = ["#e61703","#ff6254","#ffa29a","#70ac21","#378d42","#046b34"];
  }
  else if (type === "2"){
    segment = ["#046b34","#378d42","#87c03f","#f8a01b","#f46722","#bf2025"];
  }
  else if (type === "3"){
    angleOffset = -0.75;
    segment = ["#046b34","#378d42","#87c03f","#f8a01b","#f46722","#bf2025"];
  }
  else if (type === "4"){
    segment = ["#bf2025","#f46722","#f8a01b","#87c03f","#378d42","#046b34"];
  }
  else if (type === "5"){
    angleOffset = -0.75;
    segment = ["#bf2025","#f46722","#f8a01b","#87c03f","#378d42","#046b34"];
  }
  else if (type === "6"){
    angleOffset = -0.75;
    segment = ["#f46722","#f8a01b","#87c03f","#87c03f","#f8a01b","#f46722"];
  }
  else if (type === "7"){
    segment = ["#a7cbe2","#68b7eb","#0d97f3","#0f81d0","#0c6dae","#08578e"];
  }
  else if (type === "8"){  //temperature dial blue-red, first segment blue should mean below freezing C
    angleOffset = -0.25;
    segment = ["#b7beff","#ffd9d9","#ffbebe","#ff9c9c","#ff6e6e","#ff3d3d"];
  }
  else if (type === "9"){  //temperature dial blue-red, first segment blue should mean below freezing C
    angleOffset = 0;
    segment = ["#e94937","#da4130","#c43626","#ad2b1c","#992113","#86170a"];
  }
  else if (type === "10"){ //light: from dark grey to white
    segment = ["#202020","#4D4D4D","#7D7D7D","#EEF0F3","#F7F7F7", "#FFFFFF"];
  }
  else if (type === "11"){  //temperature dial blue-red, first 2 segments blue should mean below freezing C
    angleOffset = -0.5;
    segment = ["#0d97f3","#a7cbe2","#ffbebe","#ff8383","#ff6464","#ff3d3d"];
  }
  else if (type === "12"){ //  - from light to dark red
    if (position<0){position = 0;}
    segment = ["#FFCCCC", "#FFA3A3", "#FF7A7A", "#FF5151", "#FF2828", "#FF0000"];
  }
  else if (type === "13"){ //  - from light to dark orange
    if (position<0){position = 0;}
    segment = ["#FFE5CC", "#FFD0A3", "#FFBC7A", "#FFA851", "#FF9428", "#FF8000"];
  }
  else if (type === "14"){ //  - from light to dark yellow
    if (position<0){position = 0;}
    segment = ["#FFFFCC", "#FFFFA3", "#FFFF7A", "#FFFF51", "#FFFF28", "#FFFF00"];
  }
  else if (type === "15"){ //  - from light to dark cyan
    if (position<0){position = 0;}
    segment = ["#CCFFFF", "#A3FFFF", "#7AFFFF", "#51FFFF", "#28FFFF", "#00FFFF"];
  }
  else if (type === "16"){ //  - from light to dark purple
    if (position<0){position = 0;}
    segment = ["#E5CCFF", "#D0A3FF", "#BC7AFF", "#A851FF", "#9428FF", "#8000FF"];
  }
  else if (type === "17"){ //  - from light to dark pink
    if (position<0){position = 0;}
    segment = ["#FFCCFF", "#FFA3FF", "#FF7AFF", "#FF51FF", "#FF28FF", "#FF00FF"];
  }
  else if (type === "18"){ //  - from light to dark lime
    if (position<0){position = 0;}
    segment = ["#CCFFCC", "#A3FFA3", "#7AFF7A", "#51FF51", "#28FF28", "#00FF00"];
  }
  else if (type === "19"){ //  - from light to dark mint
    if (position<0){position = 0;}
    segment = ["#EEFCF5", "#E0F9ED", "#D2F7E6", "#C5F4DF", "#B7F2D8", "#AAF0D1"];
  }
  else if (type === "20"){ //  - from light to dark royal blue
    if (position<0){position = 0;}
    segment = ["#CCCCFF", "#A3A3FF", "#7A7AFF", "#5151FF", "#2828FF", "#0000FF"];
  }
  else if (type === "21"){ //  - rainbow!
    if (position<0){position = 0;}
    segment = ["#FF0000", "#FF8000", "#FFFF00", "#00FF00", "#0000FF", "#8000FF"];
  }
  else if (type === "22"){ //  - reverse rainbow!
    if (position<0){position = 0;}
    segment = ["#8000FF", "#0000FF", "#00FF00", "#FFFF00", "#FF8000", "#FF0000"];
  }
  else if (type === "23")  // can be used to emulate a barometer and display a pressure
  {
    if (position<0){position = 0;}
    segment = ["#C0C0C0","#868686","#C0C0C0","#868686","#C0C0C0","#868686"];
  }

  // needle values and their corresponding direction
  // South West (limit start) a = 1.75
  // West: .. ............... a = 1.5
  // North West: ............ a = 1.25
  // North: ................. a = 1
  // North East: ............ a = 0.75
  // East: .................. a = 0.5
  // South East (limit stop)  a = 0.25
  //
  // Calculation explanation:
  // Total needle space = 1.75 - 0.25 = 1.5 (from above).
  // (1.5 + angleOffset) = needle space from 0 to maxvalue (because angleOffset is negative).
  // (position / maxvalue) = fraction of how far round the needle space from 0 to maxvalue we need to be.
  // The rest follows from these.
  var needle = 1.75 - ((position/maxvalue) * (1.5+angleOffset)) + angleOffset;
  needle = Math.min(needle, 1.75);
  needle = Math.max(needle, 0.25);

  var minindicator = 1.75 - ((minvaluefeed/maxvalue) * (1.5+angleOffset)) + angleOffset;
  minindicator = Math.min(minindicator, 1.75);
  minindicator = Math.max(minindicator, 0.25);

  var maxindicator = 1.75 - ((maxvaluefeed/maxvalue) * (1.5+angleOffset)) + angleOffset;
  maxindicator = Math.min(maxindicator, 1.75);
  maxindicator = Math.max(maxindicator, 0.25);

  width = 0.785;
  var c=3*0.785;
  var pos = 0;
  var inner = size * 0.48;

  // Segments
  for (var z in segment){
    ctx.fillStyle = segment[z];
    ctx.beginPath();
    ctx.arc(x,y,size,c+pos,c+pos+width+0.01,false);
    ctx.lineTo(x,y);
    ctx.closePath();
    ctx.fill();
    pos += width;
  }
  pos -= width;
  ctx.lineWidth = (size*0.058).toFixed(0);
  pos += width;
  ctx.strokeStyle = "#fff";
  ctx.beginPath();
  ctx.arc(x,y,size,c,c+pos,false);
  ctx.lineTo(x,y);
  ctx.closePath();
  ctx.stroke();

  ctx.fillStyle = "#666867";
  ctx.beginPath();
  ctx.arc(x,y,inner,0,Math.PI*2,true);
  ctx.closePath();
  ctx.fill();

  ctx.lineWidth = (size*0.052).toFixed(0);
  //---------------------------------------------------------------
  ctx.beginPath();
  ctx.moveTo(x+Math.sin(Math.PI*needle-0.2)*inner,y+Math.cos(Math.PI*needle-0.2)*inner);
  ctx.lineTo(x+Math.sin(Math.PI*needle)*size,y+Math.cos(Math.PI*needle)*size);
  ctx.lineTo(x+Math.sin(Math.PI*needle+0.2)*inner,y+Math.cos(Math.PI*needle+0.2)*inner);
  ctx.arc(x,y,inner,1-(Math.PI*needle-0.2),1-(Math.PI*needle+5.4),true);
  ctx.closePath();
  ctx.fill();
  ctx.stroke();
  

  
  if (isNaN(val)){
  val = 0;}
  else if(Number(decimals)>=0){ //specified decimals
  val = val.toFixed(decimals);}
  else { //automatic decimals
    if (val>=100){
    val = val.toFixed(0);}
    else if (val>=10){
    val = val.toFixed(1);}
    else if (val<=-100){
    val = val.toFixed(0);}
    else if (val<=-10){
    val = val.toFixed(1);}
    else{
    val = val.toFixed(2);}

    val = parseFloat(val);
  }
  
  if (isNaN(val2)){
  val2 = 0;}
  else if(Number(decimals)>=0){ //specified decimals
  val2 = val2.toFixed(decimals);}
  else { //automatic decimals
    if (val2>=100){
    val2 = val2.toFixed(0);}
    else if (val2>=10){
    val2 = val2.toFixed(1);}
    else if (val2<=-100){
    val2 = val2.toFixed(0);}
    else if (val2<=-10){
    val2 = val2.toFixed(1);}
    else{
    val2 = val2.toFixed(2);}

    val2 = parseFloat(val2);
  }
  
  if (isNaN(val3)){
  val3 = 0;}
  else if(Number(decimals)>=0){ //specified decimals
  val3 = val3.toFixed(decimals);}
  else { //automatic decimals
    if (val3>=100){
    val3 = val3.toFixed(0);}
    else if (val3>=10){
    val3 = val3.toFixed(1);}
    else if (val3<=-100){
    val3 = val3.toFixed(0);}
    else if (val3<=-10){
    val3 = val3.toFixed(1);}
    else{
    val3 = val3.toFixed(2);}

    val3 = parseFloat(val3);
  }
    
  var dialtext;
  if (unitend ==="0"){
  dialtext=val+units;}
  if (unitend ==="1"){
  dialtext=units+val;}
  
  var textsize = (size / (dialtext.length+2)) * 6;
  
  ctx.fillStyle = "#fff";
  ctx.textAlign = "center";
  ctx.font = "bold "+(textsize*0.26)+"px arial";
  ctx.fillText(dialtext,x,y+(textsize*0.125));
  ctx.fillStyle = "#000";
  var spreadAngle = 32;

  if (graduationBool === "1"){
    ctx.font = "bold "+(size*0.22)+"px arial";

    var posStrt = polar_to_cart(size/1.15, 90+spreadAngle, x, y);
    var posStop = polar_to_cart(size/1.15, 90-spreadAngle, x, y);

    ctx.save();
    ctx.translate(posStrt[0], posStrt[1]);
    ctx.rotate(deg_to_radians(-45));
   
    // graduation text - start limit
    // Calculation similar to the `needle` calculation above
    var start_limit = round1decimal((angleOffset * (maxvalue / (1.5 + angleOffset))) + offset);
    if (unitend ==="0"){
    ctx.fillText(""+start_limit+units, 0, 0);}
    if (unitend ==="1"){
    ctx.fillText(""+units+start_limit, 0, 0);}
    // Since we've translated the entire context, the coords we want to draw at are now at [0,0]  
    ctx.restore();
    
    ctx.save(); // each ctx.save is only good for one restore, apparently.
    ctx.translate(posStop[0], posStop[1]);
    ctx.rotate(deg_to_radians(45));
    
    // graduation text - end limit
    var end_limit = round1decimal(offset+maxvalue);
    if (unitend ==="0"){
    ctx.fillText(""+end_limit+units, 0, 0);}
    if (unitend ==="1"){
    ctx.fillText(""+units+end_limit, 0, 0);}
    ctx.restore();


    if(displayminmax==="1"){ //display min max circle

      ctx.beginPath();
      ctx.arc(x+Math.sin(Math.PI*minindicator)*size,y+Math.cos(Math.PI*minindicator)*size,size*0.052,0,2*Math.PI,true); // draw min circle
      ctx.closePath();
      ctx.fill();
      ctx.stroke();

      ctx.beginPath();
      ctx.arc(x+Math.sin(Math.PI*maxindicator)*size,y+Math.cos(Math.PI*maxindicator)*size,size*0.052,0,2*Math.PI,true); // draw max circle
      ctx.closePath();
      ctx.fill();
      ctx.stroke();

      var canvas = document.getElementById(canvasid);
      var cw=canvas.width;
      var ch=canvas.height;
      var offsetX,offsetY;
      var mouseX,mouseY;
      var h;
      var dx,dy;

      var tt1 = document.getElementById(canvas.id + "-tooltip-1");
      var tt2 = document.getElementById(canvas.id + "-tooltip-2");

      function reOffset(){
       var BB=canvas.getBoundingClientRect();
       offsetX=BB.left;
       offsetY=BB.top;
      };

      reOffset();
      window.onscroll=function(e){ reOffset(); };
      window.onresize=function(e){ reOffset(); };

      var hotspots=[ // declare hotspots in order to active associated tooltips
      {xspot:(x+Math.sin(Math.PI*minindicator)*size),yspot:(y+Math.cos(Math.PI*minindicator)*size),radius:(size*0.052),tip: val2},
      {xspot:(x+Math.sin(Math.PI*maxindicator)*size),yspot:(y+Math.cos(Math.PI*maxindicator)*size),radius:(size*0.052),tip: val3},
      ];



      function handleMouseMove(e){
       e.preventDefault();
       e.stopPropagation();

       mouseX=parseInt(e.clientX-offsetX);
       mouseY=parseInt(e.clientY-offsetY);

       h=hotspots[0];
       dx=mouseX-h.xspot;
       dy=mouseY-h.yspot;
       if(dx*dx+dy*dy<h.radius*h.radius){
        tt1.style.cssText = "position:fixed;background-color:#DDDDDD;opacity:0.8;border: 1px solid rgb(255, 221, 221);pointer-events:none;font-weight: bold; z-index: 100;";
        tt1.style.left = e.clientX + 15 + "px";
        tt1.style.top =  e.clientY + 15+ "px";
        tt1.style.visibility ="visible";
        tt1.innerHTML = "&nbsp;"+h.tip+"&nbsp;";
       }
       else {
         tt1.style.visibility ="hidden";
       }

       h=hotspots[1];
       dx=mouseX-h.xspot;
       dy=mouseY-h.yspot;
       if(dx*dx+dy*dy<h.radius*h.radius){
       tt2.style.cssText = "position:fixed;background-color:#DDDDDD;opacity:0.8;border: 1px solid rgb(255, 221, 221);pointer-events:none;font-weight: bold; z-index: 100;";
       tt2.style.left = e.clientX + 15 + "px";
       tt2.style.top =  e.clientY + 15+ "px";
       tt2.style.visibility ="visible";
       tt2.innerHTML = "&nbsp"+h.tip+"&nbsp";
       }
       else {
          tt2.style.visibility ="hidden";
       }
      }
     $("#"+canvasid).mousemove(function(e){handleMouseMove(e);});
    }
  }
}

function dial_define_tooltips(){
  $(".dial").each(function(index) {
      var id2 = "can-"+$(this).attr("id");
      var canvas2 = document.getElementById(id2);
      var parent = canvas2.parentNode;           // parent node for canvas
      if(document.getElementById(id2 + "-tooltip-1")){}
      else{
      var div1 = document.createElement("div");      // the tool-tip div 1
      div1.id = id2 + "-tooltip-1";
      parent.appendChild(div1);}
      if(document.getElementById(id2 + "-tooltip-2")){}
      else{
      var div2 = document.createElement("div");      // the tool-tip div 2
      div2.id = id2 + "-tooltip-2";
      parent.appendChild(div2);}
  });
}

function dial_draw(){
  $(".dial").each(function(index) {
    var feedid = $(this).attr("feedid");
    var minvaluefeed = $(this).attr("minvaluefeed")||"0";
    var maxvaluefeed = $(this).attr("maxvaluefeed")||"0";
    if (associd[feedid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }

    var val = (associd[feedid]["value"] * 1).toFixed(3);        
    var val_curve = curve_value(feedid,dialrate).toFixed(3);
    
    // The minval and maxval feed settings default to the first feed in the feedlist 
    // which may not be public for use in public dashboards, which will then result in
    // an error. Here we hide the min/max val feature where the feed settings are not valid
    
    var minval = 0;
    var minval_curve = 0; 
    if (associd[minvaluefeed] != undefined) {
        minval = (associd[minvaluefeed]["value"] * 1).toFixed(3);
        minval_curve = curve_value(minvaluefeed,dialrate).toFixed(3);
    }
    
    var maxval = 0;
    var maxval_curve = 0;
    if (associd[maxvaluefeed] != undefined) {
        maxval = (associd[maxvaluefeed]["value"] * 1).toFixed(3);
        maxval_curve = curve_value(maxvaluefeed,dialrate).toFixed(3);
    }
    
    // ONLY UPDATE ON CHANGE
    if (val_curve!=val || minval_curve!=minval || maxval_curve!=maxval ||redraw == 1)
    {
      var id = "can-"+$(this).attr("id");
      var scale = 1*$(this).attr("scale") || 1;
      var unitend = $(this).attr("unitend") || "0";
      draw_gauge(widgetcanvas[id],
                 id,
                 0,
                 0,
                 $(this).width(),
                 $(this).height(),
                 val_curve*scale,
                 $(this).attr("max"),
                 $(this).attr("units"),
                 $(this).attr("decimals"),
                 $(this).attr("type"),
                 $(this).attr("offset"),
                 $(this).attr("graduations"),
                 unitend,
                 $(this).attr("displayminmax"),
                 minval_curve*scale,
                 maxval_curve*scale
                 );
    }
  });
}

function dial_init(){
  setup_widget_canvas("dial");
  dial_define_tooltips();
}

function dial_slowupdate(){
}

function dial_fastupdate(){
  dial_draw();
}
