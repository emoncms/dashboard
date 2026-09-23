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

    // Normal first, as this widget has always offered it. The shared list
    // puts Bold first, which would change the default of a new widget.
    var fweightoptions = [
        [0, _Tr("Normal")],
        [1, _Tr("Bold")]
    ];

    addOption(widgets["thermometer"], "titleThermometer",      "value",            _Tr("Title"),     _Tr("Title of thermometer"),                                                                []);
    addOption(widgets["thermometer"], "colourLabel",   "colour_picker",     _Tr("Label Colour"),    _Tr("Colour of title and values"),                                                  []);
    addOption(widgets["thermometer"], "font",           "dropbox",          _Tr("Font used"),       _Tr("Font used"),                                                                   widget_font_options());
    addOption(widgets["thermometer"], "fstyle",         "dropbox",          _Tr("Font style"),      _Tr("Font style used for display"),                                                 widget_style_options());
    addOption(widgets["thermometer"], "fweight",        "dropbox",          _Tr("Font weight"),     _Tr("Font weight used for display"),                                                fweightoptions);
    addOption(widgets["thermometer"], "feedid",         "feedid",           _Tr("Feed"),            _Tr("Feed value"),                                                                  []);
    addOption(widgets["thermometer"], "min",            "value",            _Tr("Min value"),       _Tr("Min value to show"),                                                           []);
    addOption(widgets["thermometer"], "max",            "value",            _Tr("Max value"),       _Tr("Max value to show"),                                                           []);
    addOption(widgets["thermometer"], "scale",          "value",            _Tr("Scale"),           _Tr("Value is multiplied by scale before display. Defaults to 1"),                  []);
    addOption(widgets["thermometer"], "units",          "dropbox_other",    _Tr("Units"),           _Tr("Units to show"),                                                               _SI);
    addOption(widgets["thermometer"], "unitend",        "dropbox",          _Tr("Unit position"),   _Tr("Where should the unit be shown"),                                              widget_unitend_options());
    addOption(widgets["thermometer"], "decimals",       "dropbox",          _Tr("Decimals"),        _Tr("Decimals to show"),                                                            widget_decimals_options());
    addOption(widgets["thermometer"], "offset",         "value",            _Tr("Offset"),          _Tr("Static offset. Subtracted from value before computing"),                       []);
    addOption(widgets["thermometer"], "graduations",    "dropbox",          _Tr("Graduations"),     _Tr("Should the graduations be shown"),                                             widget_yesno_options());
    addOption(widgets["thermometer"], "gradNumber",     "value",            _Tr("Grad. Num."),      _Tr("How many graduation lines to draw (only relevant if graduations are on)"),     []);
    addOption(widgets["thermometer"], "displayminmax",  "dropbox",          _Tr("Min / Max ?"),     _Tr("Display Min. and Max. ?"),                                                     widget_yesno_options());
    addOption(widgets["thermometer"], "minvaluefeed",   "feedid",           _Tr("Min. feed"),       _Tr("The feed for the minimum value"),                                              []);
    addOption(widgets["thermometer"], "maxvaluefeed",   "feedid",           _Tr("Max. feed"),       _Tr("The feed for the maximum value"),                                              []);
    addOption(widgets["thermometer"], "colourMinMax",  "colour_picker",     _Tr("Colour"),          _Tr("Colour for min. and max. bars"),                                               []);
    addOption(widgets["thermometer"], "timeout",       "value",             _Tr("Timeout"),         _Tr("Timeout without feed update in seconds (empty is never)"),                                           []);
    addOption(widgets["thermometer"], "errormessagedisplayed",       "value",        _Tr("Error Message"),         _Tr("Error message displayed when timeout is reached"),                                           []);


    return widgets;
}


