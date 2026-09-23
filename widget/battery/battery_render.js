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

    // Normal first, so a new battery is not bold. Kept apart from the helper
    // list, which offers Bold first.
    var fweightoptions = [
        [0, _Tr("Normal")],
        [1, _Tr("Bold")]
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
    addOption(widgets["battery"], "unitend",               "dropbox",       _Tr("Unit position"),   _Tr("Where should the unit be shown"),                                        widget_unitend_options());
    addOption(widgets["battery"], "number_of_blocks",      "value",         _Tr("Number of blocks"),_Tr("Number of blocks to display"),                                           []);
    addOption(widgets["battery"], "decimals",              "dropbox",       _Tr("Decimals"),        _Tr("Decimals to show"),                                                      widget_decimals_options());
    addOption(widgets["battery"], "offset",                "value",         _Tr("Offset"),          _Tr("Static offset. Subtracted from value before computing"),                 []);
    addOption(widgets["battery"], "colour",                "colour_picker", _Tr("Colour label"),    _Tr("Color of the label"),                                                    []);
    addOption(widgets["battery"], "font",                  "dropbox",       _Tr("Font"),            _Tr("Label font"),                                                            widget_font_options(true));
    addOption(widgets["battery"], "fstyle",                "dropbox",       _Tr("Font style"),      _Tr("Font style used for display"),                                           widget_style_options());
    addOption(widgets["battery"], "fweight",               "dropbox",       _Tr("Font weight"),     _Tr("Font weight used for display"),                                          fweightoptions);
    addOption(widgets["battery"], "battery_style",         "dropbox",       _Tr("Style"),           _Tr("Display style"),                                                         StyleOptions);
    addOption(widgets["battery"], "timeout",               "value",         _Tr("Timeout"),         _Tr("Timeout without feed update in seconds (empty is never)"),               []);
    addOption(widgets["battery"], "errormessagedisplayed", "value",         _Tr("Error Message"),   _Tr("Error message displayed when timeout is reached"),                       []);

    return widgets;
}


