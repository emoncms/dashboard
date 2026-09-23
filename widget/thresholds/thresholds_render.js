/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
    Widget initially created by Aymeric Thibaut
 */
var shapeOptions = [
    [2, _Tr("Circle")],
    [1, _Tr("Triangle &#9650;")],
    [5, _Tr("Triangle &#9654;")],
    [6, _Tr("Triangle &#9660;")],
    [7, _Tr("Triangle &#x25C0;")],
    [0, _Tr("Square")],
    [3, _Tr("Star 5 spikes")],
    [4, _Tr("Star 6 spikes")]
];

function thresholds_widgetlist()
{
    var widgets = {
        "thresholds":
        {
            "offsetx":0,"offsety":0,"width":80,"height":160,
            "menu":"Widgets",
            "options":    [],
            "optionstype":[],
            "optionsname":[],
            "optionshint":[],
            "optionsdata":[]
        }
    };
    addOption(widgets["thresholds"], "feedid",      "feedid",         _Tr("Feed"),       _Tr("Feed value"),                                         []);
    addOption(widgets["thresholds"], "threshold1",  "value",          _Tr("Threshold1"), _Tr("Threshold1 value"),                                   []);
    addOption(widgets["thresholds"], "threshold2",  "value",          _Tr("Threshold2"), _Tr("Threshold2 value"),                                   []);
    addOption(widgets["thresholds"], "colour1",     "colour_picker",  _Tr("Colour1"),    _Tr("Colour for range below Threshold1"),                  []);
    addOption(widgets["thresholds"], "colour2",     "colour_picker",  _Tr("Colour2"),    _Tr("Colour for range between Threshold1 and Threshold2"), []);
    addOption(widgets["thresholds"], "colour3",     "colour_picker",  _Tr("Colour3"),    _Tr("Colour for range above Threshold2"),                  []);
    addOption(widgets["thresholds"], "shapetype",   "dropbox",        _Tr("Shape"),      _Tr("Shape"),                                              shapeOptions);
    return widgets;
}

// Drawn by draw_status_shape in isactivefeed_render.js, which loads first.
var thresholds_widget = {
    mount: function(el, config, ctx) {
        var canvas = ctx.canvas(el);
        var feeds = ctx.feeds;

        var draw = function() {
            var limit1 = config.threshold1*1 || 60;
            var limit2 = config.threshold2*1 || 120;
            var colour3 = widget_colour(config.colour3, "#F70511");
            var colour2 = widget_colour(config.colour2, "#FF8425");
            var colour1 = widget_colour(config.colour1, "#019F62");
            var shapetype = config.shapetype|| "2";
            var val;
            var feedvalue = 0;

            var feed = feeds.get(config.feedid);
            if (!feed) return;

            if (feed["value"]!=undefined) {
                feedvalue = feed["value"] * 1;
                if (isNaN(feedvalue))  {feedvalue = 0;}
            }

            if (feedvalue > limit2){
                val=0;}
            else if (feedvalue > limit1){
                val=1;}
            else {
                val=2;}

            draw_status_shape(canvas.context, val, colour3, colour2, colour1, shapetype);
        };

        return {
            update: function(live) { feeds = live; draw(); },
            resize: function() { canvas.fit(); draw(); },
            destroy: function() {}
        };
    }
};
