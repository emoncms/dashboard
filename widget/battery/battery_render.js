/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
    Author: Vikas Lamba: vikas13jun@gmail.com
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

function battery_widgetlist(){
  var widgets =
  {
    "battery":
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

  var fontoptions = [
    [8, "Arial Black"],
    [7, "Arial Narrow"],
    [6, "sans-serif"],
    [5, "Helvetica"],
    [4, "Comic Sans MS"],
    [3, "Courier New"],
    [2, "Arial"],
    [1, "Georgia"],
    [0, "Impact"]
  ];

  var StyleOptions = [
    [1, _Tr("With colour gradients")],
    [0, _Tr("Without colour gradients")]
    ];

  addOption(widgets["battery"], "feedid",                "feedid",        _Tr("Feed"),            _Tr("Feed value"),                                                            []);
  addOption(widgets["battery"], "battery_title",         "value",         _Tr("Battery title"),   _Tr("Battery title"),                                                         []);
  addOption(widgets["battery"], "max",                   "value",         _Tr("Max value"),       _Tr("Max value to show"),                                                     []);
  addOption(widgets["battery"], "min",                   "value",         _Tr("Min value"),       _Tr("Min value to show"),                                                     []); //TT
  addOption(widgets["battery"], "scale",                 "value",         _Tr("Scale"),           _Tr("Value is multiplied by scale before display"),                           []);
  addOption(widgets["battery"], "units",                 "dropbox_other", _Tr("Units"),           _Tr("Units to show"),                                                         _SI);
  addOption(widgets["battery"], "unitend",               "dropbox",       _Tr("Unit position"),   _Tr("Where should the unit be shown"),                                        unitEndOptions);
  addOption(widgets["battery"], "number_of_blocks",      "value",         _Tr("Number of blocks"),_Tr("Number of blocks to display"),                                           []);
  addOption(widgets["battery"], "decimals",              "dropbox",       _Tr("Decimals"),        _Tr("Decimals to show"),                                                      decimalsDropBoxOptions);
  addOption(widgets["battery"], "offset",                "value",         _Tr("Offset"),          _Tr("Static offset. Subtracted from value before computing"),                 []);
  addOption(widgets["battery"], "colour",                "colour_picker", _Tr("Colour label"),    _Tr("Color of the label"),                                                    []);
  addOption(widgets["battery"], "font",                  "dropbox",       _Tr("Font"),            _Tr("Label font"),                                                            fontoptions);
  addOption(widgets["battery"], "fstyle",                "dropbox",       _Tr("Font style"),      _Tr("Font style used for display"),                                           fstyleoptions);
  addOption(widgets["battery"], "fweight",               "dropbox",       _Tr("Font weight"),     _Tr("Font weight used for display"),                                          fweightoptions);
  addOption(widgets["battery"], "battery_style",         "dropbox",       _Tr("Style"),           _Tr("Display style"),                                                         StyleOptions);
  addOption(widgets["battery"], "timeout",               "value",         _Tr("Timeout"),         _Tr("Timeout without feed update in seconds (empty is never)"),               []);
  addOption(widgets["battery"], "errormessagedisplayed", "value",         _Tr("Error Message"),   _Tr("Error message displayed when timeout is reached"),                       []);

  return widgets;
}

function battery_init(){
  setup_widget_canvas("battery");
}

function battery_draw(){
  $(".battery").each(function(index) {
    var errorMessage = $(this).attr("errormessagedisplayed");
        if (errorMessage === "" || errorMessage === undefined){            //Error Message parameter is empty
          errorMessage = "TO Error";
        }
    var errorTimeout = $(this).attr("timeout");
        if (errorTimeout === "" || errorTimeout === undefined){            //Timeout parameter is empty
          errorTimeout = 0;
        }

    var errorCode = "0";

    var feedid = $(this).attr("feedid");
    if (assocfeed[feedid]!=undefined) feedid = assocfeed[feedid]; // convert tag:name to feedid
    if (associd[feedid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    var val = curve_value(feedid,dialrate).toFixed(3);

    if (errorTimeout !== 0)
      {
        if (((new Date()).getTime() / 1000 - offsetofTime - (associd[feedid]["time"] * 1)) > errorTimeout) 
        {
          errorCode = "1";
        }
      }
    // ONLY UPDATE ON CHANGE
    if (val != (associd[feedid]["value"] * 1).toFixed(3) || redraw == 1 || errorTimeout != 0)
    {
      var id = "can-"+$(this).attr("id");
      var scale = 1*$(this).attr("scale") || 1;
      var offset = 1*$(this).attr("offset") || 0;
      var max_val = 1*$(this).attr("max") || 100;
      var min_val = 1*$(this).attr("min") || 0;
      var units = $(this).attr("units");
      var decimals = $(this).attr("decimals");
      var font = $(this).attr("font")|| "8";
      var fstyle = $(this).attr("fstyle") || "2";
      var fweight = $(this).attr("fweight") || "0";
      var unitend = $(this).attr("unitend") || "0";
      var color = $(this).attr("colour") || "000";
      var title = $(this).attr("battery_title");
      var battery_style = $(this).attr("battery_style") || "1";

      var start_x = 0, start_y = 0;
      var battery_height = $(this).height();
      var battery_width = $(this).width();
      var cap_height = battery_height/16;
      var cap_width = battery_width/3;
      var line_width = 2;
      var margin = 1;
      var number_of_blocks = Math.round(1*$(this).attr("number_of_blocks")) || 5;

      var fontname;
      if (font === "0"){fontname = "Impact";}
      if (font === "1"){fontname = "Georgia";}
      if (font === "2"){fontname = "Arial";}
      if (font === "3"){fontname = "Courier New";}
      if (font === "4"){fontname = "Comic Sans MS";}
      if (font === "5"){fontname = "Helvetica";}
      if (font === "6"){fontname = "sans-serif";}
      if (font === "7"){fontname = "Arial Narrow";}
      if (font === "8"){fontname = "Arial Black";}

      var fontstyle;

     if (fstyle === "0"){fontstyle = "oblique";}
     if (fstyle === "1"){fontstyle = "italic";}
     if (fstyle === "2"){fontstyle = "normal";}

     var fontweight;

     if (fweight === "0"){fontweight = "normal";}
     if (fweight === "1"){fontweight = "bold";}

      if (color.indexOf("#") === -1) {color = "#" + color;}

      var raw_value = val*scale + offset;
      var data;

      if(raw_value > max_val){
         data = max_val;} // Clamp value so we don't overshoot the top of the battery.
      else{
         data=raw_value;}

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

      var context = widgetcanvas[id];
      context.globalAlpha = 1;
      context.clearRect(0,0,battery_width,battery_height);

      //Drawing the battery cap
      context.fillStyle = "#ffffff";
      context.fillRect(start_x + battery_width/2 - cap_width/2, start_y + line_width, cap_width, cap_height + line_width*2);
      context.strokeStyle = "#000f00";
      context.lineWidth   = line_width;
      context.strokeRect(start_x + battery_width/2 - cap_width/2, start_y + line_width, cap_width, cap_height + line_width*2);
      //Clear the battery area first
      context.fillStyle = "#ffffff";
      context.fillRect(start_x + line_width, start_y + line_width + cap_height, battery_width - line_width*2, battery_height-cap_height);
      //Drawing body outline
      context.strokeStyle = "#000f00";
      context.lineWidth   = line_width;
      context.strokeRect(start_x + line_width, start_y + line_width + cap_height, battery_width - line_width*2, battery_height-cap_height - line_width*2);

      //Filling body
      var block_width = battery_width - 2*(line_width*2 + margin);
      var block_height = Math.ceil((battery_height - cap_height - 2*(line_width*2 + margin))/number_of_blocks) - 2*margin;
      var block_start_y = start_y + battery_height - line_width*2 - margin;
      
      var last_block = Math.ceil((number_of_blocks*(data-min_val))/(max_val - min_val));
      var green_val = Math.floor(((data-min_val)*255)/(max_val - min_val));
      if (green_val<0) green_val = 0;
      if (green_val>255) green_val = 255;
      if (isNaN(green_val)) green_val = 0;

      var red_val = 255 - green_val;
      if (errorCode !== "1"){
      if (battery_style ==="1"){
        var linearGradient1 = context.createLinearGradient(start_x + line_width*2 + margin, 
                                          start_y + cap_height + line_width*2 + margin, 
                                          start_x + line_width*2 + margin + block_width, 
                                          start_y + cap_height + line_width*2 + margin);
        linearGradient1.addColorStop(0, "rgb("+red_val+", "+green_val+", 0)");
        linearGradient1.addColorStop(0.5, "rgb(0, 0, 0)");
        linearGradient1.addColorStop(1, "rgb("+red_val+","+green_val+", 0)");
      }
      if (battery_style ==="0"){
        var linearGradient1 = context.createLinearGradient(start_x + line_width*2 + margin, 
                                          start_y + cap_height + line_width*2 + margin, 
                                          start_x + line_width*2 + margin + block_width, 
                                          start_y + cap_height + line_width*2 + margin);
        linearGradient1.addColorStop(0, "rgb("+red_val+", "+green_val+", 0)");
        linearGradient1.addColorStop(1, "rgb("+red_val+","+green_val+", 0)");
      }
	  }
      for(var i=1;i <= last_block; i++)
      {
        var y_pos = start_y + block_start_y - i*(block_height + margin);
        
        context.fillStyle = linearGradient1;
        context.fillRect(start_x + line_width*2 + margin, y_pos, block_width, block_height);
      }

      var size = ((battery_width<battery_height)?battery_width:battery_height)/2;
      var unitsandval = raw_value + units;
      if(errorCode == "1")
      {
      unitsandval = errorMessage;
      }
      var valsize;
      if (unitsandval.length >4){ valsize = (size / (unitsandval.length+2)) * 5.5;}
      else {valsize = (size / 6) * 5.5;}
      var titlesize ;
      if (title.length >10) {titlesize = (size / (title.length+2)) * 9;}
      else {titlesize = (size / 12) * 9.5;}
      context.fillStyle = color;
      context.textAlign = "center";
      context.font = (fontstyle+ " "+ fontweight+ " "+(valsize*0.5)+"px "+ fontname);
      if (errorCode === "1"){context.fillText(errorMessage, start_x + battery_width/2, start_y + battery_height*0.6);}
      else{
      if (unitend ==="0"){context.fillText(raw_value + units, start_x + battery_width/2, start_y + battery_height*0.6);}
      if (unitend ==="1"){context.fillText(units + raw_value, start_x + battery_width/2, start_y + battery_height*0.6);}
      }

      if(title)
      {
        context.fillStyle = color;
        context.textAlign = "center";
        context.font = (fontstyle+ " "+ fontweight+ " "+(titlesize*0.30)+"px "+ fontname);
        context.fillText(title, start_x + battery_width/2, start_y + battery_height*0.25);
      }
    }
  });
}

function battery_slowupdate(){}

function battery_fastupdate(){
  battery_draw();
}
