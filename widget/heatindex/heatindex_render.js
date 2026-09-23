/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
   Part of the OpenEnergyMonitor project: http://openenergymonitor.org
   Authors : Paul Reed & Aymeric Thibaut
 */


/**
 From http://www.wpc.ncep.noaa.gov/html/heatindex_equation.shtml
 Compute heat index for given relative humidity RH[%] and temperature T[Deg.F].
 returns : heat index [Deg.F] or temperature T[Deg.F] or nothing depending of the rule applied for T <50°F and the rule applied for 50°F < T < aproximately 80°F
*/
function heatindex(RH,T,rule1,rule2) {
    var hitemp = 61.0+((T-68.0)*1.2)+(RH*0.094);
    var fptemp = parseFloat(T);
    var hifinal = 0.5*(fptemp+hitemp);
    var hi;
    if(hifinal >= 80.0){
        hi = -42.379 + 2.04901523 * T+ 10.14333127 * RH - 0.22475541 * T * RH - 6.83783 * (Math.pow(10, -3)) * (Math.pow(T, 2)) - 5.481717 * (Math.pow(10, -2)) * (Math.pow(RH, 2)) + 1.22874 * (Math.pow(10, -3)) * (Math.pow(T, 2)) * RH+ 8.5282 * (Math.pow(10, -4)) * T * (Math.pow(RH, 2)) - 1.99 * (Math.pow(10, -6)) * (Math.pow(T, 2)) * (Math.pow(RH,2));
        var adjust = 0;
        if (RH < 13 && T >= 80 && T< 112) {adjust = (-1)*((13-RH)/4)*Math.sqrt([17-Math.abs(T-95.0)]/17);}
        else if (RH > 85 && T >= 80 && T< 87) {adjust = ((RH-85)/10) * ((87-T)/5);}
        hi = hi + adjust;
    }
    if(T >=50.00 && hifinal <80.0){
        if(rule1==="0"){hi = hifinal;}
        if(rule1==="1"){hi = T;}
    }
    if(T <50.0){
        if(rule2==="0"){hi = T;}
        if(rule2==="1"){hi = -500;} //below -459.67 °F which represents absolute zero (0K)
    }
    return hi;
}

function heatindex_widgetlist()
{
    var widgets =
    {
        "heatindex":
        {
            "offsetx":-40,"offsety":-10,"width":80,"height":20,
            "menu":"Widgets",
            "options":    [],
            "optionstype":[],
            "optionsname":[],
            "optionshint":[],
            "optionsdata":[]
        }
    };

    var tempDropBoxOptions = [        // Options for the type combobox. Each item is [typeID, "description"]
        [0,    "ºC"],
        [1,    "ºF"]
    ];

    var rule1options = [
        [0, _Tr("Heat Index")],
        [1, _Tr("Temperature Feed")]
    ];

    var rule2options = [
        [0, _Tr("Temperature Feed")],
        [1, _Tr("Display Nothing")]
    ];


    addOption(widgets["heatindex"], "feedhumid", "feedid",        _Tr("Humidity"),      _Tr("Relative humidity in %"),                      []);
    addOption(widgets["heatindex"], "feedtemp",  "feedid",        _Tr("Temperature"),   _Tr("Temperature feed"),                            []);
    addOption(widgets["heatindex"], "temptype",  "dropbox",       _Tr("Temp unit"),     _Tr("Units of the choosen temp feed"),              tempDropBoxOptions);
    addOption(widgets["heatindex"], "decimals",  "dropbox",       _Tr("Decimals"),      _Tr("Decimals to show"),                            widget_decimals_options());
    addOption(widgets["heatindex"], "rule1",     "dropbox",       _Tr("Rule 1"),        _Tr("Formula applied for T between 50°F and aproximately 80°F"), rule1options);
    addOption(widgets["heatindex"], "rule2",     "dropbox",       _Tr("Rule 2"),        _Tr("Formula applied for T lower than 50°F"),       rule2options);
    addOption(widgets["heatindex"], "colour",    "colour_picker", _Tr("Colour"),        _Tr("Colour used for display"),                     []);
    addOption(widgets["heatindex"], "font",      "dropbox",       _Tr("Font"),          _Tr("Font used for display"),                       widget_font_options());
    addOption(widgets["heatindex"], "fstyle",    "dropbox",       _Tr("Font style"),    _Tr("Font style used for display"),                 widget_style_options());
    addOption(widgets["heatindex"], "fweight",   "dropbox",       _Tr("Font weight"),   _Tr("Font weight used for display"),                widget_weight_options());
    addOption(widgets["heatindex"], "size",      "dropbox",       _Tr("Size"),          _Tr("Text size in px to use"),                      widget_size_options());
    addOption(widgets["heatindex"], "align",     "dropbox",       _Tr("Alignment"),     _Tr("Alignment"),                                   widget_align_options());
    addOption(widgets["heatindex"], "unitend",   "dropbox",       _Tr("Unit position"), _Tr("Where should the unit be shown"),              widget_unitend_options());
    return widgets;
}

// Writes text into the box in the colour, font and alignment options.
// Shared with humidex, which loads after this file. Text, not html: the
// unit is free text an author types.
function draw_index_text(el, config, text)
{
    var font = widget_font(config.font || "5", config.fstyle || "2", config.fweight || "1", config.size || "8");
    el.style.color = widget_colour(config.colour, "4444CC");
    el.style.font = font.css;
    el.style.textAlign = config.align || "center";
    el.style.lineHeight = el.clientHeight + "px";
    el.textContent = text;
}

function draw_heatindex(el, config, val, unit)
{
    var text;
    if (val === -500) {
        text = " -- ";
    } else {
        var unitend = config.unitend || "0";
        val = widget_decimals(val, config.decimals);
        text = unitend === "1" ? unit + val : val + unit;
    }
    draw_index_text(el, config, text);
}

var heatindex_widget = {
    mount: function(el, config, ctx) {
        el.innerHTML = "";
        var feeds = ctx.feeds;

        var draw = function() {
            var feedtemp = feeds.get(config.feedtemp);
            if (!feedtemp) return;
            var temp = feedtemp.value * 1;
            if (isNaN(temp))  {temp = 0;}

            var temptype = config.temptype;
            if (temptype===undefined) {temptype = "0";}

            var feedhumid = feeds.get(config.feedhumid);
            if (!feedhumid) return;
            var humid = feedhumid.value * 1;
            if (isNaN(humid))  {humid = 0;}

            var rule1 = config.rule1;
            if (rule1===undefined) {rule1 = "0";}
            var rule2 = config.rule2;
            if (rule2===undefined) {rule2 = "0";}

            if (temptype ==="0") {
                temp = (temp * 9/5 + 32) ; // Celsius to Fahrenheit
            }
            var val = heatindex(humid,temp,rule1,rule2);
            if (temptype === "0" && val !==-500) {
                val = (val - 32) * (5 / 9); // Fahrenheit to Celsius
            }

            var unit;
            if (temptype === "0") {
                unit = "ºC";
            } else {
                unit = "ºF";
            }

            draw_heatindex(el, config, val, unit);
        };

        return {
            update: function(live) { feeds = live; draw(); },
            resize: function() { draw(); },
            destroy: function() {}
        };
    }
};
