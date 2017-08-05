/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
        Part of the OpenEnergyMonitor project:
        http://openenergymonitor.org

        Author: Aymeric Thibaut
        If you have any questions please get in touch, try the forums here:
        http://openenergymonitor.org/emon/forum
 */

// Convenience function for shoving things into the widget object
// I'm not sure about calling optionKey "optionKey", but I don't want to just use "options" (because that's what this whole function returns), and it's confusing enough as it is.
function addOption(widget, optionKey, optionType, optionName, optionHint, optionData)
{

    widget["options"    ].push(optionKey);
    widget["optionstype"].push(optionType);
    widget["optionsname"].push(optionName);
    widget["optionshint"].push(optionHint);
    widget["optionsdata"].push(optionData);


}
function thermometer_widgetlist()
{
    var widgets =
    {
        "thermometer":
        {
            "offsetx":-80,"offsety":-80,"width":160,"height":200,
            "menu":"Widgets",
            "options":    [],
            "optionstype":[],
            "optionsname":[],
            "optionshint":[],
            "optionsdata":[]

        }
    };
    var decimalsDropBoxOptions = [        // Options for the type combobox. Each item is [typeID, "description"]
                    [-1,   _Tr("Automatic")],
                    [0,    "0"],
                    [1,    "1"],
                    [2,    "2"],
                    [3,    "3"],
                    [4,    "4"],
                    [5,    "5"],
                    [6,    "6"]
    ];

    var fontoptions = [
                    [9, "Arial Black"],
                    [8, "Arial Narrow"],
                    [7, "sans-serif"],
                    [6, "Helvetica Neue"],
                    [5, "Helvetica"],
                    [4, "Comic Sans MS"],
                    [3, "Courier New"],
                    [2, "Arial"],
                    [1, "Georgia"],
                    [0, "Impact"]
                ];

    var fstyleoptions = [
                    [2, _Tr("Normal")],
                    [1, _Tr("Italic")],
                    [0, _Tr("Oblique")]
                ];

    var fweightoptions = [
                    [0, _Tr("Normal")],
                    [1, _Tr("Bold")]
                ];
    
    var unitEndOptions = [
                    [0, _Tr("Back")],
                    [1, _Tr("Front")]
                ];
    
    var graduationDropBoxOptions = [
                    [0, _Tr("No")],
                    [1, _Tr("Yes")]
                    ];

    var displayminmaxDropBoxOptions = [
                    [0, _Tr("No")],
                    [1, _Tr("Yes")]
                    ];

    addOption(widgets["thermometer"], "title_thermometer",      "value",            _Tr("Title"),           _Tr("Title of thermometer"),                                                                []);
    addOption(widgets["thermometer"], "colour_label",   "colour_picker",    _Tr("Label Colour"),    _Tr("Colour of title and values"),                                                  []);
    addOption(widgets["thermometer"], "font",           "dropbox",          _Tr("Font used"),       _Tr("Font used"),                                                                   fontoptions);
    addOption(widgets["thermometer"], "fstyle",         "dropbox",          _Tr("Font style"),      _Tr("Font style used for display"),                                                 fstyleoptions);
    addOption(widgets["thermometer"], "fweight",        "dropbox",          _Tr("Font weight"),     _Tr("Font weight used for display"),                                                fweightoptions);
    addOption(widgets["thermometer"], "feedid",         "feedid",           _Tr("Feed"),            _Tr("Feed value"),                                                                  []);
    addOption(widgets["thermometer"], "max",            "value",            _Tr("Max value"),       _Tr("Max value to show"),                                                           []);
    addOption(widgets["thermometer"], "scale",          "value",            _Tr("Scale"),           _Tr("Value is multiplied by scale before display. Defaults to 1"),                  []);
    addOption(widgets["thermometer"], "units",          "value",            _Tr("Units"),           _Tr("Units to show"),                                                               []);
    addOption(widgets["thermometer"], "unitend",        "dropbox",          _Tr("Unit position"),   _Tr("Where should the unit be shown"),                                              unitEndOptions);
    addOption(widgets["thermometer"], "decimals",       "dropbox",          _Tr("Decimals"),        _Tr("Decimals to show"),                                                            decimalsDropBoxOptions);
    addOption(widgets["thermometer"], "offset",         "value",            _Tr("Offset"),          _Tr("Static offset. Subtracted from value before computing"),                       []);
    addOption(widgets["thermometer"], "graduations",    "dropbox",          _Tr("Graduations"),     _Tr("Should the graduations be shown"),                                             graduationDropBoxOptions);
    addOption(widgets["thermometer"], "gradNumber",     "value",            _Tr("Grad. Num."),      _Tr("How many graduation lines to draw (only relevant if graduations are on)"),     []);
    addOption(widgets["thermometer"], "displayminmax",  "dropbox",          _Tr("Min / Max ?"),     _Tr("Display Min. and Max. ?"),                                                     displayminmaxDropBoxOptions);
    addOption(widgets["thermometer"], "minvaluefeed",   "feedid",           _Tr("Min. feed"),       _Tr("The feed for the minimum value"),                                              []);
    addOption(widgets["thermometer"], "maxvaluefeed",   "feedid",           _Tr("Max. feed"),       _Tr("The feed for the maximum value"),                                              []);
    addOption(widgets["thermometer"], "colour_minmax",  "colour_picker",    _Tr("Colour"),          _Tr("Colour for min. and max. bars"),                                               []);


    return widgets;
}


