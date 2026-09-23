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

function feedvalue_widgetlist()
{
    var widgets =
    {
        "feedvalue":
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

    addOption(widgets["feedvalue"], "feedid",   "feedid",  _Tr("Feed"),     _Tr("Feed value"),      []);
    addOption(widgets["feedvalue"], "prepend",    "value",   _Tr("Prepend Text"),    _Tr("Prepend Text"),   []);
    addOption(widgets["feedvalue"], "append",  "value", _Tr("Append Text"), _Tr("Append Text (Units)"), []);
    addOption(widgets["feedvalue"], "decimals", "dropbox", _Tr("Decimals"), _Tr("Decimals to show"),    widget_decimals_options());
    addOption(widgets["feedvalue"], "colour",   "colour_picker",  _Tr("Colour"),     _Tr("Colour used for display"),      []);
    addOption(widgets["feedvalue"], "font",     "dropbox",  _Tr("Font"),     _Tr("Font used for display"),      widget_font_options());
    addOption(widgets["feedvalue"], "fstyle",   "dropbox", _Tr("Font style"), _Tr("Font style used for display"),    widget_style_options());
    addOption(widgets["feedvalue"], "fweight",  "dropbox", _Tr("Font weight"), _Tr("Font weight used for display"),    widget_weight_options());
    addOption(widgets["feedvalue"], "size",     "dropbox", _Tr("Size"), _Tr("Text size in px to use"),    widget_size_options());
    addOption(widgets["feedvalue"], "align",    "dropbox", _Tr("Alignment"), _Tr("Alignment"), widget_align_options());
    addOption(widgets["feedvalue"], "timeout",  "value",   _Tr("Timeout"),    _Tr("Timeout without feed update in seconds (empty is never)"),   []);
    addOption(widgets["feedvalue"], "errormessagedisplayed",    "value",  _Tr("Error Message"),   _Tr("Error message displayed when timeout is reached"),   []);
    addOption(widgets["feedvalue"], "threshold1",  "value",   _Tr("Threshold1"),    _Tr("Threshold1 value"),   []);
    addOption(widgets["feedvalue"], "threshold2",  "value",   _Tr("Threshold2"),    _Tr("Threshold2 value"),   []);
    addOption(widgets["feedvalue"], "colour1",   "colour_picker",  _Tr("Colour1"),     _Tr("Colour for range below Threshold1"),      []);
    addOption(widgets["feedvalue"], "colour2",   "colour_picker",  _Tr("Colour2"),     _Tr("Colour for range between Threshold1 and Threshold2"),      []);
    addOption(widgets["feedvalue"], "colour3",   "colour_picker",  _Tr("Colour3"),     _Tr("Colour for range above Threshold2"),      []);
    addOption(widgets["feedvalue"], "scale", "value", _Tr("Scale"), _Tr("scale"),    []);
    return widgets;
}

function draw_feedvalue(el,font,fstyle,fweight,height,prepend,val,append,colour,decimals,size,align,errorCode,errorMessage,scale)
{
    scale = scale || 1.0;
    val *= (1*scale);

    var f = widget_font(font || "5", fstyle || "2", fweight || "1", size || "8");
    val = widget_decimals(val, decimals);

    el.style.color = widget_colour(colour, "4444CC");
    el.style.font = f.css;
    el.style.textAlign = align || "center";
    el.style.lineHeight = height + "px";

    if (errorCode === "1")
    {
        el.textContent = errorMessage;
    }
    else
    {
        // Text, not html. prepend, append and units are free text an author
        // types, see the option values section of tools/SCHEMA.md.
        el.textContent = prepend+val+append;
    }
}

// Text, prepend and append from the options, with the units and unitend of
// dashboards saved before prepend and append existed.
function feedvalue_affixes(config)
{
    var prepend = config.prepend;
    var append = config.append;
    if (prepend == undefined && append == undefined) {
        if (config.unitend != undefined && config.units != undefined) {
            if (config.unitend === "0") {
                append = config.units;
                prepend = "";
            } else if (config.unitend === "1") {
                prepend = config.units;
                append = "";
            }
        } else {
            prepend = "";
            append = "";
        }
    }
    // One set without the other printed the word undefined beside the reading.
    if (prepend == undefined) prepend = "";
    if (append == undefined) append = "";
    return { prepend: prepend, append: append };
}

var feedvalue_widget = {
    mount: function(el, config, ctx) {
        el.innerHTML = "";
        var affixes = feedvalue_affixes(config);
        var feeds = ctx.feeds;

        var draw = function() {
            var val = 0;
            var feed = feeds.get(config.feedid);
            if (feed) val = feed.value * 1;
            if (isNaN(val)) val = 0;

            var timeout = widget_timeout(config, feed);

            var textcolor = config.colour;
            if (config.threshold1 != undefined && config.threshold1 != "" && config.threshold2 != undefined && config.threshold2 != "") {
                var limit1 = config.threshold1*1 || 60;
                var limit2 = config.threshold2*1 || 120;
                if (val < limit1) {
                    textcolor = config.colour1 || "#019F62";
                } else if (val > limit2) {
                    textcolor = config.colour3 || "#F70511";
                } else {
                    textcolor = config.colour2 || "#FF8425";
                }
            }

            draw_feedvalue(
                el,
                config.font,
                config.fstyle,
                config.fweight,
                el.clientHeight,
                affixes.prepend,
                val,
                affixes.append,
                textcolor,
                config.decimals,
                config.size,
                config.align,
                timeout.code,
                timeout.message,
                config.scale
            );
        };

        return {
            update: function(live) { feeds = live; draw(); },
            resize: function() { draw(); },
            destroy: function() {}
        };
    }
};
