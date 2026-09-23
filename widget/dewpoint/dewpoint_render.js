/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
   Part of the OpenEnergyMonitor project: http://openenergymonitor.org

   Author: Nuno Chaveiro nchaveiro(a)gmail.com
   If you have any questions please get in touch, try the forums here:  http://openenergymonitor.org/emon/forum
 */

/**
 http://www.e-lab.de/downloads/DOCs/SHT11appnote2.pdf
 Compute dewPoint for given relative humidity RH[%] and temperature T[Deg.C].
 returns : Dew Point Temperature [0C]
*/
function dewPoint(RH,T) {
    var H = ((Math.log(RH)/Math.LN10)-2)/0.4343 + (17.62*T)/(243.12+T);
    var dp = 243.12*H/(17.62-H);     // this is the dew point in Celsius
    return dp;
}

function dewpoint_widgetlist()
{
    var widgets =
    {
        "dewpoint":
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

    addOption(widgets["dewpoint"], "feedhumid", "feedid",  _Tr("Humidity"),    _Tr("Relative humidity in %"),          []);
    addOption(widgets["dewpoint"], "feedtemp",  "feedid",  _Tr("Temperature"), _Tr("Temperature feed"),                []);
    addOption(widgets["dewpoint"], "temptype",  "dropbox", _Tr("Temp unit"),   _Tr("Units of the choosen temp feed"),  tempDropBoxOptions);
    addOption(widgets["dewpoint"], "decimals",  "dropbox", _Tr("Decimals"),    _Tr("Decimals to show"),                widget_decimals_options());
    addOption(widgets["dewpoint"], "colour",     "colour_picker",  _Tr("Colour"),     _Tr("Colour used for display"),      []);
    addOption(widgets["dewpoint"], "font",     "dropbox",  _Tr("Font"),     _Tr("Font used for display"),      widget_font_options());
    addOption(widgets["dewpoint"], "fstyle",   "dropbox", _Tr("Font style"), _Tr("Font style used for display"),    widget_style_options());
    addOption(widgets["dewpoint"], "fweight",   "dropbox", _Tr("Font weight"), _Tr("Font weight used for display"),    widget_weight_options());
    addOption(widgets["dewpoint"], "size",   "dropbox", _Tr("Size"), _Tr("Text size in px to use"),    widget_size_options());
    addOption(widgets["dewpoint"], "align",    "dropbox", _Tr("Alignment"), _Tr("Alignment"), widget_align_options());
    addOption(widgets["dewpoint"], "unitend",  "dropbox", _Tr("Unit position"), _Tr("Where should the unit be shown"), widget_unitend_options());
    return widgets;
}

// Writes a formatted value and its unit into the box, styled from the
// options. Shared with frostpoint, which draws the same way.
function draw_dewpoint(el, config, text, unit)
{
    var f = widget_font(config.font || "5", config.fstyle || "2", config.fweight || "1", config.size || "8");
    el.style.color = widget_colour(config.colour, "4444CC");
    el.style.font = f.css;
    el.style.textAlign = config.align || "center";
    el.style.lineHeight = el.clientHeight + "px";

    if (config.unitend === "0") el.textContent = text + unit;
    if (config.unitend === "1") el.textContent = unit + text;
}

// Temperature in Celsius and the unit of the temptype option, from the
// temperature feed. Null without the feed. Shared with frostpoint.
function dewpoint_reading(config, feeds)
{
    var feedtemp = feeds.get(config.feedtemp);
    if (!feedtemp) return null;
    var temp = feedtemp.value * 1;
    if (isNaN(temp)) temp = 0;

    var feedhumid = feeds.get(config.feedhumid);
    if (!feedhumid) return null;
    var humid = feedhumid.value * 1;
    if (isNaN(humid)) humid = 0;

    var temptype = config.temptype;
    if (temptype === undefined) temptype = "0";

    if (temptype === "1") temp = (temp - 32) * (5 / 9); // Fahrenheit to celsius
    return { temp: temp, humid: humid, fahrenheit: temptype === "1", unit: temptype === "1" ? "ºF" : "ºC" };
}

var dewpoint_widget = {
    mount: function(el, config, ctx) {
        el.innerHTML = "";
        var feeds = ctx.feeds;

        var draw = function() {
            var r = dewpoint_reading(config, feeds);
            if (!r) return;
            var val = dewPoint(r.humid, r.temp);
            if (r.fahrenheit) val = (val * 9/5 + 32); // Celsius to Fahrenheit

            draw_dewpoint(el, config, widget_decimals(val, config.decimals), r.unit);
        };

        return {
            update: function(live) { feeds = live; draw(); },
            resize: function() { draw(); },
            destroy: function() {}
        };
    }
};