// Draws the thermometer on the canvas. Returns the hotspots of the min and
// max bars, each {x, y, w, h, tip}, for the tooltips. Empty when the bars
// are not shown. config holds the widget settings. reading holds the scaled
// feed values as {value, min, max, displayminmax, errorCode, errorMessage}.
function draw_thermometer(canvas, config, reading)
{
    var context = canvas.getContext("2d");
    var width = canvas.width;
    var height = canvas.height;
    var hotspots = [];

    context.clearRect(0,0,width+10,height+10); // Clear old drawing

    var rawValue = reading.value;
    var minvaluefeed = reading.min;
    var maxvaluefeed = reading.max;
    var displayminmax = reading.displayminmax;
    var errorCode = reading.errorCode;
    var errorMessage = reading.errorMessage;

    var minValue = 1 * config.min || 0;
    var maxValue = 1 * config.max || 3000;
    var unitsString = config.units || "";
    var titleThermometer = config.titlethermometer || "";
    var unitend = config.unitend || "0";
    var decimals = config.decimals;
    var colourLabel = widget_colour(config.colourlabel, "000");
    var colourMinMax = widget_colour(config.colourminmax, "555");
    var graduationBool = config.graduations;
    var graduationQuant = config.gradnumber;
    var staticOffset = 1*config.offset || 0;
    var displayValue = rawValue;
    displayValue = displayValue-staticOffset;

    var scaledValue = ((displayValue-minValue)/(maxValue-minValue));    // Produce a scaled 0-1 value corresponding to min-max
    if (scaledValue < 0){scaledValue = 0;}

    var minDisplayValue = minvaluefeed;
    minDisplayValue = minDisplayValue-staticOffset;

    var minScaledValue = ((minDisplayValue-minValue)/(maxValue-minValue));    // Produce a scaled 0-1 value corresponding to min-max
    if (minScaledValue < 0){minScaledValue = 0;}

    var maxDisplayValue = maxvaluefeed;
    maxDisplayValue = maxDisplayValue-staticOffset;

    var maxScaledValue = ((maxDisplayValue-minValue)/(maxValue-minValue));    // Produce a scaled 0-1 value corresponding to min-max
    if (maxScaledValue < 0){maxScaledValue = 0;}

    rawValue = widget_decimals(rawValue, decimals);
    minvaluefeed = widget_decimals(minvaluefeed, decimals);
    maxvaluefeed = widget_decimals(maxvaluefeed, decimals);

    if (width>height){width = height;}
    var size = 0;
    if (width<height){size = width/2;}
    else {size = height/2;}
    size = size - (size*0.058/2);

    var halfWidth = width/2;

    var f = widget_font(config.font || "9", config.fstyle || "2", config.fweight || "0");
    var fontname = f.name;
    var fontstyle = f.style;
    var fontweight = f.weight;

    context.save();
    if (graduationBool === "1")
    {
        context.translate((halfWidth*(-0.25)),0); //translate the thermometer to the left in order to get more space for the graduations
        if (graduationQuant > 0)
        {
            context.textAlign    = "start";
            context.font = (fontstyle+ " "+ fontweight+ " "+(size*0.12)+"px "+ fontname);

            var step = (height*0.8)/(Number(graduationQuant)+1);
            var curY;

            var divisions = Number(graduationQuant)+1;

            for (var y = 0; y <= graduationQuant; y++)
            {
                curY = Number(((y+1)*step).toFixed(0))+0.5;  // Bin down so we're drawing in the middle of the pixel, so the line is exactly 1 px wide
                context.moveTo(halfWidth*0.3, curY);
                context.lineTo(halfWidth*0.7, curY);

                var unitOffset = Number(staticOffset+ maxValue - (y+1)*(maxValue-minValue)/divisions);
                if (unitOffset < 1000)
                {unitOffset = unitOffset.toFixed(1);}
                else
                {unitOffset = unitOffset.toFixed(0);}
                if (unitend ==="0"){context.fillText(parseFloat(unitOffset)+unitsString, halfWidth*0.75, curY);}
                if (unitend ==="1"){context.fillText(unitsString+parseFloat(unitOffset), halfWidth*0.75, curY);}
            }
            context.moveTo(halfWidth*0.3, height*0.8);
            context.lineTo(halfWidth*0.7, height*0.8);

            context.strokeStyle = "#888";
            context.stroke();
        }
    }

    if (!displayValue) {displayValue = 0;}  // Clamp value so we don't draw negative values.

    context.fillStyle = "#FB0000";

    var thermometerTop = (height*0.8 - (scaledValue * height*0.8));

    if (thermometerTop < height*0.05)     // Clamp value so we don't overshoot the top of the thermometergraph.
    {thermometerTop = height*0.05;}

    context.beginPath();
    context.lineWidth = width*0.01;
    context.strokeStyle = "#000";
    context.arc(halfWidth/2,height*0.05 + width*0.02,halfWidth*0.1,1*Math.PI,2*Math.PI);
    context.fillStyle = "#FFF";
    context.fill();
    context.fillRect(halfWidth*0.4,height*0.05,halfWidth*0.2,height*0.85);
    context.moveTo(halfWidth*0.4,height*0.05 + width*0.01);
    context.lineTo(halfWidth*0.4,height*0.9);
    context.moveTo(halfWidth*0.6,height*0.05 + width*0.01);
    context.lineTo(halfWidth*0.6,height*0.9);
    context.stroke();

    context.beginPath();
    context.arc(halfWidth/2,height*0.9 - width*0.01,halfWidth*0.2,1.66*Math.PI,1.35*Math.PI);
    context.fillStyle = "#FFF";
    context.fill();
    context.lineWidth = width*0.01;
    context.strokeStyle = "#000";
    context.stroke();

    context.beginPath();
    context.arc(halfWidth/2,height*0.9 - width*0.01,halfWidth*0.2-width*0.02,0,2*Math.PI);
    context.fillStyle = "#FB0000";
    context.fill();
    if(errorCode != "1"){                // Do not display the value if timeout is reached
        context.fillRect(halfWidth*0.4 + width*0.02,
            thermometerTop,
            halfWidth*0.2 - width*0.04,
            height*0.9 - thermometerTop);
    }
    var thermometerMin=0;
    var thermometerMax=0;

    if(displayminmax==="1"){

        context.fillStyle = colourMinMax;
        thermometerMin = (height*0.8 - (minScaledValue * height*0.8));

        if (thermometerMin < height*0.05)     // Clamp value so we don't overshoot the top of the thermometergraph.
        {thermometerMin = height*0.05;}

        context.fillRect(halfWidth*0.4 + width*0.005,
            thermometerMin,
            halfWidth*0.2 - width*0.01,
            2);

        context.fillStyle = colourMinMax;
        thermometerMax = (height*0.8 - (maxScaledValue * height*0.8));

        if (thermometerMax < height*0.05)     // Clamp value so we don't overshoot the top of the thermometergraph.
        {thermometerMax = height*0.05;}

        context.fillRect(halfWidth*0.4 + width*0.005,
            thermometerMax,
            halfWidth*0.2 - width*0.01,
            2);

        // Bars in canvas coordinates, allowing for the translate above.
        var offsetPosition = 0;
        if (graduationBool === "1"){offsetPosition = 0.25;}

        hotspots = [
            {x:(halfWidth*(0.4 - offsetPosition) + width*0.005),y: thermometerMin,w:(halfWidth*0.2 - width*0.01),h:2,tip: minvaluefeed},
            {x:(halfWidth*(0.4 - offsetPosition) + width*0.005),y: thermometerMax,w:(halfWidth*0.2 - width*0.01),h:2,tip: maxvaluefeed},
        ];
    }

    context.restore();

    context.fillStyle = colourLabel;

    var thermometertext;
    if (errorCode === "1")
    {
        thermometertext = errorMessage;
    }
    else
    {
        if (unitend ==="0"){thermometertext= rawValue+unitsString;}
        if (unitend ==="1"){thermometertext= unitsString+rawValue;}
    }
    var valsize;
    if (thermometertext.length >4){ valsize = (size / (thermometertext.length+2)) * 5.5;}
    else {valsize = (size / 6) * 5.5;}
    var titlesize ;
    if (titleThermometer.length >10) {titlesize = (size / (titleThermometer.length+2)) * 9;}
    else {titlesize = (size / 12) * 9.5;}

    context.textAlign    = "center";
    context.font = (fontstyle+ " "+ fontweight+ " "+(valsize*0.45)+"px "+ fontname);
    context.fillText(thermometertext, width*0.75, height*0.6);
    context.font = (fontstyle+ " "+ fontweight+ " "+(titlesize*0.25)+"px "+ fontname);
    context.fillText(titleThermometer, width*0.75, height*0.2);

    return hotspots;
}

