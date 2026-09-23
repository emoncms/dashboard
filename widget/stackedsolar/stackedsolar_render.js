/*
   All emon_widgets code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.

   Part of the OpenEnergyMonitor project:
   http://openenergymonitor.org
 */

/*
   The stackedsolar widget draws in the dashboard page.

   It is given a solar feed and a consumption feed and draws three series
   stacked: the solar, the import worked out per day as consumption less
   solar, and the export as the rest of the solar. Neither of the two worked
   out series is a feed, which is why this is a widget of its own rather than a
   graph config like stacked, see notes/CHARTS.md.

   It opens on months and drills into the days of one when a bar is clicked.
   Clicking away from the bars goes back.

   stackedsolar_widget.php loads flot 5.1 and the shared chart helpers.
 */

// How far back to read, in days. Five years, as the visualisation did.
var STACKEDSOLAR_DAYS = 365 * 5;

// How often to read the feeds again. Daily readings, so there is nothing to be
// gained from asking more often than this.
var STACKEDSOLAR_REFRESH = 900;

var STACKEDSOLAR_COLOURS = ["#e0c21f", "#4e9acf", "#AA96ff"];

function stackedsolar_widgetlist(){
    var widgets = {
        "stackedsolar":
        {
            "offsetx":0,"offsety":0,"width":400,"height":300,
            "menu":"Visualisations",
            "title":_Tr("Stacked solar"),
            "description":_Tr("Draws solar, import and export stacked by month, worked out from a solar feed and a consumption feed. A click on a bar shows the days of that month."),
            "options":["solar","consumption","delta","colourbg","colouraxis"],
            "optionstype":["feedid","feedid","boolean","colour_picker","colour_picker"],
            "optionsname":[_Tr("Solar"),_Tr("Consumption"),_Tr("delta"),_Tr("Background"),_Tr("Axis colour")],
            "optionshint":[_Tr("Solar feed value"),_Tr("Consumption feed value"),_Tr("Show difference between each bar"),_Tr("Background colour in hex. Blank is white."),_Tr("Axis, label and legend colour in hex. Blank is use default.")],
            "optionsdata":[ , , ,"ffffff", ],
            "html":""
        }
    };

    return widgets;
}

var stackedsolar_widget = {
    mount: function(el, config, ctx){
        var solar = ctx.feeds.id(config.solar);
        var consumption = ctx.feeds.id(config.consumption);
        var delta = config.delta == "1";

        chart_background(el, config.colourbg, "#ffffff");
        chart_axis(el, config.colouraxis);

        var chart = stackedsolar_build(el, ctx, solar, consumption, delta);

        return {
            update: function(feeds){
                // A tag:name pair resolves once the poll has answered.
                if (!chart.plotel && (chart.solar === "" || chart.consumption === "")) {
                    if (config.solar) chart.solar = feeds.id(config.solar);
                    if (config.consumption) chart.consumption = feeds.id(config.consumption);
                    if (chart.solar !== "" && chart.consumption !== "") stackedsolar_start(chart);
                }
                stackedsolar_refresh(chart);
            },
            resize: function(){ stackedsolar_plot(chart, chart.ticks); },
            destroy: function(){ chart_destroy(chart); }
        };
    }
};

function stackedsolar_build(element, ctx, solar, consumption, delta){
    var chart = {
        name: _Tr("Stacked solar"),
        ctx: ctx,
        element: element,
        solar: solar,
        consumption: consumption,
        delta: delta,
        // Daily readings, and the three series of the view being shown.
        days: { solar: [], import: [], export: [] },
        months: null,
        series: [[], [], []],
        view: "months",
        ticks: "month",
        plot: null,
        plotel: null,
        frame: null,
        readout: null,
        request: null,
        read: 0
    };

    if (solar === "" || consumption === "") {
        chart_message(element, _Tr("No feed selected"));
        return chart;
    }

    stackedsolar_start(chart);
    return chart;
}

// Frame, events and first read, once both feeds are known.
function stackedsolar_start(chart){
    var frame = chart_frame(chart.element);
    chart.plotel = frame.plot;
    chart.frame = frame.frame;
    chart.readout = chart_readout(frame.frame);
    stackedsolar_bind(chart);
    stackedsolar_fetch(chart);
}

/* ── Data ────────────────────────────────────────────────────────────────── */

// Both feeds, read together.
function stackedsolar_fetch(chart){
    var end = Date.now() / 1000;
    var start = end - (CHART_DAY * STACKEDSOLAR_DAYS);
    var ask = function(id){
        return { id: id, start: start, end: end, interval: "daily", delta: chart.delta, skipmissing: 0 };
    };

    chart_fetch(chart, [ask(chart.solar), ask(chart.consumption)], function(answers){
            chart.days = stackedsolar_derive(answers[0], answers[1]);
            chart.months = {
                solar: chart_months(chart.days.solar),
                import: chart_months(chart.days.import),
                export: chart_months(chart.days.export)
            };
            stackedsolar_months_view(chart);
        });
}

