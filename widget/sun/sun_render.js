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

    // Normal first, as this widget has always offered it. The shared list
    // puts Bold first, which would change the default of a new widget.
    var fweightoptions = [
        [0, _Tr("Normal")],
        [1, _Tr("Bold")]
    ];

    addOption(widgets["sun"],   "feedid",                "feedid",          _Tr("Feed"),          _Tr("Feed value"),                                            []);
    addOption(widgets["sun"],   "max",                   "value",           _Tr("Max value"),     _Tr("Max value to show"),                                     []);
    addOption(widgets["sun"],   "scale",                 "value",           _Tr("Scale"),         _Tr("Value is multiplied by scale before display"),           []);
    addOption(widgets["sun"],   "units",                 "dropbox_other",   _Tr("Units"),         _Tr("Units to show"),                                         _SI);
    addOption(widgets["sun"],   "unitend",               "dropbox",         _Tr("Unit position"), _Tr("Where should the unit be shown"),                        widget_unitend_options());
    addOption(widgets["sun"],   "decimals",              "dropbox",         _Tr("Decimals"),      _Tr("Decimals to show"),                                      widget_decimals_options());
    addOption(widgets["sun"],   "offset",                "value",           _Tr("Offset"),        _Tr("Static offset. Subtracted from value before computing"), []);
    addOption(widgets["sun"],   "solar_title",           "value",           _Tr("solar title"),   _Tr("Solar title"),                                           []);
    addOption(widgets["sun"],   "colour",                "colour_picker",   _Tr("Colour label"),  _Tr("Color of the label"),                                    []);
    addOption(widgets["sun"],   "font",                  "dropbox",         _Tr("Font"),          _Tr("Label font"),                                            widget_font_options(true));
    addOption(widgets["sun"],   "fstyle",                "dropbox",         _Tr("Font style"),    _Tr("Font style used for display"),                           widget_style_options());
    addOption(widgets["sun"],   "fweight",               "dropbox",         _Tr("Font weight"),   _Tr("Font weight used for display"),                          fweightoptions);
    addOption(widgets["sun"],   "timeout",               "value",           _Tr("Timeout"),       _Tr("Timeout without feed update in seconds (empty is never)"),[]);
    addOption(widgets["sun"],   "errormessagedisplayed", "value",           _Tr("Error Message"), _Tr("Error message displayed when timeout is reached"),        []);

    return widgets;
}


var sun_widget = {
    mount: function(el, config, ctx){
        var canvas = ctx.canvas(el);
        var feeds = ctx.feeds;

        // Reading eases towards its value over several frames.
        var curve = 0;
        var force = true;

        var draw = function(){
            var feed = feeds.get(config.feedid);
            if (!feed) return;
            curve = render_curve(curve, feed.value);
            var val = curve.toFixed(3);

            var timeout = widget_timeout(config, feed);
            var errorCode = timeout.code;
            var errorMessage = timeout.message;
            var errorTimeout = parseFloat(config.timeout) || 0;

            // ONLY UPDATE ON CHANGE
            if (val != (feed.value * 1).toFixed(3) || force || errorTimeout != 0)
            {
                var scale = 1*config.scale || 1;
                var offset = 1*config.offset || 0;
                var max_val = 1*config.max || 100;
                var units = config.units || "";
                var decimals = config.decimals;
                var unitend = config.unitend || "0";
                var font = widget_font(config.font || "8", config.fstyle || "2", config.fweight || "0", undefined, true);
                var color = widget_colour(config.colour, "000");
                var title = config.solar_title || "";

                var sun_height = el.clientHeight;
                var sun_width = el.clientWidth;
                var line_width = 2;

                var data = val*scale + offset;

                if(data > max_val)
                data = max_val;

                data = widget_decimals(data, decimals);

                var context = canvas.context;

                context.clearRect(0,0,sun_width,sun_height);
                context.globalAlpha = 1;

                var bar_length = 10;
                var bar_width = 5;

                var radius = Math.max(Math.min(sun_width/2,sun_height) - bar_length - bar_width,0);

                var centerX = sun_width / 2;
                var centerY = sun_height;

                context.beginPath();
                context.arc(centerX, centerY, radius, 1 * Math.PI, 2 * Math.PI, false);
                context.closePath();

                context.fillStyle = "rgb(255, 228, 124)";
                context.fill();
                context.lineWidth = line_width+2;
                context.strokeStyle = "#ffffff";
                context.stroke();

                //Draw the bars

                context.lineWidth = line_width;
                context.strokeStyle = "#FFE87C";
                context.beginPath();
                var lxs, lys, lxe, lye;
                var theta = 1;
                for(var i=180;i<360;i+=theta)
                {
                    lxs = (radius+line_width)*Math.cos(i);
                    lys = (radius+line_width)*Math.sin(i);

                    lxe = (radius+line_width + bar_length)*Math.cos(i);
                    lye = (radius+line_width + bar_length)*Math.sin(i);

                    context.moveTo(centerX + lxs, centerY + lys);
                    context.lineTo(centerX + lxe, centerY + lye);
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
                context.font = (font.style+ " "+ font.weight+ " "+(valsize*0.50)+"px "+ font.name);
                if (errorCode === "1"){context.fillText(errorMessage, sun_width/2, centerY - radius*0.2);}
                else{
                    if (unitend ==="0"){context.fillText(data + units, sun_width/2, centerY - radius*0.2);}
                    if (unitend ==="1"){context.fillText(units + data, sun_width/2, centerY - radius*0.2);}
                }
                if(title)
                {
                    context.fillStyle = color;
                    context.textAlign = "center";
                    context.font = (font.style+ " "+ font.weight+ " "+(titlesize*0.25)+"px "+ font.name);
                    context.fillText(title, sun_width/2, centerY - radius*0.6);
                }
                force = false;
            }
        };

        return {
            update: function(live){ feeds = live; },
            frame: function(){ draw(); },
            resize: function(){ canvas.fit(); force = true; draw(); },
            destroy: function(){}
        };
    }
};
