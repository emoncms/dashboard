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

  var fontoptions = [
    [9, "Arial Black"],
    [8, "arial"],
    [7, "Arial Narrow"],
    [6, "sans-serif"],
    [5, "Helvetica"],
    [4, "Comic Sans MS"],
    [3, "Courier New"],
    [2, "arial"],
    [1, "Georgia"],
    [0, "Impact"]
  ];

  addOption(widgets["sun"], "feedid",      "feedid",  _Tr("Feed"),        _Tr("Feed value"),                                                            []);
  addOption(widgets["sun"], "max",         "value",   _Tr("Max value"),   _Tr("Max value to show"),                                                     []);
  addOption(widgets["sun"], "scale",       "value",   _Tr("Scale"),       _Tr("Value is multiplied by scale before display"),                           []);
  addOption(widgets["sun"], "units",       "value",   _Tr("Units"),       _Tr("Units to show"),                                                         []);
  addOption(widgets["sun"], "offset",      "value",   _Tr("Offset"),      _Tr("Static offset. Subtracted from value before computing needle position"), []);
  addOption(widgets["solar"], "solar_title",       "value",   _Tr("solar title"),       _Tr("Solar title"),                                     []);
  addOption(widgets["solar"], "colour",      "colour_picker",   _Tr("Colour label"),      _Tr("Color of the label"),                                  []);
  addOption(widgets["solar"], "font",      "dropbox",   _Tr("Font"),      _Tr("Label font"),                                                  fontoptions);

  return widgets;
}

function sun_init(){
  setup_widget_canvas('sun');
}

function sun_draw(){
  $('.sun').each(function(index) {
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
      var units = $(this).attr("units");
      var font = $(this).attr("font");
      var color = $(this).attr("colour") || "000";
      var title = $(this).attr("battery_title");

      if (font == 0){fontname = "Impact"}
      if (font == 1){fontname = "Georgia"}
      if (font == 2){fontname = "arial"}
      if (font == 3){fontname = "Courier New"}
      if (font == 4){fontname = "Comic Sans MS"}
      if (font == 5){fontname = "Helvetica"}
      if (font == 6){fontname = "sans-serif"}
      if (font == 7){fontname = "Arial Narrow"}
      if (font == 8){fontname = "Arial"}
      if (font == 9){fontname = "Arial Black"}
      else if (typeof(font) == "undefined") {fontname = "Arial Black"}

      if (color.indexOf("#") == -1) color = "#" + color;

      var start_x = 0, start_y = 0;
      var sun_height = $(this).height();
      var sun_width = $(this).width();
      var line_width = 2;
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
      var radius = sun_height>sun_width?(sun_height - bar_length)/3:(sun_width - bar_length)/3;
  
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
      context.lineWidth = line_width;
      context.strokeStyle = '#ffffff';
      context.stroke();

      //Draw the bars
      var bar_width = 5;
      var number_of_bars = 5;

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

      var size = ((sun_width<sun_height)?sun_width:sun_height)/2;
      context.font      = fontname;
      context.fillStyle = color;
      context.textAlign = "center";
      context.font = ((size*0.50)+"px "+ fontname);
      context.fillText(data + units, start_x + sun_width/2, start_y + sun_height*0.6);

      if(title)
      {
        context.fillStyle = color;
        context.textAlign = "center";
        context.font = ((size*0.20)+"px "+ fontname);
        context.fillText(title, start_x + sun_width/2, start_y + sun_height*0.25);
      }
    }
  });
}

function sun_slowupdate(){}

function sun_fastupdate(){
  sun_draw();
}