// The three series, per day. A day the two feeds do not share is left out,
// because an import cannot be worked out without both halves of it.
function stackedsolar_derive(solar_data, use_data){
    var use = {};
    for (var i = 0; i < use_data.length; i++){
        if (!use_data[i]) continue;
        use[use_data[i][0]] = use_data[i][1] === null ? 0 : parseFloat(use_data[i][1]);
    }

    var days = { solar: [], import: [], export: [] };

    for (var n = 0; n < solar_data.length; n++){
        if (!solar_data[n]) continue;
        var time = solar_data[n][0];
        if (use[time] === undefined) continue;

        var generated = solar_data[n][1] === null ? 0 : parseFloat(solar_data[n][1]);
        if (!isFinite(generated)) generated = 0;
        var consumed = isFinite(use[time]) ? use[time] : 0;

        var balance = consumed - generated;
        days.solar.push([time, generated]);
        days.import.push([time, balance > 0 ? balance : 0]);
        days.export.push([time, balance > 0 ? 0 : -balance]);
    }

    return days;
}

/* ── The two views ───────────────────────────────────────────────────────── */

function stackedsolar_months_view(chart){
    if (!chart.months) return;
    chart.view = "months";
    chart.series = [chart.months.solar.data, chart.months.import.data, chart.months.export.data];
    stackedsolar_plot(chart, "month");
}

function stackedsolar_days_view(chart, seconds){
    var date = new Date(seconds * 1000);
    var month = date.getMonth();
    var year = date.getFullYear();

    chart.view = "days";
    chart.series = [
        chart_days_month(chart.days.solar, month, year),
        chart_days_month(chart.days.import, month, year),
        chart_days_month(chart.days.export, month, year)
    ];
    stackedsolar_plot(chart, "day");
}

/* ── Drawing ─────────────────────────────────────────────────────────────── */

function stackedsolar_plot(chart, ticks){
    var options = chart_plot_options(chart);
    if (!options) return;

    var series = [];
    for (var i = 0; i < chart.series.length; i++){
        series.push({
                data: chart.series[i],
                color: STACKEDSOLAR_COLOURS[i],
                stack: true,
                bars: { show: true, align: "center", barWidth: CHART_BAR_WIDTH, fill: true }
            });
    }

    $.extend(true, options, {
            xaxis: { mode: "time", timezone: "browser", minTickSize: [1, ticks], tickLength: 1 },
            yaxis: { min: 0 },
            grid: { hoverable: true, clickable: true }
        });
    chart.plot = Flot.plot(chart.plotel, series, options);

    chart.ticks = ticks;
    chart_place(chart.plot, chart.frame);
}

/* ── Events ──────────────────────────────────────────────────────────────── */

// A click on a bar of the month view drills into its days. A click away from
// the bars goes back, which is how the visualisation was navigated.
function stackedsolar_bind(chart){
    chart.plotel.addEventListener("plotclick", function(event){
            var item = chart_event_item(event);
            if (item && chart.view === "months") stackedsolar_days_view(chart, item.datapoint[0]);
            else if (!item && chart.view === "days") stackedsolar_months_view(chart);
        });

    chart.plotel.addEventListener("plothover", function(event){
            var item = chart_event_item(event);
            if (!item) { chart.readout.text(""); return; }

            var names = [_Tr("Solar"), _Tr("Import"), _Tr("Export")];
            var name = names[item.seriesIndex] || "";
            // A stacked series carries what it was stacked on in the third slot, so
            // the reading is the difference.
            var point = item.datapoint;
            var value = point[2] !== undefined ? point[1] - point[2] : point[1];
            if (typeof value !== "number" || !isFinite(value)) { chart.readout.text(""); return; }

            var when = chart.view === "months"
            ? chart_date(point[0], "month") : chart_date(point[0], "day");
            var unit = chart.view === "months" ? _Tr("kWh/month") : _Tr("kWh/d");

            chart.readout.text(name + " " + value.toFixed(1) + " " + unit + " | " + when);
        });
}

/* ── The dashboard update cycle ──────────────────────────────────────────── */

// Called after each poll. Reads the feeds again once STACKEDSOLAR_REFRESH has
// passed since the last read. Reading again draws the months, so it waits
// while somebody is looking at the days of one of them.
function stackedsolar_refresh(chart){
    if (chart.view !== "months") return;
    chart_refresh(chart, STACKEDSOLAR_REFRESH, stackedsolar_fetch);
}
