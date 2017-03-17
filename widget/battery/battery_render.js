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

  addOption(widgets["battery"], "feedid",      "feedid",  _Tr("Feed"),        _Tr("Feed value"),                                                            []);
  addOption(widgets["battery"], "max",         "value",   _Tr("Max value"),   _Tr("Max value to show"),                                                     []);
  addOption(widgets["battery"], "scale",       "value",   _Tr("Scale"),       _Tr("Value is multiplied by scale before display"),                           []);
  addOption(widgets["battery"], "units",       "value",   _Tr("Units"),       _Tr("Units to show"),                                                         []);
  addOption(widgets["battery"], "offset",      "value",   _Tr("Offset"),      _Tr("Static offset. Subtracted from value before computing needle position"), []);

  return widgets;
}

function battery_init(){
  setup_widget_canvas('battery');
}

function battery_draw(){
  $('.battery').each(function(index) {
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

      var start_x = 0, start_y = 0;
      var battery_height = $(this).height();
      var battery_width = $(this).width();
      var cap_height = battery_height/16;
      var cap_width = battery_width/3;
      var line_width = 2;
      var margin = 1;
      var number_of_blocks = 5;

      var data = val*scale + offset;
      
      if(data > max_val)
        data = max_val;

      var context = widgetcanvas[id];
      context.globalAlpha = 1;
      
      //Drawing the battery cap
      context.fillStyle = "#ffffff";
        context.fillRect(start_x + battery_width/2 - cap_width/2, start_y, cap_width, cap_height);
        
        //Clear the battery area first
      context.fillStyle = "#ffffff";
      context.fillRect(start_x, start_y + cap_height, battery_width, battery_height-cap_height);
        //Drawing body outline
        context.strokeStyle = "#000f00";
      context.lineWidth   = line_width;
      context.strokeRect(start_x, start_y + cap_height, battery_width, battery_height-cap_height);
      //context.fillStyle = "#000000";
      //context.fillRect(start_x + margin, start_y + cap_height + margin, battery_width - margin, battery_height-cap_height-margin);
      //Filling body
      var block_width = battery_width - 2*(line_width + margin);
      var block_height = Math.ceil((battery_height - cap_height - 2*(line_width + margin))/number_of_blocks) - 2*margin;
      var block_start_y = start_y + battery_height - line_width - margin;
      
      var last_block = Math.ceil(number_of_blocks*data/100);
      var green_val = Math.floor(data*255/100);//<49?255:0;


      var red_val = 255 - green_val;
      var linearGradient1 = context.createLinearGradient(start_x + line_width + margin, 
                                        start_y + cap_height + line_width + margin, 
                                        start_x + line_width + margin + block_width, 
                                        start_y + cap_height + line_width + margin);
      linearGradient1.addColorStop(0, 'rgb('+red_val+', '+green_val+', 0)');
      linearGradient1.addColorStop(0.5, 'rgb(0, 0, 0)');
      linearGradient1.addColorStop(1, 'rgb('+red_val+','+green_val+', 0)');
      
      for(var i=1;i <= last_block; i++)
      {
        var y_pos = start_y + block_start_y - i*(block_height + margin);
        
        context.fillStyle = linearGradient1;
        context.fillRect(start_x + line_width + margin, y_pos, block_width, block_height);
      }

      context.font      = "16px Verdana";
      context.fillStyle = "#ff0000";
      context.fillText(data.toFixed(1) + units, start_x + battery_width/4, start_y + battery_height/2+10, battery_width);
    }
  });
}

function battery_slowupdate(){}

function battery_fastupdate(){
  battery_draw();
}