var battery_widget = {
    mount: function(el, config, ctx){
        var canvas = ctx.canvas(el);
        var feeds = ctx.feeds;

        // Reading eases towards its value over several frames.
        var curve = 0;
        var force = true;
        var last_code = null;

        var draw = function(){
            var feed = feeds.get(config.feedid);
            if (!feed) return;
            curve = render_curve(curve, feed.value);
            var val = curve.toFixed(3);

            var timeout = widget_timeout(config, feed);
            var errorCode = timeout.code;

            // Only drawn on change
            if (val != (feed.value * 1).toFixed(3) || force || errorCode !== last_code)
            {
                var scale = 1*config.scale || 1;
                var offset = 1*config.offset || 0;
                var max_val = 1*config.max || 100;
                var min_val = 1*config.min || 0;
                var units = config.units;
                var decimals = config.decimals;
                var font = config.font|| "8";
                var fstyle = config.fstyle || "2";
                var fweight = config.fweight || "0";
                var unitend = config.unitend || "0";
                var color = widget_colour(config.colour, "000");
                var title = config.battery_title || "";
                var battery_style = config.battery_style || "1";

                var battery_height = el.clientHeight;
                var battery_width = el.clientWidth;
                var cap_height = battery_height/16;
                var cap_width = battery_width/3;
                var line_width = 2;
                var margin = 1;
                var number_of_blocks = Math.round(1*config.number_of_blocks) || 5;

                var f = widget_font(font, fstyle, fweight, undefined, true);
                var fontcss = function(px){ return f.style + " " + f.weight + " " + px + "px " + f.name; };

                var raw_value = val*scale + offset;
                var data;

                if(raw_value > max_val){
                    data = max_val;} // Clamp value so we don't overshoot the top of the battery.
                else{
                    data=raw_value;}

                raw_value = widget_decimals(raw_value, decimals);

                var context = canvas.context;
                context.globalAlpha = 1;
                context.clearRect(0,0,battery_width,battery_height);

                //Drawing the battery cap
                context.fillStyle = "#ffffff";
                context.fillRect(battery_width/2 - cap_width/2, line_width, cap_width, cap_height + line_width*2);
                context.strokeStyle = "#000f00";
                context.lineWidth   = line_width;
                context.strokeRect(battery_width/2 - cap_width/2, line_width, cap_width, cap_height + line_width*2);
                //Clear the battery area first
                context.fillStyle = "#ffffff";
                context.fillRect(line_width, line_width + cap_height, battery_width - line_width*2, battery_height-cap_height);
                //Drawing body outline
                context.strokeStyle = "#000f00";
                context.lineWidth   = line_width;
                context.strokeRect(line_width, line_width + cap_height, battery_width - line_width*2, battery_height-cap_height - line_width*2);

                //Filling body
                var block_width = battery_width - 2*(line_width*2 + margin);
                var block_height = Math.ceil((battery_height - cap_height - 2*(line_width*2 + margin))/number_of_blocks) - 2*margin;
                var block_start_y = battery_height - line_width*2 - margin;

                var last_block = Math.ceil((number_of_blocks*(data-min_val))/(max_val - min_val));
                var green_val = Math.floor(((data-min_val)*255)/(max_val - min_val));
                if (green_val<0) green_val = 0;
                if (green_val>255) green_val = 255;
                if (isNaN(green_val)) green_val = 0;

                var red_val = 255 - green_val;

                // Blocks are not drawn on a timeout
                if (errorCode !== "1"){
                    var linearGradient1 = context.createLinearGradient(line_width*2 + margin,
                        cap_height + line_width*2 + margin,
                        line_width*2 + margin + block_width,
                        cap_height + line_width*2 + margin);
                    linearGradient1.addColorStop(0, "rgb("+red_val+", "+green_val+", 0)");
                    if (battery_style ==="1") linearGradient1.addColorStop(0.5, "rgb(0, 0, 0)");
                    linearGradient1.addColorStop(1, "rgb("+red_val+","+green_val+", 0)");

                    context.fillStyle = linearGradient1;
                    for(var i=1;i <= last_block; i++)
                    {
                        var y_pos = block_start_y - i*(block_height + margin);
                        context.fillRect(line_width*2 + margin, y_pos, block_width, block_height);
                    }
                }

                var size = ((battery_width<battery_height)?battery_width:battery_height)/2;
                var unitsandval = raw_value + units;
                if(errorCode == "1")
                {
                    unitsandval = timeout.message;
                }
                var valsize;
                if (unitsandval.length >4){ valsize = (size / (unitsandval.length+2)) * 5.5;}
                else {valsize = (size / 6) * 5.5;}
                var titlesize ;
                if (title.length >10) {titlesize = (size / (title.length+2)) * 9;}
                else {titlesize = (size / 12) * 9.5;}
                context.fillStyle = color;
                context.textAlign = "center";
                context.font = fontcss(valsize*0.5);
                if (errorCode === "1"){context.fillText(timeout.message, battery_width/2, battery_height*0.6);}
                else{
                    if (unitend ==="0"){context.fillText(raw_value + units, battery_width/2, battery_height*0.6);}
                    if (unitend ==="1"){context.fillText(units + raw_value, battery_width/2, battery_height*0.6);}
                }

                if(title)
                {
                    context.fillStyle = color;
                    context.textAlign = "center";
                    context.font = fontcss(titlesize*0.30);
                    context.fillText(title, battery_width/2, battery_height*0.25);
                }
                force = false;
            }
            last_code = errorCode;
        };

        return {
            update: function(live){ feeds = live; },
            frame: function(){ draw(); },
            resize: function(){ canvas.fit(); force = true; draw(); },
            destroy: function(){}
        };
    }
};
