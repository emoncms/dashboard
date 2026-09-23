/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
 */
/**
 http://www.meteolafleche.com/temperature.html
 Compute humidex for given relative humidity RH[%] and temperature T[Deg.C].
 returns : Humidex
*/
function humidex(RH,T) {
    var t=7.5*T/(237.7+T);
    var et=Math.pow(10,t);
    var e=6.112*et*(RH/100);
    var hum=T+(5/9)*(e-10);
    if (hum < T)
    {
        hum=T;
    }
    return hum;
}

function humidex_widgetlist()
{
    var widgets =
    {
        "humidex":
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

    addOption(widgets["humidex"], "feedhumid", "feedid",  _Tr("Humidity"),    _Tr("Relative humidity in %"),          []);
    addOption(widgets["humidex"], "feedtemp",  "feedid",  _Tr("Temperature"), _Tr("Temperature feed"),                []);
    addOption(widgets["humidex"], "temptype",  "dropbox", _Tr("Temp unit"),   _Tr("Units of the choosen temp feed"),  tempDropBoxOptions);
    addOption(widgets["humidex"], "decimals",   "dropbox", _Tr("Decimals"), _Tr("Decimals to show"),    widget_decimals_options());
    addOption(widgets["humidex"], "colour",     "colour_picker",  _Tr("Colour"),     _Tr("Colour used for display"),      []);
    addOption(widgets["humidex"], "font",     "dropbox",  _Tr("Font"),     _Tr("Font used for display"),      widget_font_options());
    addOption(widgets["humidex"], "fstyle",   "dropbox", _Tr("Font style"), _Tr("Font style used for display"),    widget_style_options());
    addOption(widgets["humidex"], "fweight",   "dropbox", _Tr("Font weight"), _Tr("Font weight used for display"),    widget_weight_options());
    addOption(widgets["humidex"], "size",       "dropbox", _Tr("Size"), _Tr("Text size in px to use"),    widget_size_options());
    addOption(widgets["humidex"], "align",    "dropbox", _Tr("Alignment"), _Tr("Alignment"), widget_align_options());
    return widgets;
}

// Drawn by draw_index_text in heatindex_render.js, which loads first.
function draw_humidex(el, config, val)
{
    draw_index_text(el, config, widget_decimals(val, config.decimals));
}

var humidex_widget = {
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

            if (temptype ==="1") {
                temp = (temp - 32) * (5 / 9); // Fahrenheit to celsius
            }
            var val = humidex(humid,temp);

            draw_humidex(el, config, val);
        };

        return {
            update: function(live) { feeds = live; draw(); },
            resize: function() { draw(); },
            destroy: function() {}
        };
    }
};