function draw_thermometer(context,
                  canvasid,
                  x_pos,              // these x and y coords seem unused?
                  y_pos,
                  title_thermometer,
                  font,
                  fstyle,
                  fweight,
                  width,
                  height,
                  raw_value,
                  max_value,
                  units_string,
                  decimals,
                  unitend,
                  colour_label,
                  static_offset,
                  graduationBool,
                  graduationQuant,
                  displayminmax,
                  minvaluefeed,
                  maxvaluefeed,
                  colour_minmax
                  )
{
    if (!context) {return;}

    context.clearRect(0,0,width+10,height+10); // Clear old drawing

    // if (1 * max_value) == false: 3000. Else 1 * max_value
    max_value = 1 * max_value || 3000;
    // if units_string == false: "". Else units_string
    units_string = units_string || "";
    title_thermometer = title_thermometer || "";
    fstyle = fstyle || "2";
    fweight = fweight || "0";
    unitend = unitend || "0";
    colour_label = colour_label || "000";
    colour_minmax = colour_minmax || "555";
    static_offset = 1*static_offset || 0;
    var display_value = raw_value;
    display_value = display_value-static_offset;

    var scaled_value = (display_value/max_value);    // Produce a scaled 0-1 value corresponding to min-max
    if (scaled_value < 0){scaled_value = 0;}

    var min_display_value = minvaluefeed;
    min_display_value = min_display_value-static_offset;

    var min_scaled_value = (min_display_value/max_value);    // Produce a scaled 0-1 value corresponding to min-max
    if (min_scaled_value < 0){min_scaled_value = 0;}

    var max_display_value = maxvaluefeed;
    max_display_value = max_display_value-static_offset;

    var max_scaled_value = (max_display_value/max_value);    // Produce a scaled 0-1 value corresponding to min-max
    if (max_scaled_value < 0){max_scaled_value = 0;}

        if (decimals<0){
            if (raw_value>=100) {
            raw_value = raw_value.toFixed(0);
            } else if (raw_value>=10) {
            raw_value = raw_value.toFixed(1);
            } else  {
            raw_value = raw_value.toFixed(2);
            }
        raw_value = parseFloat(raw_value);
        }
        else {
             raw_value = raw_value.toFixed(decimals);
        }

        if (decimals<0){
            if (minvaluefeed>=100) {
            minvaluefeed = minvaluefeed.toFixed(0);
            } else if (minvaluefeed>=10) {
            minvaluefeed = minvaluefeed.toFixed(1);
            } else  {
            minvaluefeed = minvaluefeed.toFixed(2);
            }
        minvaluefeed = parseFloat(minvaluefeed);
        }
        else {
             minvaluefeed = minvaluefeed.toFixed(decimals);
        }

        if (decimals<0){
            if (maxvaluefeed>=100) {
            maxvaluefeed = maxvaluefeed.toFixed(0);
            } else if (maxvaluefeed>=10) {
            maxvaluefeed = maxvaluefeed.toFixed(1);
            } else  {
            maxvaluefeed = maxvaluefeed.toFixed(2);
            }
        maxvaluefeed = parseFloat(maxvaluefeed);
        }
        else {
             maxvaluefeed = maxvaluefeed.toFixed(decimals);
        }

    if (width>height){width = height;}
    var size = 0;
    if (width<height){size = width/2;}
    else {size = height/2;}
    size = size - (size*0.058/2);

    var half_width = width/2;
    var half_height = height/2;


    var fontname;

    if (font === "0"){fontname = "Impact";}
    if (font === "1"){fontname = "Georgia";}
    if (font === "2"){fontname = "Arial";}
    if (font === "3"){fontname = "Courier New";}
    if (font === "4"){fontname = "Comic Sans MS";}
    if (font === "5"){fontname = "Helvetica";}
    if (font === "6"){fontname = "Helvetica Neue";}
    if (font === "7"){fontname = "sans-serif";}
    if (font === "8"){fontname = "Arial Narrow";}
    if (font === "9"){fontname = "Arial Black";}
    else if (typeof(font) === "undefined") {fontname = "Arial Black";}

    var fontstyle;

    if (fstyle === "0"){fontstyle = "oblique";}
    if (fstyle === "1"){fontstyle = "italic";}
    if (fstyle === "2"){fontstyle = "normal";}

    var fontweight;

    if (fweight === "0"){fontweight = "normal";}
    if (fweight === "1"){fontweight = "bold";}


    if (graduationBool == 1)
    {
     context.save();
     context.translate((half_width*(-0.25)),0); //translate the thermometer to the left in order to get more space for the graduations
        if (graduationQuant > 0)
        {
            context.textAlign    = "start";
            context.font = (fontstyle+ " "+ fontweight+ " "+(size*0.12)+"px "+ fontname);

            var step = (height*0.8)/(Number(graduationQuant)+1);
            var curY;

            var divisions = Number(graduationQuant)+1;

            for (var y = 0; y < graduationQuant; y++)
            {
                curY = Number(((y+1)*step).toFixed(0))+0.5;  // Bin down so we're drawing in the middle of the pixel, so the line is exactly 1 px wide
                context.moveTo(half_width*0.3, curY);
                context.lineTo(half_width*0.7, curY);

                var unitOffset = Number(static_offset+((graduationQuant-y)*(max_value/divisions)));
                if (unitOffset < 1000)
                    {unitOffset = unitOffset.toFixed(1);}
                else
                    {unitOffset = unitOffset.toFixed(0);}
                if (unitend ==="0"){context.fillText(parseFloat(unitOffset)+units_string, half_width*0.75, curY);}
                if (unitend ==="1"){context.fillText(units_string+parseFloat(unitOffset), half_width*0.75, curY);}
            }
            context.moveTo(half_width*0.3, height*0.8);
            context.lineTo(half_width*0.7, height*0.8);
            if (unitend ==="0"){context.fillText(static_offset+units_string, half_width*0.75, height*0.8);}
            if (unitend ==="1"){context.fillText(units_string+static_offset, half_width*0.75, height*0.8);}

            context.strokeStyle = "#888";
            context.stroke();
        }
    }

    if (!display_value) {display_value = 0;}  // Clamp value so we don't draw negative values.

    context.fillStyle = "#FB0000";

    var thermometer_top = (height*0.8 - (scaled_value * height*0.8));

    if (thermometer_top < height*0.05)     // Clamp value so we don't overshoot the top of the thermometergraph.
       {thermometer_top = height*0.05;}

    context.beginPath();
    context.lineWidth = width*0.01;
    context.strokeStyle = "#000";
    context.arc(half_width/2,height*0.05 + width*0.02,half_width*0.1,1*Math.PI,2*Math.PI);
    context.fillStyle = "#FFF";
    context.fill();
    context.fillRect(half_width*0.4,height*0.05,half_width*0.2,height*0.9);
    context.moveTo(half_width*0.4,height*0.05 + width*0.01);
    context.lineTo(half_width*0.4,height*0.9);
    context.moveTo(half_width*0.6,height*0.05 + width*0.01);
    context.lineTo(half_width*0.6,height*0.9);
    context.stroke();

    context.beginPath();
    context.arc(half_width/2,height*0.9 - width*0.01,half_width*0.2,1.66*Math.PI,1.35*Math.PI);
    context.fillStyle = "#FFF";
    context.fill();
    context.lineWidth = width*0.01;
    context.strokeStyle = "#000";
    context.stroke();

    context.beginPath();
    context.arc(half_width/2,height*0.9 - width*0.01,half_width*0.2-width*0.02,0,2*Math.PI);
    context.fillStyle = "#FB0000";
    context.fill();

    context.fillRect(half_width*0.4 + width*0.02,
                    thermometer_top,
                    half_width*0.2 - width*0.04,
                    height*0.9 - thermometer_top);

    if(displayminmax==="1"){

    if (colour_minmax.indexOf("#") === -1) {colour_minmax = "#" + colour_minmax;}  // Fix missing "#" on colour if needed
    context.fillStyle = colour_minmax;
    var thermometer_min = (height*0.8 - (min_scaled_value * height*0.8));

    if (thermometer_min < height*0.05)     // Clamp value so we don't overshoot the top of the thermometergraph.
       {thermometer_min = height*0.05;}

    context.fillRect(half_width*0.4 + width*0.005,
                    thermometer_min,
                    half_width*0.2 - width*0.01,
                    2);

    context.fillStyle = colour_minmax;
    var thermometer_max = (height*0.8 - (max_scaled_value * height*0.8));

    if (thermometer_max < height*0.05)     // Clamp value so we don't overshoot the top of the thermometergraph.
       {thermometer_max = height*0.05;}

    context.fillRect(half_width*0.4 + width*0.005,
                    thermometer_max,
                    half_width*0.2 - width*0.01,
                    2);
    }

    context.restore();

      var canvas = document.getElementById(canvasid);
      var cw=canvas.width;
      var ch=canvas.height;
      var offsetX,offsetY;
      var mouseX,mouseY;

      function reOffset(){
       var BB=canvas.getBoundingClientRect();
       offsetX=BB.left;
       offsetY=BB.top;
      };

      reOffset();
      window.onscroll=function(e){ reOffset(); };
      window.onresize=function(e){ reOffset(); };

     var offset_position = 0;
     if (graduationBool == 1){offset_position = 0.25;}

     var hotspots=[ // declare hotspots in order to active associated tooltips
      {xspot:(half_width*(0.4 - offset_position) + width*0.005),yspot:(thermometer_min),wspot:(half_width*0.2 - width*0.01),hspot:2,tip: minvaluefeed},
      {xspot:(half_width*(0.4 - offset_position) + width*0.005),yspot:(thermometer_max),wspot:(half_width*0.2 - width*0.01),hspot:2,tip: maxvaluefeed},
      ];

       function handleMouseMove(e){
       e.preventDefault();
       e.stopPropagation();

       mouseX=parseInt(e.clientX-offsetX);
       mouseY=parseInt(e.clientY-offsetY);

        var dx=mouseX;
        var dy=mouseY;

        h=hotspots[0];
        if(dx >= h.xspot && dx < h.xspot + h.wspot && dy >= h.yspot && dy < h.yspot + h.hspot){
        div1.style.cssText = "position:fixed;background-color:#DDDDDD;opacity:0.8;border: 1px solid rgb(255, 221, 221);pointer-events:none;font-weight: bold;";
        div1.style.left = e.clientX + 15 + "px";
        div1.style.top =  e.clientY + 15+ "px";
        div1.style.visibility ="visible";
        div1.innerHTML = "&nbsp;"+h.tip+"&nbsp;";
        }
        else {
          div1.style.visibility ="hidden";
          }

        h=hotspots[1];
        if(dx >= h.xspot && dx < h.xspot + h.wspot && dy >= h.yspot && dy < h.yspot + h.hspot){
        div2.style.cssText = "position:fixed;background-color:#DDDDDD;opacity:0.8;border: 1px solid rgb(255, 221, 221);pointer-events:none;font-weight: bold;";
        div2.style.left = e.clientX + 15 + "px";
        div2.style.top =  e.clientY + 15+ "px";
        div2.style.visibility ="visible";
        div2.innerHTML = "&nbsp"+h.tip+"&nbsp";
        }
        else {
          div2.style.visibility ="hidden";
          }
       }

    $("#"+canvasid).mousemove(function(e){handleMouseMove(e);});

    if (colour_label.indexOf("#") === -1) {colour_label = "#" + colour_label;} // Fix missing "#" on colour if needed

    context.fillStyle = colour_label;
    
    var unitsandval = raw_value+units_string;
    var valsize;
    if (unitsandval.length >4){ valsize = (size / (unitsandval.length+2)) * 5.5;}
    else {valsize = (size / 6) * 5.5;}
    var titlesize ;
    if (title_thermometer.length >10) {titlesize = (size / (title_thermometer.length+2)) * 9;}
    else {titlesize = (size / 12) * 9.5;}
    
    context.textAlign    = "center";
        context.font = (fontstyle+ " "+ fontweight+ " "+(valsize*0.45)+"px "+ fontname);
        if (unitend ==="0"){context.fillText(raw_value+units_string, width*0.75, height*0.6);}
        if (unitend ==="1"){context.fillText(units_string+raw_value, width*0.75, height*0.6);}
        context.font = (fontstyle+ " "+ fontweight+ " "+(titlesize*0.25)+"px "+ fontname);
        context.fillText(title_thermometer, width*0.75, height*0.2);

}

