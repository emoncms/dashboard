/*
   All emon_widgets code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.

   Part of the OpenEnergyMonitor project:
   http://openenergymonitor.org
 */

/*
   The orderbars widget draws in the dashboard page. It reads five years of
   daily readings and draws them as bars sorted by size, largest first, so
   there is no time axis: the position of a bar is its rank.

   orderbars_widget.php loads flot 5.1 and the shared chart helpers.
 */

// How far back to read, in days. Five years, as the visualisation did.
var ORDERBARS_DAYS = 365 * 5;

// How often to read the feed again. Daily readings, so there is a new bar once
// a day and nothing to be gained from asking more often than this.
var ORDERBARS_REFRESH = 900;

var ORDERBARS_COLOUR = "#0096ff";

function orderbars_widgetlist(){
    var widgets = {
        "orderbars":
        {
            "offsetx":0,"offsety":0,"width":400,"height":300,
            "menu":"Visualisations",
            "title":_Tr("Order bars"),
            "description":_Tr("Draws daily readings as bars sorted by size, largest first. Position of a bar is its rank, there is no time axis."),
            "options":["feedid","delta","colourbg","colouraxis"],
            "optionstype":["feedid","boolean","colour_picker","colour_picker"],
            "optionsname":[_Tr("Feed"),_Tr("delta"),_Tr("Background"),_Tr("Axis colour")],
            "optionshint":[_Tr("Feed source"),_Tr("Show difference between each bar"),_Tr("Background colour in hex. Blank is white."),_Tr("Axis, label and legend colour in hex. Blank is use default.")],
            "optionsdata":[ , ,"ffffff", ],
            "html":""
        }
    };

    return widgets;
}

var orderbars_widget = {
    mount: function(el, config, ctx){
        var feedid = ctx.feeds.id(config.feedid);
        var delta = config.delta == "1";

        chart_background(el, config.colourbg, "#ffffff");
        chart_axis(el, config.colouraxis);

        var chart = orderbars_build(el, ctx, feedid, delta);

        return {
            update: function(feeds){
                // A tag:name pair resolves once the poll has answered.
                if (chart.feedid === "" && config.feedid) {
                    chart.feedid = feeds.id(config.feedid);
                    if (chart.feedid !== "") orderbars_start(chart);
                }
                orderbars_refresh(chart);
            },
            resize: function(){ orderbars_plot(chart); },
            destroy: function(){ chart_destroy(chart); }
        };
    }
};

function orderbars_build(element, ctx, feedid, delta){
    var chart = {
        name: _Tr("Order bars"),
        ctx: ctx,
        element: element,
        feedid: feedid,
        delta: delta,
        data: [],
        plot: null,
        plotel: null,
        request: null,
        read: 0
    };

    if (feedid === "") {
        chart_message(element, _Tr("No feed selected"));
        return chart;
    }

    orderbars_start(chart);
    return chart;
}

// Frame and first read, once the feed is known.
function orderbars_start(chart){
    chart.plotel = chart_frame(chart.element).plot;
    orderbars_fetch(chart);
}

function orderbars_fetch(chart){
    var end = Date.now() / 1000;

    chart_fetch(chart, {
            id: chart.feedid,
            start: end - (CHART_DAY * ORDERBARS_DAYS),
            end: end,
            interval: "daily",
            delta: chart.delta,
            skipmissing: 1
        }, function(data){
            chart.data = orderbars_sort(data);
            orderbars_plot(chart);
        });
}

// The readings by size, largest first, drawn against their rank rather than
// against the day they fell on.
function orderbars_sort(data){
    var values = [];
    for (var i = 0; i < data.length; i++){
        if (data[i][1] === null || !isFinite(data[i][1])) continue;
        values.push(parseFloat(data[i][1]));
    }
    values.sort(function(a, b){ return b - a; });

    var sorted = [];
    for (var n = 0; n < values.length; n++) sorted.push([n, values[n]]);
    return sorted;
}

function orderbars_plot(chart){
    var options = chart_plot_options(chart);
    if (!options) return;

    options.xaxis = { show: false };
    options.yaxis.min = 0;
    chart.plot = Flot.plot(chart.plotel, [{
                data: chart.data,
                color: ORDERBARS_COLOUR,
                bars: { show: true, align: "center", barWidth: CHART_BAR_WIDTH, fill: true }
            }], options);
}

// Called after each poll. Reads the feed again once ORDERBARS_REFRESH has
// passed since the last read.
function orderbars_refresh(chart){
    chart_refresh(chart, ORDERBARS_REFRESH, orderbars_fetch);
}
