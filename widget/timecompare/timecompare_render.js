/*
   All emon_widgets code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.

   Part of the OpenEnergyMonitor project:
   http://openenergymonitor.org
 */

/*
   The timecompare widget draws in the dashboard page.

   It draws one feed several times over, each line a period further back, so
   this week sits on top of last week. depth says how many lines, and the
   length of the window is the period they are offset by.

   timecompare_widget.php loads flot 5.1 and the shared chart helpers.
 */

// The window it opens on, in hours, when initzoom is not set. One week.
var TIMECOMPARE_ZOOM = 168;

// How many lines, when depth is not set.
var TIMECOMPARE_DEPTH = 3;

// How often a window that ends now moves up to it.
var TIMECOMPARE_REFRESH = 60;

function timecompare_widgetlist(){
    var zoomoptions = [
        ["8", "8 " + _Tr("Hours")],
        ["24", _Tr("Day")],
        ["168", _Tr("Week")],
        ["672", _Tr("Month")],
        ["8760", _Tr("Year")]
    ];

    var widgets = {
        "timecompare":
        {
            "offsetx":0,"offsety":0,"width":400,"height":300,
            "menu":"Visualisations",
            "title":_Tr("Time compare"),
            "description":_Tr("Draws one feed several times over, each line one period further back, so this week sits on top of last week. Zoom sets the window and the distance between the lines. Depth is the number of lines."),
            "options":["feedid","fill","depth","npoints","initzoom","colourbg","colouraxis"],
            "optionstype":["feedid","value","value","value","dropbox","colour_picker","colour_picker"],
            "optionsname":[_Tr("Feed"),_Tr("Fill"),_Tr("Depth"),_Tr("Data points"),_Tr("Zoom"),_Tr("Background"),_Tr("Axis colour")],
            "optionshint":[_Tr("Feed source"),_Tr("Fill under line: 1 for on, or an opacity below 1"),_Tr("Number of lines"),_Tr("Blank reads as many as the box is wide"),_Tr("Default visible window interval"),_Tr("Background colour in hex. Blank is white."),_Tr("Axis, label and legend colour in hex. Blank is use default.")],
            "optionsdata":[ , , , ,zoomoptions,"ffffff", ],
            "html":""
        }
    };

    return widgets;
}

var timecompare_widget = {
    mount: function(el, config, ctx){
        var feedid = ctx.feeds.id(config.feedid);

        // Fill under each line. 1 is the half opacity the widget has always
        // drawn, which is what the vis module stored for on. A fraction below
        // one is the opacity itself, 0 is no fill.
        var fill = parseFloat(config.fill);
        if (!isFinite(fill) || fill < 0) fill = 0;
        if (fill >= 1) fill = 0.5;

        var depth = parseInt(config.depth);
        if (!isFinite(depth) || depth < 1) depth = TIMECOMPARE_DEPTH;

        var npoints = parseInt(config.npoints);
        if (!isFinite(npoints) || npoints < 1) npoints = 0;

        var zoom = parseFloat(config.initzoom);
        if (!isFinite(zoom) || zoom < 1) zoom = TIMECOMPARE_ZOOM;

        chart_background(el, config.colourbg, "#ffffff");
        chart_axis(el, config.colouraxis);

        var chart = timecompare_build(el, ctx, feedid, fill, depth, npoints, zoom * 3600);

        return {
            update: function(feeds){
                // A tag:name pair resolves once the poll has answered.
                if (chart.feedid === "" && config.feedid) {
                    chart.feedid = feeds.id(config.feedid);
                    if (chart.feedid !== "") timecompare_start(chart);
                }
                timecompare_refresh(chart);
            },
            resize: function(){ timecompare_plot(chart); },
            destroy: function(){ chart_tooltip_hide(); chart_destroy(chart); }
        };
    }
};

