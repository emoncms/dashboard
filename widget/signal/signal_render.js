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

    // Normal first, as this widget has always offered it. The shared list
    // puts Bold first, which would change the default of a new widget.
    var fweightoptions = [
        [0, _Tr("Normal")],
        [1, _Tr("Bold")]

    ];

    addOption(widgets["signal"], "feedid",                "feedid",         _Tr("Feed"),           _Tr("Feed value"),                                             []);
    addOption(widgets["signal"], "max",                   "value",          _Tr("Max value"),      _Tr("Max value to show"),                                      []);
    addOption(widgets["signal"], "scale",                 "value",          _Tr("Scale"),          _Tr("Value is multiplied by scale before display"),            []);
    addOption(widgets["signal"], "offset",                "value",          _Tr("Offset"),         _Tr("Static offset. Subtracted from value before computing"),  []);
    addOption(widgets["signal"], "signal_title",          "value",          _Tr("Signal title"),   _Tr("Signal title"),                                           []);
    addOption(widgets["signal"], "colour1",               "colour_picker",  _Tr("Colour signal"),  _Tr("Color of the signal"),                                    []);
    addOption(widgets["signal"], "colour",                "colour_picker",  _Tr("Colour label"),   _Tr("Color of the label"),                                     []);
    addOption(widgets["signal"], "font",                  "dropbox",        _Tr("Font"),           _Tr("Label font"),                                             widget_font_options(true));
    addOption(widgets["signal"], "fstyle",                "dropbox",        _Tr("Font style"),     _Tr("Font style used for display"),                            widget_style_options());
    addOption(widgets["signal"], "fweight",               "dropbox",        _Tr("Font weight"),    _Tr("Font weight used for display"),                           fweightoptions);
    addOption(widgets["signal"], "timeout",               "value",          _Tr("Timeout"),        _Tr("Timeout without feed update in seconds (empty is never)"),[]);
    addOption(widgets["signal"], "errormessagedisplayed", "value",          _Tr("Error Message"),  _Tr("Error message displayed when timeout is reached"),        []);

    return widgets;
}


var signal_widget = {
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
                var font = widget_font(config.font || "8", config.fstyle || "2", config.fweight || "0", undefined, true);
                var color = widget_colour(config.colour, "000");
                var title = config.signal_title;
                var colour1 = widget_colour(config.colour1, "000");

                var signal_height = el.clientHeight;
                var signal_width = el.clientWidth;
                var line_width = Math.min(signal_width,signal_height)/20;
                var number_of_blocks = 5;

                var data = val*scale + offset;

                if(data > max_val){
                    data = max_val;}

                data = parseFloat(widget_decimals(data, -1));

                var context = canvas.context;

                context.clearRect(0,0,signal_width,signal_height); // Clear old drawing

                context.globalAlpha = 1;

                var radius = Math.min(signal_width,signal_height*1.6)/(0.5*Math.PI);
                var block_width = radius/number_of_blocks - line_width;

                var centerX = signal_width/2;
                var centerY = signal_height;

                var stroke_style = colour1;
                var stroke_style_empty = "#BDBDBD";

                var arc_radius = radius;

                var signal_bars = Math.ceil(number_of_blocks*(data/max_val));

                if (errorCode == "1")
                {
                    signal_bars=0;
                }

                for(var i=0; i<number_of_blocks; i++)
                {
                    var angle = 0;
                    arc_radius -= block_width;
                    if(arc_radius < 0){break;}

                    context.beginPath();
                    context.arc(centerX, centerY, arc_radius, (1.25+angle) * Math.PI, (1.75-angle) * Math.PI, false);

                    context.lineWidth = block_width;
                    if(signal_bars >= number_of_blocks - i){context.strokeStyle = stroke_style;}
                    else {context.strokeStyle = stroke_style_empty;}
                    context.stroke();

                    arc_radius -= line_width;
                }

                var size = radius/2;

                if(title)
                {
                    var titlesize ;
                    if (title.length >10) {titlesize = (size / (title.length+2)) * 9;}
                    else {titlesize = (size / 12) * 9.5;}
                    context.fillStyle = color;
                    context.textAlign = "center";
                    context.font = (font.style+ " "+ font.weight+ " "+ (titlesize*0.70)+"px "+ font.name);
                    context.fillText(title, signal_width/2, centerY - radius*0.6);
                }
                if (errorCode == "1")
                {
                    var errorMessagesize ;
                    if (errorMessage.length >10) {errorMessagesize = (size / (errorMessage.length+2)) * 9;}
                    else {errorMessagesize = (size / 12) * 9.5;}
                    context.fillStyle = color;
                    context.textAlign = "center";
                    context.font = (font.style+ " "+ font.weight+ " "+ (errorMessagesize*0.70)+"px "+ font.name);
                    context.fillText(errorMessage, signal_width/2, centerY - radius*0.3);
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
