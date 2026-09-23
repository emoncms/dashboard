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

function bar_widgetlist()
{
    var widgets =
    {
        "bar":
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

    // Normal first, so a new bar is not bold. Kept apart from the helper
    // list, which offers Bold first.
    var fweightoptions = [
        [0, _Tr("Normal")],
        [1, _Tr("Bold")]
    ];

    addOption(widgets["bar"], "title_bar",      "value",            _Tr("Title"),           _Tr("Title of bar"),                                                                []);
    addOption(widgets["bar"], "colour_label",   "colour_picker",    _Tr("Label Colour"),    _Tr("Colour of title and values"),                                                  []);
    addOption(widgets["bar"], "font",           "dropbox",          _Tr("Font used"),       _Tr("Font used"),                                                                   widget_font_options());
    addOption(widgets["bar"], "fstyle",         "dropbox",          _Tr("Font style"),      _Tr("Font style used for display"),                                                 widget_style_options());
    addOption(widgets["bar"], "fweight",        "dropbox",          _Tr("Font weight"),     _Tr("Font weight used for display"),                                                fweightoptions);
    addOption(widgets["bar"], "feedid",         "feedid",           _Tr("Feed"),            _Tr("Feed value"),                                                                  []);
    addOption(widgets["bar"], "max",            "value",            _Tr("Max value"),       _Tr("Max value to show"),                                                           []);
    addOption(widgets["bar"], "scale",          "value",            _Tr("Scale"),           _Tr("Value is multiplied by scale before display. Defaults to 1"),                  []);
    addOption(widgets["bar"], "units",          "dropbox_other",    _Tr("Units"),           _Tr("Units to show"),                                                               _SI);
    addOption(widgets["bar"], "unitend",        "dropbox",          _Tr("Unit position"),   _Tr("Where should the unit be shown"),                                              widget_unitend_options());
    addOption(widgets["bar"], "decimals",       "dropbox",          _Tr("Decimals"),        _Tr("Decimals to show"),                                                            widget_decimals_options());
    addOption(widgets["bar"], "offset",         "value",            _Tr("Offset"),          _Tr("Static offset. Subtracted from value before computing"),                       []);
    addOption(widgets["bar"], "colour",         "colour_picker",    _Tr("Colour"),          _Tr("Colour to draw bar in"),                                                       []);
    addOption(widgets["bar"], "graduations",    "dropbox",          _Tr("Graduations"),     _Tr("Should the graduations be shown"),                                             widget_yesno_options());
    addOption(widgets["bar"], "gradNumber",     "value",            _Tr("Grad. Num."),      _Tr("How many graduation lines to draw (only relevant if graduations are on)"),     []);
    addOption(widgets["bar"], "displayminmax",  "dropbox",          _Tr("Min / Max ?"),     _Tr("Display Min. and Max. ?"),                                                     widget_yesno_options());
    addOption(widgets["bar"], "minvaluefeed",   "feedid",           _Tr("Min. feed"),       _Tr("The feed for the minimum value"),                                              []);
    addOption(widgets["bar"], "maxvaluefeed",   "feedid",           _Tr("Max. feed"),       _Tr("The feed for the maximum value"),                                              []);
    addOption(widgets["bar"], "colour_minmax",  "colour_picker",    _Tr("Colour"),          _Tr("Colour for min. and max. bars"),                                               []);
    addOption(widgets["bar"], "timeout",        "value",            _Tr("Timeout"),         _Tr("Timeout without feed update in seconds (empty is never)"),                     []);
    addOption(widgets["bar"], "errormessagedisplayed",    "value",  _Tr("Error Message"),   _Tr("Error message displayed when timeout is reached"),                             []);


    return widgets;
}


// Draws the bar and returns the tooltip hotspots of the min and max lines,
// as {x, y, w, h, tip} in canvas pixels. Empty when min and max are off.
// config holds the widget settings. reading holds the scaled feed values as
// {title, value, min, max, displayminmax, errorCode, errorMessage}.
function draw_bar(context, width, height, config, reading)
{
    if (!context) return [];

    context.clearRect(0,0,width+10,height+10); // Clear old drawing

    var raw_value = reading.value;
    var minvaluefeed = reading.min;
    var maxvaluefeed = reading.max;
    var displayminmax = reading.displayminmax;
    var errorCode = reading.errorCode;
    var errorMessage = reading.errorMessage;

    var max_value = 1 * config.max || 3000;
    var units_string = config.units || "";
    var title_bar = reading.title || "";
    var font = config.font || "9";
    var fstyle = config.fstyle || "2";
    var fweight = config.fweight || "0";
    var unitend = config.unitend || "0";
    var decimals = config.decimals;
    var display_colour = config.colour;
    var colour_label = config.colour_label;
    var colour_minmax = config.colour_minmax;
    var graduationBool = config.graduations;
    var graduationQuant = config.gradnumber;
    var static_offset = 1*config.offset || 0;
    var display_value = raw_value - static_offset;

    // Scaled 0-1 values corresponding to min-max
    var scaled_value = display_value/max_value;
    if (scaled_value < 0){scaled_value = 0;}

    var min_scaled_value = (minvaluefeed-static_offset)/max_value;
    if (min_scaled_value < 0){min_scaled_value = 0;}

    var max_scaled_value = (maxvaluefeed-static_offset)/max_value;
    if (max_scaled_value < 0){max_scaled_value = 0;}

    raw_value = widget_decimals(raw_value, decimals);
    minvaluefeed = widget_decimals(minvaluefeed, decimals);
    maxvaluefeed = widget_decimals(maxvaluefeed, decimals);

    var size = 0;
    if (width<height){size = width/2;}
    else {size = height/2;}
    size = size - (size*0.058/2);

    var f = widget_font(font, fstyle, fweight);
    var fontcss = function(px){ return f.style + " " + f.weight + " " + px + "px " + f.name; };

    if (graduationBool === "1")
    {
        height = height - (size/2);
        width = width - (size);
    }

    var half_width = width/2;

    if (!display_value) {display_value = 0;}  // Clamp value so we don't draw negative values.

    context.lineWidth = 1;
    context.strokeStyle = "#000";
    var border_space = 5;
    context.strokeRect(border_space,
        border_space,
        width-(border_space*2),
        height-(border_space*2));
    context.lineWidth = 0;

    context.fillStyle = widget_colour(display_colour);

    var bar_border_space = 10;
    var bar_top = ((height-bar_border_space) - (scaled_value * (height - (bar_border_space*2))));

    if (bar_top < bar_border_space)     // Clamp value so we don't overshoot the top of the bargraph.
    {bar_top = bar_border_space;}

    context.fillRect(bar_border_space,
        bar_top,
        width-(bar_border_space*2),
        (height-bar_border_space) - bar_top );

    var bar_min = ((height-bar_border_space) - (min_scaled_value * (height - (bar_border_space*2))));
    if (bar_min < bar_border_space) {bar_min = bar_border_space;}

    var bar_max = ((height-bar_border_space) - (max_scaled_value * (height - (bar_border_space*2))));
    if (bar_max < bar_border_space) {bar_max = bar_border_space;}

    var hotspots = [];
    if(displayminmax==="1"){
        context.fillStyle = widget_colour(colour_minmax, "555");
        context.fillRect(bar_border_space, bar_min, width-(bar_border_space*2), 2);
        context.fillRect(bar_border_space, bar_max, width-(bar_border_space*2), 2);

        hotspots = [
            {x: bar_border_space, y: bar_min, w: width-(bar_border_space*2), h: 2, tip: minvaluefeed},
            {x: bar_border_space, y: bar_max, w: width-(bar_border_space*2), h: 2, tip: maxvaluefeed}
        ];
    }

    colour_label = widget_colour(colour_label, "000");
    context.fillStyle = colour_label;

    if (graduationBool == 1)
    {
        if (graduationQuant > 0)
        {
            context.textAlign    = "start";
            context.font = fontcss(size*0.15);

            var step = (height-border_space*2)/(Number(graduationQuant)+1);
            var curY;

            if (unitend ==="0"){context.fillText((static_offset+max_value)+units_string, width+(size*0.1), (size*0.15)+2);}
            if (unitend ==="1"){context.fillText(units_string+(static_offset+max_value), width+(size*0.1), (size*0.15)+2);}
            var divisions = Number(graduationQuant)+1;

            for (var y = 0; y < graduationQuant; y++)
            {
                curY = Number(((y+1)*step).toFixed(0))+0.5;  // Bin down so we're drawing in the middle of the pixel, so the line is exactly 1 px wide
                context.moveTo(border_space, curY);
                context.lineTo(width-border_space, curY);

                var unitOffset = Number(static_offset+((graduationQuant-y)*(max_value/divisions)));
                if (unitOffset < 1000)
                {unitOffset = unitOffset.toFixed(1);}
                else
                {unitOffset = unitOffset.toFixed(0);}
                if (unitend ==="0"){context.fillText(parseFloat(unitOffset)+units_string, width+(size*0.1), curY+(size*0.05));}
                if (unitend ==="1"){context.fillText(units_string+parseFloat(unitOffset), width+(size*0.1), curY+(size*0.05));}
            }
            if (unitend ==="0"){context.fillText(static_offset+units_string, width+(size*0.1), height-2);}
            if (unitend ==="1"){context.fillText(units_string+static_offset, width+(size*0.1), height-2);}

            context.strokeStyle = "#888";
            context.stroke();
        }
    }

    context.fillStyle = colour_label;

    var bartext;
    if (errorCode === "1")
    {
        bartext = errorMessage;
    }
    else
    {
        if (unitend ==="0"){bartext= raw_value+units_string;}
        if (unitend ==="1"){bartext= units_string+raw_value;}
    }

    var valsize;
    if (bartext.length >4){ valsize = (size / (bartext.length+2)) * 5.5;}
    else {valsize = (size / 6) * 5.5;}
    var titlesize ;
    if (title_bar.length >10) {titlesize = (size / (title_bar.length+2)) * 9;}
    else {titlesize = (size / 12) * 9.5;}

    if (graduationBool == 1) {
        context.textAlign    = "start";
        if (title_bar) {
            context.font = fontcss(valsize*0.3);
            context.fillText(bartext, bar_border_space, height + (size*0.42));
            context.font = fontcss(titlesize*0.35);
            context.fillText(title_bar, bar_border_space, height + (size * 0.2));
        } else {
            context.font = fontcss(valsize*0.45);
            context.fillText(bartext, bar_border_space, height + (size*0.3));
        }
    }
    else
    {
        context.textAlign    = "center";
        context.font = fontcss(valsize*0.5);
        context.fillText(bartext, half_width, height/2 + (size*0.2));
        context.font = fontcss(titlesize*0.4);
        context.fillText(title_bar, half_width, height/7 + (size *0.1));
    }

    context.fillStyle = "#000";

    return hotspots;
}

var bar_widget = {
    mount: function(el, config, ctx){
        var canvas = ctx.canvas(el);
        var canvasid = "can-" + config.id;
        canvas.canvas.id = canvasid;
        var tips = widget_tooltips(el, canvas.canvas, 2, canvasid);
        var feeds = ctx.feeds;

        // A dashboard saved before title_bar existed holds the title in title,
        // which the browser also shows as a tooltip over the box.
        var title_bar = config.title_bar || config.title || "";
        if (config.title) el.removeAttribute("title");

        // Each reading eases towards its value over several frames.
        var curve = { val: 0, minval: 0, maxval: 0 };
        var force = true;
        var last_code = null;

        var draw = function(){
            var feed = feeds.get(config.feedid);
            var minfeed = feeds.get(config.minvaluefeed || "0");
            var maxfeed = feeds.get(config.maxvaluefeed || "0");

            if (!feed) return;
            var val = (feed.value * 1).toFixed(3);
            curve.val = render_curve(curve.val, feed.value);
            var val_curve = curve.val.toFixed(3);

            // Min and max feed settings default to the first feed in the feed
            // list, which may not be public on a public dashboard. Values are 0
            // where the feed settings are not valid.
            var minval = 0;
            if (minfeed) {
                minval = (minfeed.value * 1).toFixed(3);
                curve.minval = render_curve(curve.minval, minfeed.value);
            }
            var minval_curve = curve.minval.toFixed(3);

            var maxval = 0;
            if (maxfeed) {
                maxval = (maxfeed.value * 1).toFixed(3);
                curve.maxval = render_curve(curve.maxval, maxfeed.value);
            }
            var maxval_curve = curve.maxval.toFixed(3);

            // Min and max are off when one of the feed settings is not valid
            var displayminmax = config.displayminmax || "0";
            if (!minfeed || !maxfeed) {
                displayminmax = "0";
            }

            var timeout = widget_timeout(config, feed);
            if (timeout.code === "1") val = 0;

            // Only drawn on change
            if (val_curve!=val || minval_curve!=minval || maxval_curve!=maxval || force || timeout.code !== last_code)
            {
                var scale = 1*config.scale || 1;
                tips.set(draw_bar(canvas.context, el.clientWidth, el.clientHeight, config, {
                    title: title_bar,
                    value: val*scale,
                    min: minval*scale,
                    max: maxval*scale,
                    displayminmax: displayminmax,
                    errorCode: timeout.code,
                    errorMessage: timeout.message
                }));
                force = false;
            }
            last_code = timeout.code;
        };

        return {
            update: function(live){ feeds = live; },
            frame: function(){ draw(); },
            resize: function(){ canvas.fit(); force = true; draw(); },
            destroy: function(){ tips.destroy(); }
        };
    }
};
