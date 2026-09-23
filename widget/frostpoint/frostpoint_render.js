/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
 */
/**
 http://www.e-lab.de/downloads/DOCs/SHT11appnote2.pdf
 Compute frostPoint for given relative humidity RH[%] and temperature T[Deg.C].
 returns : Frost Point Temperature [0C]
*/
function frostPoint(RH,T) {
    var dp = dewPoint(RH,T);     // dew point in Celsius, see dewpoint_render.js
    var fp= (dp+273.15) + 2671.02 /(2954.61/(T+273.15)+2.193665*Math.log(T+273.15) -13.3448) -(T+273.15)- 273.15;
    return fp;
}

function frostpoint_widgetlist()
{
    var widgets =
    {
        "frostpoint":
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

    addOption(widgets["frostpoint"], "feedhumid", "feedid",  _Tr("Humidity"),    _Tr("Relative humidity in %"),          []);
    addOption(widgets["frostpoint"], "feedtemp",  "feedid",  _Tr("Temperature"), _Tr("Temperature feed"),                []);
    addOption(widgets["frostpoint"], "temptype",  "dropbox", _Tr("Temp unit"),   _Tr("Units of the choosen temp feed"),  tempDropBoxOptions);
    addOption(widgets["frostpoint"], "decimals",   "dropbox", _Tr("Decimals"), _Tr("Decimals to show"),    widget_decimals_options());
    addOption(widgets["frostpoint"], "colour",     "colour_picker",  _Tr("Colour"),     _Tr("Colour used for display"),      []);
    addOption(widgets["frostpoint"], "font",     "dropbox",  _Tr("Font"),     _Tr("Font used for display"),      widget_font_options());
    addOption(widgets["frostpoint"], "fstyle",   "dropbox", _Tr("Font style"), _Tr("Font style used for display"),    widget_style_options());
    addOption(widgets["frostpoint"], "fweight",   "dropbox", _Tr("Font weight"), _Tr("Font weight used for display"),    widget_weight_options());
    addOption(widgets["frostpoint"], "size",    "dropbox", _Tr("Size"), _Tr("Text size in px to use"),    widget_size_options());
    addOption(widgets["frostpoint"], "align",    "dropbox", _Tr("Alignment"), _Tr("Alignment"), widget_align_options());
    addOption(widgets["frostpoint"], "unitend",  "dropbox", _Tr("Unit position"), _Tr("Where should the unit be shown"), widget_unitend_options());
    return widgets;
}

// Drawn by draw_dewpoint in dewpoint_render.js, with "-- " in place of the
// value above freezing. dewpoint_reading is shared from there too.
var frostpoint_widget = {
    mount: function(el, config, ctx) {
        el.innerHTML = "";
        var feeds = ctx.feeds;

        var draw = function() {
            var r = dewpoint_reading(config, feeds);
            if (!r) return;
            var val = frostPoint(r.humid, r.temp);
            if (r.fahrenheit) val = (val * 9/5 + 32); // Celsius to Fahrenheit

            var text = r.temp > 0 ? "-- " : widget_decimals(val, config.decimals);
            draw_dewpoint(el, config, text, r.unit);
        };

        return {
            update: function(live) { feeds = live; draw(); },
            resize: function() { draw(); },
            destroy: function() {}
        };
    }
};
