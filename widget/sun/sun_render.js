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
function sun_widgetlist(){
  var widgets =
  {
    "sun":
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

  addOption(widgets["sun"],   "feedid",                "feedid",          _Tr("Feed"),          _Tr("Feed value"),                                            []);
  addOption(widgets["sun"],   "max",                   "value",           _Tr("Max value"),     _Tr("Max value to show"),                                     []);
  addOption(widgets["sun"],   "scale",                 "value",           _Tr("Scale"),         _Tr("Value is multiplied by scale before display"),           []);
  addOption(widgets["sun"],   "units",                 "dropbox_other",   _Tr("Units"),         _Tr("Units to show"),                                         _SI);
  addOption(widgets["sun"],   "unitend",               "dropbox",         _Tr("Unit position"), _Tr("Where should the unit be shown"),                        unitEndOptions);
  addOption(widgets["sun"],   "decimals",              "dropbox",         _Tr("Decimals"),      _Tr("Decimals to show"),                                      decimalsDropBoxOptions);
  addOption(widgets["sun"],   "offset",                "value",           _Tr("Offset"),        _Tr("Static offset. Subtracted from value before computing"), []);
  addOption(widgets["sun"],   "solar_title",           "value",           _Tr("solar title"),   _Tr("Solar title"),                                           []);
  addOption(widgets["sun"],   "colour",                "colour_picker",   _Tr("Colour label"),  _Tr("Color of the label"),                                    []);
  addOption(widgets["sun"],   "font",                  "dropbox",         _Tr("Font"),          _Tr("Label font"),                                            fontoptions);
  addOption(widgets["sun"],   "fstyle",                "dropbox",         _Tr("Font style"),    _Tr("Font style used for display"),                           fstyleoptions);
  addOption(widgets["sun"],   "fweight",               "dropbox",         _Tr("Font weight"),   _Tr("Font weight used for display"),                          fweightoptions);
  addOption(widgets["sun"],   "timeout",               "value",           _Tr("Timeout"),       _Tr("Timeout without feed update in seconds (empty is never)"),[]);
  addOption(widgets["sun"],   "errormessagedisplayed", "value",           _Tr("Error Message"), _Tr("Error message displayed when timeout is reached"),        []);

  return widgets;
}

function sun_init(){
  setup_widget_canvas('sun');
}

function sun_draw(){
  $('.sun').each(function(index) {
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
    if (val != (associd[feedid]['value'] * 1).toFixed(3) || redraw == 1 || errorTimeout != 0)
    {
      var id = "can-"+$(this).attr("id");
      var scale = 1*$(this).attr("scale") || 1;
      var offset = 1*$(this).attr("offset") || 0;
      var max_val = 1*$(this).attr("max") || 100;
      var units = $(this).attr("units");
      var decimals = $(this).attr("decimals");
      var unitend = $(this).attr("unitend") || "0";
      var font = $(this).attr("font");
      var fstyle = $(this).attr("fstyle") || "2";
      var fweight = $(this).attr("fweight") || "0";
      var color = $(this).attr("colour") || "000";
      var title = $(this).attr("solar_title");

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
      else if (typeof(font) === "undefined") {fontname = "Arial Black";}

      var fontstyle;

      if (fstyle === "0"){fontstyle = "oblique";}
      if (fstyle === "1"){fontstyle = "italic";}
      if (fstyle === "2"){fontstyle = "normal";}

      var fontweight;

      if (fweight === "0"){fontweight = "normal";}
      if (fweight === "1"){fontweight = "bold";}

      if (color.indexOf("#") === -1) color = "#" + color;

      var start_x = 0, start_y = 0;
      var sun_height = $(this).height();
      var sun_width = $(this).width();
      var line_width = 2;
      var margin = 1;
      var number_of_blocks = 5;

      var data = val*scale + offset;
      
      if(data > max_val)
        data = max_val;

      if (decimals<0){
        if (data>=100) {
            data = data.toFixed(0);
        } else if (data>=10) {
            data = data.toFixed(1);
        } else  {
            data = data.toFixed(2);
        }
        data = parseFloat(data);
      }
      else {
           data = data.toFixed(decimals);
      }

      var context = widgetcanvas[id];

      context.globalAlpha = 1;

      var bar_length = 10;
      var bar_width = 5;
      var number_of_bars = 5;

      var radius = Math.max(Math.min(sun_width/2,sun_height) - bar_length - bar_width,0);
  
      var centerX = sun_width / 2;
      var centerY = sun_height;

      context.beginPath();
      context.arc(start_x + centerX, start_y + centerY, radius, 1 * Math.PI, 2 * Math.PI, false);
      context.closePath();

      var fill = 255;//Math.floor(data/240*100 + 100);
      if(fill > 255)
        fill = 255;

      context.fillStyle = 'rgb('+fill+', '+(fill-27)+', 124)';
      context.fill();
      context.lineWidth = line_width+2;
      context.strokeStyle = '#ffffff';
      context.stroke();

      //Draw the bars

      context.lineWidth = line_width;
      context.strokeStyle = '#FFE87C';//'#FDB813';
      context.beginPath();
      var lxs, lys, lxe, lye;
      var theta = 1;
      for(var i=180;i<360;i+=theta)
      {
        lxs = (radius+line_width)*Math.cos(i);
        lys = (radius+line_width)*Math.sin(i);

        lxe = (radius+line_width + bar_length)*Math.cos(i);
        lye = (radius+line_width + + bar_length)*Math.sin(i);

        context.moveTo(start_x + centerX + lxs, start_y + centerY + lys);
        context.lineTo(start_x + centerX + lxe, start_y + centerY + lye);
      }
      context.stroke();

      var size = radius;

      if(errorCode == "1")
      {
      data = errorMessage;
      }
      var unitsandval = data +units;
      var valsize;
      if (unitsandval.length >4){ valsize = (size / (unitsandval.length+2)) * 5.5;}
      else {valsize = (size / 6) * 5.5;}
      var titlesize ;
      if (title.length >10) {titlesize = (size / (title.length+2)) * 9;}
      else {titlesize = (size / 12) * 9.5;}

      context.fillStyle = color;
      context.textAlign = "center";
      context.font = (fontstyle+ " "+ fontweight+ " "+(valsize*0.50)+"px "+ fontname);
      if (errorCode === "1"){context.fillText(errorMessage, start_x + sun_width/2, start_y + centerY - radius*0.2);}
      else{
      if (unitend ==="0"){context.fillText(data + units, start_x + sun_width/2, start_y + centerY - radius*0.2);}
      if (unitend ==="1"){context.fillText(units + data, start_x + sun_width/2, start_y + centerY - radius*0.2);}
      }
      if(title)
      {
        context.fillStyle = color;
        context.textAlign = "center";
        context.font = (fontstyle+ " "+ fontweight+ " "+(titlesize*0.25)+"px "+ fontname);
        context.fillText(title, start_x + sun_width/2, start_y + centerY - radius*0.6);
      }
    }
  });
}

function sun_slowupdate(){}

function sun_fastupdate(){
  sun_draw();
}
