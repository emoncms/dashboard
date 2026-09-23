/*
   All emon_widgets code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.

   Part of the OpenEnergyMonitor project:
   http://openenergymonitor.org

   Author: Trystan Lea: trystan.lea@googlemail.com
   If you have any questions please get in touch, try the forums here:
   http://openenergymonitor.org/emon/forum
 */

/*
   The realtime widget draws in the dashboard page. It used to load
   /vis/realtime in an iframe, which meant a second document and a second copy
   of flot per widget, and a request of its own for each one on a timer.

   It reads the feed once for the window it opens on, then follows the feed
   list the dashboard fetches every five seconds, appending each new reading
   on each update. The window scrolls on each frame, drawing from the data
   that is here rather than asking for more.

   realtime_widget.php loads flot 5.1, which is where the Flot global comes
   from, and the shared chart helpers. Times are in seconds, which is what
   that build plots on a time axis.
 */

// The lengths the buttons offer, in seconds.
var realtime_windows = [
    [3600, "1 hour"], [1800, "30 min"], [900, "15 min"], [300, "5 min"], [60, "1 min"]
];

// How often the window scrolls, in frames per second. The dashboard calls
// every widget at its own rate, which is faster than this chart needs.
var REALTIME_FPS = 10;

// The line colour a widget with no colour draws in, as the visualisation did.
var REALTIME_LINE = "#EDC240";

// The background a widget with no colour draws on, as the visualisation did.
var REALTIME_BACKGROUND = "#ffffff";

function realtime_widgetlist(){
    var zoomoptions = [
        ["1", "1 " + _Tr("minute")],
        ["5", "5 " + _Tr("minutes")],
        ["15", "15 " + _Tr("minutes")],
        ["30", "30 " + _Tr("minutes")],
        ["60", "1 " + _Tr("hour")]
    ];

    var widgets = {
        "realtime":
        {
            "offsetx":0,"offsety":0,"width":400,"height":300,
            "menu":"Visualisations",
            "title":_Tr("Realtime"),
            "description":_Tr("Draws one feed as a line over the last few minutes and appends each new reading as it arrives. Zoom sets the window."),
            "options":["feedid","colour","colourbg","colouraxis","initzoom","kw"],
            "optionstype":["feedid","colour_picker","colour_picker","colour_picker","dropbox","boolean"],
            "optionsname":[_Tr("Feed"),_Tr("Colour"),_Tr("Background"),_Tr("Axis colour"),_Tr("Zoom"),"kW"],
            "optionshint":[_Tr("Feed source"),_Tr("Line colour in hex. Blank is use default."),_Tr("Background colour in hex. Blank is white."),_Tr("Axis, label and legend colour in hex. Blank is use default."),_Tr("Default visible window interval"),_Tr("Display power as kW")],
            "optionsdata":[ , ,"ffffff", ,zoomoptions, ],
            "html":""
        }
    };

    return widgets;
}

var realtime_widget = {
    mount: function(el, config, ctx){
        var feedid = ctx.feeds.id(config.feedid);
        var colour = chart_hex(config.colour) || REALTIME_LINE;
        var kw = config.kw == "1";

        var zoom = parseFloat(config.initzoom);
        if (!isFinite(zoom) || zoom < 1) zoom = 15; // minutes, as the visualisation opened on

        chart_background(el, config.colourbg, REALTIME_BACKGROUND);
        chart_axis(el, config.colouraxis);

        var chart = realtime_build(el, ctx, feedid, colour, kw, zoom * 60);

        return {
            update: function(feeds){
                // A tag:name pair resolves once the poll has answered.
                if (chart.feedid === "" && config.feedid) {
                    chart.feedid = feeds.id(config.feedid);
                    if (chart.feedid !== "") realtime_start(chart);
                }
                realtime_append(chart, feeds);
            },
            frame: function(now){ realtime_tick(chart, now); },
            resize: function(){ realtime_plot(chart); },
            destroy: function(){ chart_destroy(chart); }
        };
    }
};

// One widget: the chart, the buttons over it, and the first read of the feed.
function realtime_build(element, ctx, feedid, colour, kw, window_seconds){
    var chart = {
        name: _Tr("Realtime"),
        ctx: ctx,
        element: element,
        feedid: feedid,
        colour: colour,
        kw: kw,
        window: window_seconds,
        data: [],
        plot: null,
        plotel: null,
        frame: null,
        request: null,
        read: 0,
        drawn: 0,
        width: 0,
        height: 0
    };

    if (feedid === "") {
        chart_message(element, _Tr("No feed selected"));
        return chart;
    }

    realtime_start(chart);
    return chart;
}

