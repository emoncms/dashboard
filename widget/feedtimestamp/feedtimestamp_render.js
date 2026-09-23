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

function feedtimestamp_widgetlist()
{
    var widgets =
    {
        "feedtimestamp":
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

    var dateformatOptions = [
        [0, _Tr("Not displayed")],
        [1, "DD/MM/YYYY"],
        [2, "DD-MM-YYYY"],
        [3, "DD.MM.YYYY"],
        [4, "YYYY/MM/DD"],
        [5, "YYYY-MM-DD"],
        [6, "YYYY.MM.DD"],
        [7, "MM/DD/YYYY"],
        [8, "MM-DD-YYYY"],
        [9, "MM.DD.YYYY"]
    ];

    var timeformatOptions = [
        [0, _Tr("Not displayed")],
        [1, "HH:MM:SS"],
        [2, "HH:MM"],
        [3, "H:MM:SS"],
        [4, "H:MM"],
        [5, "H:MM:SS A"],
        [6, "H:MM A"]
    ];

    addOption(widgets["feedtimestamp"], "feedid",       "feedid",        _Tr("Feed"),          _Tr("Feed value"),                     []);
    addOption(widgets["feedtimestamp"], "colour",       "colour_picker", _Tr("Colour"),        _Tr("Colour used for display"),        []);
    addOption(widgets["feedtimestamp"], "font",         "dropbox",       _Tr("Font"),          _Tr("Font used for display"),          widget_font_options());
    addOption(widgets["feedtimestamp"], "fstyle",       "dropbox",       _Tr("Font style"),    _Tr("Font style used for display"),    widget_style_options());
    addOption(widgets["feedtimestamp"], "fweight",      "dropbox",       _Tr("Font weight"),   _Tr("Font weight used for display"),   widget_weight_options());
    addOption(widgets["feedtimestamp"], "size",         "dropbox",       _Tr("Size"),          _Tr("Text size in px to use"),         widget_size_options());
    addOption(widgets["feedtimestamp"], "align",        "dropbox",       _Tr("Alignment"),     _Tr("Alignment"),                      widget_align_options());
    addOption(widgets["feedtimestamp"], "dateformat",   "dropbox",       _Tr("Date format"),   _Tr("Date format"),                    dateformatOptions);
    addOption(widgets["feedtimestamp"], "timeformat",   "dropbox",       _Tr("Time format"),   _Tr("Time format"),                    timeformatOptions);
    return widgets;
}

function draw_feedtimestamp(el, font, fstyle, fweight, height, val, colour, size, align)
{
    var f = widget_font(font || "5", fstyle || "2", fweight || "1", size || "8");

    el.style.color = widget_colour(colour, "4444CC");
    el.style.font = f.css;
    el.style.textAlign = align || "center";
    el.style.lineHeight = height + "px";

    el.textContent = val;
}

var feedtimestamp_widget = {
    mount: function(el, config, ctx) {
        el.innerHTML = "";
        var feeds = ctx.feeds;

        var draw = function() {
            var feed = feeds.get(config.feedid);
            if (!feed) return;

            var timestamp = new Date(feed.time * 1000);
            var day = "0" + timestamp.getDate();
            var month ="0" + (timestamp.getMonth() + 1);
            var year = timestamp.getFullYear();
            var hours = timestamp.getHours();
            var minutes = "0" + timestamp.getMinutes();
            var seconds = "0" + timestamp.getSeconds();

            var val = "";
            var hours2;
            var suffix;
            var dateformat = config.dateformat;
            var timeformat = config.timeformat;

            if(hours==0){hours2=12; suffix="AM";}
            if(hours<12 && hours!=0){hours2=hours; suffix="AM";}
            if(hours==12){hours2=hours; suffix="PM";}
            if(hours>12){hours2=hours-12; suffix="PM";}

            if (dateformat === "1"){val=day.substr(-2) + "/" +month.substr(-2)+ "/" +year + " ";}
            if (dateformat === "2"){val=day.substr(-2) + "-" +month.substr(-2)+ "-" +year + " ";}
            if (dateformat === "3"){val=day.substr(-2) + "." +month.substr(-2)+ "." +year + " ";}
            if (dateformat === "4"){val=year + "/" +month.substr(-2) + "/" +day.substr(-2)+ " ";}
            if (dateformat === "5"){val=year + "-" +month.substr(-2) + "-" +day.substr(-2)+ " ";}
            if (dateformat === "6"){val=year + " " +month.substr(-2) + "." +day.substr(-2)+ " ";}
            if (dateformat === "7"){val=month.substr(-2) + "/" +day.substr(-2)+ "/" +year + " ";}
            if (dateformat === "8"){val=month.substr(-2) + "-" +day.substr(-2)+ "-" +year + " ";}
            if (dateformat === "9"){val=month.substr(-2) + "." +day.substr(-2)+ "." +year + " ";}

            if (timeformat === "1"){val=val + ("0"+hours).substr(-2) + ":" +minutes.substr(-2)+ ":" +seconds.substr(-2);}
            if (timeformat === "2"){val=val + ("0"+hours).substr(-2) + ":" +minutes.substr(-2);}
            if (timeformat === "3"){val=val + hours + ":" +minutes.substr(-2)+ ":" +seconds.substr(-2);}
            if (timeformat === "4"){val=val + hours + ":" +minutes.substr(-2);}
            if (timeformat === "5"){val=val + hours2 + ":" +minutes.substr(-2)+ ":" +seconds.substr(-2)+ " "+suffix;}
            if (timeformat === "6"){val=val + hours2 + ":" +minutes.substr(-2) + " "+suffix;}

            draw_feedtimestamp(el,
                config.font,
                config.fstyle,
                config.fweight,
                el.clientHeight,
                val,
                config.colour,
                config.size,
                config.align
            );
        };

        return {
            update: function(live) { feeds = live; draw(); },
            resize: function() { draw(); },
            destroy: function() {}
        };
    }
};