function timecompare_build(element, ctx, feedid, fill, depth, npoints, period){
    var now = Date.now() / 1000;

    var chart = {
        name: _Tr("Time compare"),
        ctx: ctx,
        element: element,
        feedid: feedid,
        fill: fill,
        depth: depth,
        npoints: npoints,
        // Distance between one line and the next, which is also the length of
        // the window. Moving the window changes it.
        period: period,
        view: new ChartView(now - period, now),
        // Whether the window ends now, which is what a length button leaves it
        // doing. A window chosen by hand stays where it was put.
        floating: true,
        series: [],
        plot: null,
        plotel: null,
        bar: null,
        frame: null,
        request: null,
        read: 0
    };

    if (feedid === "") {
        chart_message(element, _Tr("No feed selected"));
        return chart;
    }

    timecompare_start(chart);
    return chart;
}

// Frame, buttons and first read, once the feed is known.
function timecompare_start(chart){
    var frame = chart_frame(chart.element);
    chart.plotel = frame.plot;
    chart.bar = frame.bar;
    chart.frame = frame.frame;
    timecompare_toolbar(chart);
    timecompare_bind(chart);
    timecompare_fetch(chart);
}

/* ── The buttons ─────────────────────────────────────────────────────────── */

// The lengths the bar offers, in days, as the visualisation did.
var TIMECOMPARE_LENGTHS = [[1, "D"], [7, "W"], [28, "M"], [365, "Y"]];

function timecompare_toolbar(chart){
    var lengths = chart_group_of(chart.bar);
    for (var i = 0; i < TIMECOMPARE_LENGTHS.length; i++){
        timecompare_length(chart, lengths, TIMECOMPARE_LENGTHS[i][0], TIMECOMPARE_LENGTHS[i][1]);
    }

    var nav = chart_group_of(chart.bar);
    chart_button(nav, "+", _Tr("Zoom In"), function(){ chart.view.zoom(0.5); timecompare_moved(chart); });
    chart_button(nav, "-", _Tr("Zoom Out"), function(){ chart.view.zoom(2); timecompare_moved(chart); });
    chart_button(nav, "&lt;", _Tr("Earlier"), function(){ chart.view.pan(-1); timecompare_moved(chart); });
    chart_button(nav, "&gt;", _Tr("Later"), function(){ chart.view.pan(1); timecompare_moved(chart); });

    var full = chart_group_of(chart.bar);
    chart_button(full, '<i class="icon-resize-full"></i>', _Tr("Expand"), function(){
            chart_fullscreen(chart.element);
        });
}

// A length button also sets the distance between the lines, so a week window
// compares this week with last week.
function timecompare_length(chart, group, days, label){
    chart_button(group, label, days + " " + _Tr("days"), function(){
            chart.view.days(days);
            chart.period = chart.view.length();
            chart.floating = true;
            timecompare_fetch(chart);
        });
}

// The window was moved by hand, so it stops following the clock. The distance
// between the lines is left as it was, which is what zooming in on one period
// of the comparison means.
function timecompare_moved(chart){
    chart.floating = false;
    timecompare_fetch(chart);
}

/* ── Data ────────────────────────────────────────────────────────────────── */

// One read per line, made together and drawn once all have answered.
function timecompare_fetch(chart){
    var points = chart_points(chart.plotel, chart.npoints);
    var interval = Math.max(1, Math.round(chart.view.length() / points));

    var series = [];
    var asks = [];
    for (var i = 0; i < chart.depth; i++){
        // Furthest back first, so the current period is the line on top and the
        // legend reads oldest to newest as it did.
        var back = chart.depth - i - 1;
        var offset = chart.period * back;
        series.push({ offset: offset, label: timecompare_label(chart, back), data: [] });
        asks.push({
                id: chart.feedid,
                start: chart.view.start - offset,
                end: chart.view.end - offset,
                interval: interval,
                skipmissing: 0,
                limitinterval: 1
            });
    }

    chart_fetch(chart, asks, function(answers){
            for (var i = 0; i < series.length; i++){
                // Moved forward onto the current window, which draws the periods over
                // each other.
                var data = answers[i];
                var moved = [];
                for (var n = 0; n < data.length; n++){
                    if (!data[n]) continue;
                    moved.push([data[n][0] + series[i].offset, data[n][1]]);
                }
                series[i].data = moved;
            }
            chart.series = series;
            timecompare_plot(chart);
        });
}