// Frame, window buttons and first read, once the feed is known.
function realtime_start(chart){
    var frame = chart_frame(chart.element);
    chart.plotel = frame.plot;
    chart.frame = frame.frame;

    var group = chart_group_of(frame.bar);
    realtime_windows.forEach(function(window){
            chart_button(group, _Tr(window[1]), null, function(){
                    chart.window = window[0];
                    realtime_fetch(chart);
                });
        });

    realtime_fetch(chart);
}

/* ── Data ────────────────────────────────────────────────────────────────── */

// The window the chart opens on, read once. Everything after it arrives with
// the feed list the dashboard fetches for every widget.
function realtime_fetch(chart){
    var now = Math.round(Date.now() / 1000);
    var interval = Math.max(1, Math.round(chart.window / chart_points(chart.element)));

    chart_fetch(chart, {
            id: chart.feedid, start: now - chart.window, end: now, interval: interval,
            skipmissing: 1, limitinterval: 0
        }, function(points){
            chart.data = realtime_read(chart, points);
            realtime_plot(chart);
        });
}

// Readings as flot takes them. A gap is a null reading and is left out,
// so the line closes over it as the visualisation drew it.
function realtime_read(chart, points){
    var data = [];
    if (!Array.isArray(points)) return data;
    for (var n = 0; n < points.length; n++){
        var point = points[n];
        if (!point || point[1] === null || !isFinite(point[1])) continue;
        data.push([point[0], realtime_value(chart, point[1])]);
    }
    return data;
}

// One reading, in the units the widget shows. Applied as the point is taken
// rather than in the draw, so a point is never scaled twice.
function realtime_value(chart, value){
    return chart.kw ? value / 1000 : value;
}

/* ── Drawing ───────────────────────────────────────────────── */

// The chart, built. Called when the widget is drawn and again when the box
// changes size, which is what a plot has to be built again for.
function realtime_plot(chart){
    var options = chart_plot_options(chart);
    if (!options) return;

    var window_now = realtime_window(chart);
    $.extend(true, options, {
            xaxis: {
                mode: "time",
                timezone: "browser",
                min: window_now.start,
                max: window_now.end,
                // Flot 5 otherwise snaps the axis to where the data is, which on a
                // window that has just been opened is a fraction of it.
                autoScale: "none"
            }
        });
    chart.plot = Flot.plot(chart.plotel, realtime_series(chart), options);

    chart.width = chart.plotel.clientWidth;
    chart.height = chart.plotel.clientHeight;
    chart_place(chart.plot, chart.frame);
}

// The window moved up to now, drawn over the data that is already here. The
// plot is kept and given the new axis, which is what the library does after a
// pan, rather than built again ten times a second.
function realtime_scroll(chart){
    if (!chart.plotel) return;
    if (!chart.plot || typeof chart.plot.setData !== "function") { realtime_plot(chart); return; }

    // A box that has changed size needs a plot of the new size.
    if (chart.plotel.clientWidth !== chart.width || chart.plotel.clientHeight !== chart.height) {
        realtime_plot(chart);
        return;
    }

    var window_now = realtime_window(chart);
    var axis = chart.plot.getAxes().xaxis;
    axis.options.min = window_now.start;
    axis.options.max = window_now.end;

    chart.plot.setData(realtime_series(chart));
    chart.plot.setupGrid(true);
    chart.plot.draw();
    chart_place(chart.plot, chart.frame);
}

// The window as it stands, in seconds, ending now.
function realtime_window(chart){
    var end = Date.now() / 1000;
    return { start: end - chart.window, end: end };
}

function realtime_series(chart){
    return [{
            data: chart.data,
            color: chart.colour,
            lines: { show: true, fill: 0.5, lineWidth: 2 }
        }];
}

/* ── The dashboard update cycle ──────────────────────────────────────────── */

// Called after each feed list fetch, which is every five seconds. The reading
// it carries is the one the visualisation asked for on a timer of its own.
function realtime_append(chart, feeds){
    var feed = feeds.get(chart.feedid);
    if (!feed) return;

    var time = parseInt(feed.time);
    var value = parseFloat(feed.value);
    if (!isFinite(time) || !isFinite(value)) return;

    var last = chart.data.length ? chart.data[chart.data.length - 1] : null;
    if (last && last[0] >= time) return;
    chart.data.push([time, realtime_value(chart, value)]);

    // Points that have scrolled off the left of the window, keeping the one
    // that is still holding the line up at the edge.
    var oldest = (Date.now() / 1000) - chart.window;
    while (chart.data.length > 2 && chart.data[1][0] < oldest) chart.data.shift();
}

// The window scrolls here. Nothing is fetched, the chart is drawn again over
// the data that is already here.
function realtime_tick(chart, now){
    if (!chart.plotel) return;
    if (now - chart.drawn < 1000 / REALTIME_FPS) return;
    chart.drawn = now;
    realtime_scroll(chart);
}