function thermometer_define_tooltips(){
  $(".thermometer").each(function(index) {
      var id2 = "can-"+$(this).attr("id");
      var canvas2 = document.getElementById(id2);
      div1 = document.createElement("div");      // the tool-tip div
      div2 = document.createElement("div");      // the tool-tip div
      parent = canvas2.parentNode;           // parent node for canvas

      parent.appendChild(div1);
      parent.appendChild(div2);
  });
}
function thermometer_draw()
{
    $(".thermometer").each(function(index)
    {
        var feedid = $(this).attr("feedid");
        var minvaluefeed = $(this).attr("minvaluefeed");
        var maxvaluefeed = $(this).attr("maxvaluefeed");
        if (associd[feedid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
        var val = curve_value(feedid,dialrate).toFixed(3);
        var minval = curve_value(minvaluefeed,dialrate).toFixed(3);
        var maxval = curve_value(maxvaluefeed,dialrate).toFixed(3);

        // ONLY UPDATE ON CHANGE
        if (val != (associd[feedid]["value"] * 1).toFixed(3) || redraw == 1)
        {
            var id = "can-"+$(this).attr("id");
            var scale = 1*$(this).attr("scale") || 1;
            draw_thermometer(widgetcanvas[id],
                     id,
                     0,
                     0,
                     $(this).attr("title_thermometer"),
                     $(this).attr("font"),
                     $(this).attr("fstyle"),
                     $(this).attr("fweight"),
                     $(this).width(),
                     $(this).height(),
                     val*scale,
                     $(this).attr("max"),
                     $(this).attr("units"),
                     $(this).attr("decimals"),
                     $(this).attr("unitend"),
                     $(this).attr("colour_label"),
                     $(this).attr("offset"),
                     $(this).attr("graduations"),
                     $(this).attr("gradNumber"),
                     $(this).attr("displayminmax"),
                     minval*scale,
                     maxval*scale,
                     $(this).attr("colour_minmax")
                     );
        }
    });
}


function thermometer_init()
{
    setup_widget_canvas("thermometer");
    thermometer_define_tooltips();
}
function thermometer_slowupdate()
{

}

function thermometer_fastupdate()
{
    thermometer_draw();
}
