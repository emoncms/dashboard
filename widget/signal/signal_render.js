/*
   All emon_widgets code is released under the GNU General Public License v3.
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

function signal_widgetlist(){
  var widgets =
  {
    "signal":
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

  addOption(widgets["signal"], "feedid",      "feedid",  _Tr("Feed"),        _Tr("Feed value"),                                                            []);
  addOption(widgets["signal"], "max",         "value",   _Tr("Max value"),   _Tr("Max value to show"),                                                     []);
  addOption(widgets["signal"], "scale",       "value",   _Tr("Scale"),       _Tr("Value is multiplied by scale before display"),                           []);
  // addOption(widgets["signal"], "units",       "value",   _Tr("Units"),       _Tr("Units to show"),                                                      []);
  addOption(widgets["signal"], "offset",      "value",   _Tr("Offset"),      _Tr("Static offset. Subtracted from value before computing needle position"), []);
  addOption(widgets["signal"], "signal_title",       "value",   _Tr("Signal title"),       _Tr("Signal title"),                                             []);
  addOption(widgets["signal"], "colour1",      "colour_picker",   _Tr("Colour signal"),      _Tr("Color of the signal"),                                      []);
  addOption(widgets["signal"], "colour",      "colour_picker",   _Tr("Colour label"),      _Tr("Color of the label"),                 []);
  addOption(widgets["signal"], "font",      "dropbox",   _Tr("Font"),      _Tr("Label font"),                                                  fontoptions);

  return widgets;
}

function signal_init(){
  setup_widget_canvas('signal');
}

function signal_draw(){
  $('.signal').each(function(index) {
    var feedid = $(this).attr("feedid");
    if (associd[feedid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    var val = curve_value(feedid,dialrate).toFixed(3);
    // ONLY UPDATE ON CHANGE
    if (val != (associd[feedid]['value'] * 1).toFixed(3) || redraw == 1)
    {
      var id = "can-"+$(this).attr("id");
      var scale = 1*$(this).attr("scale") || 1;
      var offset = 1*$(this).attr("offset") || 0;
      var max_val = 1*$(this).attr("max") || 100;
      // var units = $(this).attr("units");
      var font = $(this).attr("font");
      var color = $(this).attr("colour") || "000";
      var title = $(this).attr("signal_title");
      var colour1 = $(this).attr("colour1") || "000";

      if (font == 0){fontname = "Impact"}
      if (font == 1){fontname = "Georgia"}
      if (font == 2){fontname = "Arial"}
      if (font == 3){fontname = "Courier New"}
      if (font == 4){fontname = "Comic Sans MS"}
      if (font == 5){fontname = "Helvetica"}
      if (font == 6){fontname = "sans-serif"}
      if (font == 7){fontname = "Arial Narrow"}
      if (font == 8){fontname = "Arial Black"}
      else if (typeof(font) == "undefined") {fontname = "Arial Black"}

      console.log(color, colour1);
      if (color.indexOf("#") == -1) color = "#" + color;
      if (colour1.indexOf("#") == -1) colour1 = "#" + colour1;

      var start_x = 0, start_y = 0;
      var signal_height = $(this).height();
      var signal_width = $(this).width();
      var line_width = signal_width/20;
      var margin = 1;
      var number_of_blocks = 5;

      var data = val*scale + offset;
      
      if(data > max_val)
        data = max_val;

      if (data>=100) {
          data = data.toFixed(0);
      } else if (data>=10) {
          data = data.toFixed(1);
      } else  {
          data = data.toFixed(2);
      }
      data = parseFloat(data);

      var context = widgetcanvas[id];

      context.globalAlpha = 1;

      var bar_length = 10;
      var radius = signal_width/(0.5*Math.PI);
      var block_width = radius/number_of_blocks - line_width;
  
      var centerX = signal_width/2;
      var centerY = signal_height;

      var stroke_style = colour1;
      var stroke_style_empty = "#BDBDBD";

      var arc_radius = radius;

      var signal_bars = Math.ceil(number_of_blocks*(data/max_val));
      console.log("signal", signal_bars);

      for(var i=0; i<number_of_blocks; i++)
      {
        var angle = 0;
        arc_radius -= block_width;
        if(arc_radius < 0)
          break;

        
        context.beginPath();
        context.arc(start_x + centerX, start_y + centerY, arc_radius, (1.25+angle) * Math.PI, (1.75-angle) * Math.PI, false);

        context.lineWidth = block_width;
        if(signal_bars >= number_of_blocks - i)
          context.strokeStyle = stroke_style;
        else
          context.strokeStyle = stroke_style_empty;
        context.stroke();

        arc_radius -= line_width;
      }

      var size = radius/2;
      // context.font      = fontname;
      // context.fillStyle = color;
      // context.textAlign = "center";
      // context.font = ((size*0.50)+"px "+ fontname);
      // context.fillText(data + units, start_x + signal_width/2, start_y + centerY - radius*0.2);

      if(title)
      {
        context.fillStyle = color;
        context.textAlign = "center";
        context.font = ((size*0.20)+"px "+ fontname);
        context.fillText(title, start_x + signal_width/2, start_y + centerY - radius*0.6);
      }
    }
  });
}

function signal_slowupdate(){}

function signal_fastupdate(){
  signal_draw();
}