// The name of one line: the current period, or how many periods back it is.
function timecompare_label(chart, back){
    if (back === 0) return _Tr("Current");

    var unit = timecompare_unit(chart.period);
    return back + " " + (back === 1 ? unit.one : unit.many) + " " + _Tr("Prior");
}

// What the distance between the lines is called. A window of about a day, a
// week, a month or a year is named, and anything else is said in seconds.
function timecompare_unit(period){
    var day = CHART_DAY;
    if (Math.abs(period - day) < day * 0.1) return { one: _Tr("Day"), many: _Tr("Days") };
    if (Math.abs(period - day * 7) < day) return { one: _Tr("Week"), many: _Tr("Weeks") };
    if (Math.abs(period - day * 28) < day * 4) return { one: _Tr("Month"), many: _Tr("Months") };
    if (Math.abs(period - day * 365) < day * 30) return { one: _Tr("Year"), many: _Tr("Years") };

    var seconds = Math.round(period) + " " + _Tr("Seconds");
    return { one: seconds, many: seconds };
}

/* ── Drawing ─────────────────────────────────────────────────────────────── */

function timecompare_plot(chart){
    var options = chart_plot_options(chart);
    if (!options) return;

    var series = [];
    for (var i = 0; i < chart.series.length; i++){
        series.push({
                data: chart.series[i].data,
                label: chart.series[i].label,
                lines: { show: true, fill: chart.fill, lineWidth: 2 }
            });
    }

    $.extend(true, options, {
            xaxis: {
                mode: "time", timezone: "browser",
                min: chart.view.start, max: chart.view.end, autoScale: "none"
            },
            grid: { hoverable: true, clickable: true },
            selection: { mode: "x", color: "#e8cfac" },
            // A click on a name in the legend takes that line off the chart, which is
            // how the visualisation let you pull one period out of the comparison.
            legend: { show: true, position: "nw", toggle: true },
            toggle: { scale: "visible" }
        });
    chart.plot = Flot.plot(chart.plotel, series, options);

    chart_place(chart.plot, chart.frame);
}

/* ── Events ──────────────────────────────────────────────────────────────── */

function timecompare_bind(chart){
    chart.plotel.addEventListener("plotselected", function(event){
            var ranges = chart_event_ranges(event);
            if (!ranges || !ranges.xaxis) return;
            chart.view.start = ranges.xaxis.from;
            chart.view.end = ranges.xaxis.to;
            timecompare_moved(chart);
        });

    var show = function(event){
        var item = chart_event_item(event);
        if (!item) { chart_tooltip_hide(); return; }

        var series = chart.series[item.seriesIndex];
        var offset = series ? series.offset : 0;
        var value = item.datapoint[1];
        if (typeof value !== "number" || !isFinite(value)) { chart_tooltip_hide(); return; }

        // The point is drawn on the current window, so the time it was read at is
        // the time it is drawn at less the offset of its line.
        chart_tooltip(item.pageX, item.pageY, [
                value.toFixed(2),
                chart_date(item.datapoint[0] - offset)
            ]);
    };

    chart.plotel.addEventListener("plothover", show);
    chart.plotel.addEventListener("plotclick", show);
}

/* ── The dashboard update cycle ──────────────────────────────────────────── */

// Called after each poll. A window that ends now moves up to it once
// TIMECOMPARE_REFRESH has passed since the last read.
function timecompare_refresh(chart){
    if (!chart.floating) return;
    chart_refresh(chart, TIMECOMPARE_REFRESH, function(){
            var length = chart.view.length();
            chart.view.end = Date.now() / 1000;
            chart.view.start = chart.view.end - length;
            timecompare_fetch(chart);
        });
}
