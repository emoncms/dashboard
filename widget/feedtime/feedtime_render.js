/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    Author: Trystan Lea: trystan.lea@googlemail.com
    Enhancements done by: Andreas Messerli firefox7518@gmail.com
    If you have any questions please get in touch, try the forums here:
    http://openenergymonitor.org/emon/forum
 */

function feedtime_widgetlist()
{
    var widgets =
    {
        "feedtime":
        {
            "offsetx":-40,"offsety":-30,"width":120,"height":60,
            "menu":"Widgets",
            "options":    [],
            "optionstype":[],
            "optionsname":[],
            "optionshint":[],
            "optionsdata":[]
        }
    };

    addOption(widgets["feedtime"], "feedid",  "feedid",        _Tr("Feed"),          _Tr("Feed value"),                     []);
    addOption(widgets["feedtime"], "colour",  "colour_picker", _Tr("Colour"),        _Tr("Colour used for display"),        []);
    addOption(widgets["feedtime"], "font",    "dropbox",       _Tr("Font"),          _Tr("Font used for display"),          widget_font_options());
    addOption(widgets["feedtime"], "fstyle",  "dropbox",       _Tr("Font style"),    _Tr("Font style used for display"),    widget_style_options());
    addOption(widgets["feedtime"], "fweight", "dropbox",       _Tr("Font weight"),   _Tr("Font weight used for display"),   widget_weight_options());
    addOption(widgets["feedtime"], "units",   "dropbox_other", _Tr("Units"),         _Tr("Units to show"),                  _SI);
    addOption(widgets["feedtime"], "size",    "dropbox",       _Tr("Size"),          _Tr("Text size in px to use"),         widget_size_options());
    addOption(widgets["feedtime"], "align",    "dropbox", _Tr("Alignment"), _Tr("Alignment"), widget_align_options());
    addOption(widgets["feedtime"], "unitend", "dropbox",       _Tr("Unit position"), _Tr("Where should the unit be shown"), widget_unitend_options());

    return widgets;
}

// Seconds since the feed was written, as whole seconds with the units.
function draw_feedtime(el, font, fstyle, fweight, height, val, units, colour, size, align, unitend)
{
    unitend = unitend || "0";
    var f = widget_font(font || "5", fstyle || "2", fweight || "1", size || "8");

    val = val.toFixed(0);

    el.style.color = widget_colour(colour, "4444CC");
    el.style.font = f.css;
    el.style.textAlign = align || "center";
    el.style.lineHeight = height + "px";

    if (unitend === "0") el.textContent = val + units;
    if (unitend === "1") el.textContent = units + val;
}

var feedtime_widget = {
    mount: function(el, config, ctx) {
        el.innerHTML = "";
        var feeds = ctx.feeds;
        // Whole seconds last written, so the text changes once a second.
        var shown = null;

        var draw = function(force) {
            var feed = feeds.get(config.feedid);
            if (!feed) return;

            var val = ((new Date()).getTime() / 1000  - offsetofTime - (feed.time * 1));
            if (isNaN(val)) val = 0;

            var seconds = val.toFixed(0);
            if (!force && seconds === shown) return;
            shown = seconds;

            draw_feedtime(el,
                config.font,
                config.fstyle,
                config.fweight,
                el.clientHeight,
                val,
                config.units,
                config.colour,
                config.size,
                config.align,
                config.unitend
            );
        };

        return {
            update: function(live) { feeds = live; draw(true); },
            frame: function() { draw(false); },
            resize: function() { draw(true); },
            destroy: function() {}
        };
    }
};