var thermometer_widget = {
    mount: function(el, config, ctx){
        var canvas = ctx.canvas(el);
        var feeds = ctx.feeds;

        // Tooltips for the min and max bars.
        var tips = widget_tooltips(el, canvas.canvas, 2);

        // Each reading eases towards its value over several frames.
        var curve = { val: 0, minval: 0, maxval: 0 };
        var force = true;

        var draw = function(){
            var feed = feeds.get(config.feedid);
            var minfeed = feeds.get(config.minvaluefeed || "0");
            var maxfeed = feeds.get(config.maxvaluefeed || "0");

            if (!feed) return;

            var val = (feed.value * 1).toFixed(3);
            curve.val = render_curve(curve.val, feed.value);
            var val_curve = curve.val.toFixed(3);

            var timeout = widget_timeout(config, feed);
            var errorTimeout = parseFloat(config.timeout) || 0;

            // The minval and maxval feed settings default to the first feed in the feedlist
            // which may not be public for use in public dashboards, which will then result in
            // an error. Here we set the min/max values to 0 where the feed settings are not valid

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

            // Here we disable the min/max values feature when one of the feed settings is not valid
            var displayminmax = config.displayminmax || "0";
            if (!minfeed || !maxfeed) {
                displayminmax = "0";
            }

            // ONLY UPDATE ON CHANGE
            if (val_curve!=val || minval_curve!=minval || maxval_curve!=maxval || force || errorTimeout != 0)
            {
                var scale = 1*config.scale || 1;
                tips.set(draw_thermometer(canvas.canvas, config, {
                    value: val*scale,
                    min: minval*scale,
                    max: maxval*scale,
                    displayminmax: displayminmax,
                    errorCode: timeout.code,
                    errorMessage: timeout.message
                }));
                force = false;
            }
        };

        return {
            update: function(live){ feeds = live; },
            frame: function(){ draw(); },
            resize: function(){ canvas.fit(); force = true; draw(); },
            destroy: function(){ tips.destroy(); }
        };
    }
};
