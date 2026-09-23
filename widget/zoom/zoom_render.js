/*
   All emon_widgets code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.

   Part of the OpenEnergyMonitor project:
   http://openenergymonitor.org
 */

/*
   The zoom widget draws in the dashboard page.

   It is a pair of feeds, energy per day and power, with a cost worked out from
   a unit price. It reads ten years of daily readings once and draws them four
   ways: years, the months of a year, the days of a month, and the power of one
   day. A click on a bar goes one step in, the back button goes one step out,
   and every view has a window the pan and zoom buttons move, in whole days,
   months or years for the bar views.

   With no power feed it draws the three bar views from the daily feed alone,
   which is what the bargraph visualisation of the vis module did. A stored
   bargraph converts to one of these, see notes/CHARTS.md, so the widget takes
   the colour, background and scale a bargraph carried, and a view to open on.

   The pair of feeds, the linked windows and the cost are the visualisation,
   which is why this is a widget of its own rather than a graph config, see
   notes/CHARTS.md. simplezoom is the same pair without the cost.

   zoom_widget.php loads flot 5.1 and the shared chart helpers.
 */

// How far back to read the daily feed, in days. Ten years, as the
// visualisation did.
var ZOOM_DAYS = 365 * 10;

// How often the daily feed is read again.
var ZOOM_REFRESH = 900;

var ZOOM_COLOUR = "#0096ff";

function zoom_widgetlist(){
    var widgets = {
        "zoom":
        {
            "offsetx":0,"offsety":0,"width":400,"height":300,
            "menu":"Visualisations",
            "title":_Tr("Zoom"),
            "description":_Tr("Draws energy per day as bars with a cost worked out from the unit price. Opens on years and steps into the months of a year, the days of a month and the power of one day when a bar is clicked."),
            "options":["power","kwhd","currency","currency_after_val","pricekwh","delta","scale","colour","colourbg","colouraxis","view"],
            "optionstype":["feedid","feedid","value","value","value","boolean","value","colour_picker","colour_picker","colour_picker","dropbox"],
            "optionsname":[_Tr("Power"),_Tr("kwhd"),_Tr("Currency"),_Tr("Currency position"),_Tr("Kwh price"),_Tr("delta"),_Tr("Scale"),_Tr("Colour"),_Tr("Background"),_Tr("Axis colour"),_Tr("Opens on")],
            "optionshint":[_Tr("Power to show. Leave empty to draw the bars only"),_Tr("kwhd source"),_Tr("Currency to show"),_Tr("0 = before value, 1 = after value"),_Tr("Set kwh price"),_Tr("Show difference between each bar"),_Tr("Multiply the kwhd feed by this"),_Tr("Bar colour in hex. Blank is use default."),_Tr("Background colour in hex. Blank is clear."),_Tr("Axis, label and legend colour in hex. Blank is use default."),_Tr("The view the widget opens on")],
            "optionsdata":[ , , , , , , , ZOOM_COLOUR.substr(1), , , [["days", _Tr("Days")], ["months", _Tr("Months")], ["years", _Tr("Years")]] ],
            "html":""
        }
    };

    return widgets;
}

var zoom_widget = {
    mount: function(el, config, ctx){
        var power = ctx.feeds.id(config.power);
        var kwhd = ctx.feeds.id(config.kwhd);
        var currency = config.currency || "";
        var after = config.currency_after_val == "1";
        var price = parseFloat(config.pricekwh);
        if (!isFinite(price)) price = 0;
        var delta = config.delta == "1";
        var scale = parseFloat(config.scale);
        if (!isFinite(scale) || scale === 0) scale = 1;
        var colour = chart_hex(config.colour) || ZOOM_COLOUR;
        var open = config.view;
        if (open !== "months" && open !== "years") open = "days";

        chart_background(el, config.colourbg, "");
        chart_axis(el, config.colouraxis);

        var chart = zoom_build(el, ctx, {
                power: power, kwhd: kwhd, currency: currency, after: after, price: price,
                delta: delta, scale: scale, colour: colour, open: open
            });

        return {
            update: function(feeds){
                // A tag:name pair resolves once the poll has answered. The daily feed
                // is what the chart is built on. The power feed is only read on a
                // click into a day, so it is taken whenever it becomes known.
                if (chart.kwhd === "" && config.kwhd) {
                    chart.kwhd = feeds.id(config.kwhd);
                    if (chart.kwhd !== "") zoom_start(chart);
                }
                if (chart.power === "" && config.power) chart.power = feeds.id(config.power);
                zoom_refresh(chart);
            },
            resize: function(){ zoom_plot(chart, chart.ticks); },
            destroy: function(){ chart_abort(chart, "power_request"); chart_destroy(chart); }
        };
    }
};

function zoom_build(element, ctx, opts){
    var now = Date.now() / 1000;

    var chart = {
        name: _Tr("Zoom"),
        ctx: ctx,
        element: element,
        power: opts.power,
        kwhd: opts.kwhd,
        currency: opts.currency,
        after: opts.after,
        price: opts.price,
        delta: opts.delta,
        scale: opts.scale,
        colour: opts.colour,
        // The view the widget opens on, and goes back to after a read.
        open: opts.open,
        // years, months, days or power.
        view: opts.open,
        // Daily readings, and what has been worked out from them for the bar
        // view being shown.
        kwh: [],
        years: null,
        months: null,
        // What is drawn, the window of the power view, and the window of each
        // bar view, kept so the back button returns to where it was.
        series: [],
        window: new ChartView(now - CHART_DAY, now),
        windows: { years: null, months: null, days: null },
        // Set once a window has been moved by hand, so a read does not take
        // somebody out of what they are looking at.
        moved: false,
        totals: "",
        plot: null,
        plotel: null,
        bar: null,
        frame: null,
        top: null,
        bottom: null,
        back: null,
        backbtn: null,
        lengths: null,
        request: null,
        power_request: null,
        read: 0,
        ticks: "day",
    };

    if (chart.kwhd === "") {
        chart_message(element, _Tr("No feed selected"));
        return chart;
    }

    zoom_start(chart);
    return chart;
}

// Frame, buttons and first read, once the daily feed is known.
function zoom_start(chart){
    var frame = chart_frame(chart.element);
    chart.plotel = frame.plot;
    chart.bar = frame.bar;
    chart.frame = frame.frame;
    chart.top = chart_readout(frame.frame, "top");
    chart.bottom = chart_readout(frame.frame, "bottom");
    zoom_toolbar(chart);
    zoom_bind(chart);
    zoom_fetch(chart);
}

/* ── The buttons ─────────────────────────────────────────────────────────── */

// The lengths the bar offers in the power view, in days.
var ZOOM_LENGTHS = [[1, "D"], [7, "W"], [30, "M"], [365, "Y"]];

function zoom_toolbar(chart){
    chart.back = chart_group_of(chart.bar);
    chart.backbtn = chart_button(chart.back, _Tr("Back"), _Tr("Back"), function(){ zoom_back(chart); });

    chart.lengths = chart_group_of(chart.bar);
    for (var i = 0; i < ZOOM_LENGTHS.length; i++){
        zoom_length(chart, chart.lengths, ZOOM_LENGTHS[i][0], ZOOM_LENGTHS[i][1]);
    }

    var nav = chart_group_of(chart.bar);
    chart_button(nav, "+", _Tr("Zoom In"), function(){ zoom_nav(chart, "in"); });
    chart_button(nav, "-", _Tr("Zoom Out"), function(){ zoom_nav(chart, "out"); });
    chart_button(nav, "&lt;", _Tr("Earlier"), function(){ zoom_nav(chart, -1); });
    chart_button(nav, "&gt;", _Tr("Later"), function(){ zoom_nav(chart, 1); });

    var full = chart_group_of(chart.bar);
    chart_button(full, '<i class="icon-resize-full"></i>', _Tr("Expand"), function(){
            chart_fullscreen(chart.element);
        });
}

function zoom_length(chart, group, days, label){
    chart_button(group, label, days + " " + _Tr("days"), function(){
            chart.window.days(days);
            chart.moved = true;
            zoom_power(chart);
        });
}

// One press of a pan or zoom button. The power view moves its window as a
// span of seconds. A bar view moves its window in whole periods: zooming
// halves or doubles the number shown, keeping the end where it is, and a pan
// moves the window by its own length.
function zoom_nav(chart, how){
    chart.moved = true;

    if (chart.view === "power") {
        if (how === "in") chart.window.zoom(0.5);
        else if (how === "out") chart.window.zoom(2);
        else chart.window.pan(how);
        zoom_power(chart);
        return;
    }

    var w = chart.windows[chart.view];
    if (!w) return;
    var n = zoom_periods(chart.view, w.start, w.end);
    var start = w.start, end = w.end;

    if (how === "in") {
        start = zoom_step(chart.view, end, -Math.max(1, Math.round(n / 2)));
    } else if (how === "out") {
        start = zoom_step(chart.view, end, -Math.max(2, n * 2));
    } else {
        start = zoom_step(chart.view, start, n * how);
        end = zoom_step(chart.view, end, n * how);
    }
    zoom_bars_view(chart, chart.view, start, end);
}

// The lengths belong to the power view only. The back button names the view
// it goes to, and the years view has nothing to go back to. Nothing to do
// before the toolbar is built.
function zoom_buttons(chart){
    if (!chart.back) return;
    var to = { months: _Tr("Annual"), days: _Tr("Monthly"), power: _Tr("Daily") };
    chart.back.toggle(chart.view !== "years");
    if (to[chart.view]) {
        chart.backbtn.html("&lt; " + to[chart.view]).attr("title", _Tr("Back to") + " " + to[chart.view]);
    }
    chart.lengths.toggle(chart.view === "power");
}

/* ── Periods ─────────────────────────────────────────────────────────────── */

// A boundary moved by n whole periods of a bar view, in local time, so a
// month or a year starts where the calendar says it does.
function zoom_step(view, seconds, n){
    var d = new Date(seconds * 1000);
    if (view === "years") d.setFullYear(d.getFullYear() + n);
    else if (view === "months") d.setMonth(d.getMonth() + n);
    else d.setDate(d.getDate() + n);
    return d.getTime() / 1000;
}

// How many whole periods a window spans.
function zoom_periods(view, start, end){
    var a = new Date(start * 1000), b = new Date(end * 1000);
    if (view === "years") return Math.max(1, b.getFullYear() - a.getFullYear());
    if (view === "months") return Math.max(1, (b.getFullYear() - a.getFullYear()) * 12 + b.getMonth() - a.getMonth());
    return Math.max(1, Math.round((end - start) / CHART_DAY));
}

// The start of the period a time falls in.
function zoom_floor(view, seconds){
    var d = new Date(seconds * 1000);
    if (view === "years") return new Date(d.getFullYear(), 0, 1).getTime() / 1000;
    if (view === "months") return new Date(d.getFullYear(), d.getMonth(), 1).getTime() / 1000;
    return new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime() / 1000;
}

// The nominal length of one period, for the half bar of room either side of
// the window.
function zoom_period_length(view){
    if (view === "years") return 365 * CHART_DAY;
    if (view === "months") return 30 * CHART_DAY;
    return CHART_DAY;
}

/* ── Data ────────────────────────────────────────────────────────────────── */

// The daily feed, read once. Everything but the power view is worked out from
// it without asking for anything more.
function zoom_fetch(chart){
    var end = Date.now() / 1000;

    chart_fetch(chart, {
            id: chart.kwhd,
            start: end - (CHART_DAY * ZOOM_DAYS),
            end: end,
            interval: "daily",
            delta: chart.delta,
            skipmissing: 0
        }, function(data){
            if (chart.scale !== 1) {
                for (var i = 0; i < data.length; i++){
                    if (data[i][1] !== null && isFinite(data[i][1])) data[i][1] = data[i][1] * chart.scale;
                }
            }
            chart.kwh = data;
            chart.totals = zoom_totals(chart);
            zoom_open(chart);
        });
}

// The line under the chart: what the whole feed comes to, and what it costs.
function zoom_totals(chart){
    var total = 0, days = 0;
    for (var i = 0; i < chart.kwh.length; i++){
        var value = chart.kwh[i][1];
        if (value === null || !isFinite(value)) continue;
        total += value;
        days++;
    }
    if (!days) return "";

    var perday = total / days;
    var kwh = _Tr("kWh");

    var line = _Tr("Total:") + " " + total.toFixed(0) + " " + kwh + zoom_priced(chart, total, 0)
    + " | " + _Tr("Average:") + " " + perday.toFixed(1) + " " + kwh + zoom_priced(chart, perday, 2);
    if (!zoom_has_price(chart)) return line;

    return line
    + " | " + zoom_cost(chart, perday * chart.price * 7, 0) + " " + _Tr("a week")
    + ", " + zoom_cost(chart, perday * chart.price * 365, 0) + " " + _Tr("a year")
    + " | " + _Tr("Unit price:") + " " + zoom_cost(chart, chart.price, 2);
}

// Whether a unit price was given. Without one the readouts say the energy
// and nothing about what it costs.
function zoom_has_price(chart){
    return isFinite(chart.price) && chart.price > 0;
}

// What an amount of energy costs, written after it, or nothing when there is
// no unit price.
function zoom_priced(chart, kwh, places){
    if (!zoom_has_price(chart)) return "";
    return " : " + zoom_cost(chart, kwh * chart.price, places);
}

// A value with the currency the author gave, before or after it.
function zoom_cost(chart, value, places){
    var shown = (isFinite(value) ? value : 0).toFixed(places);
    return chart.after ? shown + chart.currency : chart.currency + shown;
}

/* ── The four views ──────────────────────────────────────────────────────── */

// A bar view over a window. With no window given the view keeps the one it
// had, or opens on its default: every year there is data for, the months of
// this year, or the last thirty days.
function zoom_bars_view(chart, view, start, end){
    if (start === undefined) {
        var kept = chart.windows[view];
        if (kept) { start = kept.start; end = kept.end; }
        else {
            var now = Date.now() / 1000;
            if (view === "years") {
                var first = chart.kwh.length ? chart.kwh[0][0] : now;
                start = zoom_floor("years", first);
                end = zoom_step("years", zoom_floor("years", now), 1);
            } else if (view === "months") {
                start = zoom_floor("years", now);
                end = zoom_step("years", start, 1);
            } else {
                end = zoom_step("days", zoom_floor("days", now), 1);
                start = zoom_step("days", end, -30);
            }
        }
    }
    chart.windows[view] = { start: start, end: end };
    chart.view = view;

    var data = chart_range(chart.kwh, start, end);
    if (view === "years") {
        chart.years = chart_years(data);
        chart.series = chart.years.data;
    } else if (view === "months") {
        chart.months = chart_months(data);
        chart.series = chart.months.data;
    } else {
        chart.series = data;
    }

    zoom_title(chart);
    chart.bottom.text(chart.totals);
    zoom_plot(chart, { years: "year", months: "month", days: "day" }[view]);
}

function zoom_years_view(chart){
    zoom_bars_view(chart, "years");
}

// The months of one year, or the months the view was on.
function zoom_months_view(chart, year){
    if (year === undefined) zoom_bars_view(chart, "months");
    else {
        var start = new Date(year, 0, 1).getTime() / 1000;
        zoom_bars_view(chart, "months", start, zoom_step("years", start, 1));
    }
}

// The days of one month, or the days the view was on.
function zoom_days_view(chart, month, year){
    if (month === undefined) zoom_bars_view(chart, "days");
    else {
        var start = new Date(year, month, 1).getTime() / 1000;
        zoom_bars_view(chart, "days", start, zoom_step("months", start, 1));
    }
}

// The power of one day, which is the only view that reads the other feed.
function zoom_power(chart, window){
    if (window) {
        chart.window.start = window.start;
        chart.window.end = window.end;
    }
    chart.view = "power";
    chart.top.text(_Tr("Power"));

    chart.window.calc_interval(chart_points(chart.plotel));

    // Held apart from the daily read, so a click into a day does not drop a
    // read of the daily feed in flight.
    chart_fetch(chart, {
            id: chart.power,
            start: chart.window.start,
            end: chart.window.end,
            interval: chart.window.interval,
            skipmissing: 1
        }, function(data){
            chart.series = data;

            var stats = chart_stats(data);
            chart.bottom.text(chart_date(chart.window.start, "day") + ": "
                + _Tr("Average:") + " " + stats.mean.toFixed(0) + "W | "
                + stats.kwh.toFixed(2) + " " + _Tr("kWh") + zoom_priced(chart, stats.kwh, 2));

            zoom_plot(chart, null);
        }, "power_request");
}

// The view the widget opens on, after each read of the daily feed. Every
// window is dropped, so the view opens on its default.
function zoom_open(chart){
    chart.windows = { years: null, months: null, days: null };
    chart.moved = false;
    zoom_bars_view(chart, chart.open);
}

// One step out.
function zoom_back(chart){
    if (chart.view === "months") zoom_years_view(chart);
    else if (chart.view === "days") zoom_months_view(chart);
    else if (chart.view === "power") zoom_days_view(chart);
}

/* ── Drawing ─────────────────────────────────────────────────────────────── */

function zoom_plot(chart, ticks){
    chart.ticks = ticks;
    var options = chart_plot_options(chart);
    if (!options) return;
    zoom_buttons(chart);

    var series = { data: chart.series, color: chart.colour };
    var xaxis = { mode: "time", timezone: "browser" };

    if (chart.view === "power") {
        series.lines = { show: true, fill: 0.5, lineWidth: 2 };
        xaxis.min = chart.window.start;
        xaxis.max = chart.window.end;
        xaxis.autoScale = "none";
    } else {
        series.bars = { show: true, align: "center", barWidth: CHART_BAR_WIDTH, fill: true };
        xaxis.minTickSize = [1, ticks];
        xaxis.tickLength = 1;
        // Bars sit centred on the start of their period, so the axis runs half a
        // period either side of the window to hold the first and last whole.
        var w = chart.windows[chart.view];
        if (w) {
            var half = zoom_period_length(chart.view) / 2;
            xaxis.min = w.start - half;
            xaxis.max = w.end - half;
            xaxis.autoScale = "none";
        }
    }

    $.extend(true, options, {
            xaxis: xaxis,
            yaxis: { min: 0 },
            grid: { hoverable: true, clickable: true },
            selection: { mode: chart.view === "power" ? "x" : null, color: "#e8cfac" }
        });
    chart.plot = Flot.plot(chart.plotel, [series], options);

    chart_place(chart.plot, chart.frame);
}

/* ── Events ──────────────────────────────────────────────────────────────── */

function zoom_bind(chart){
    // A click on a bar goes one step in. In the power view a selection is a
    // window rather than a step.
    chart.plotel.addEventListener("plotclick", function(event){
            var item = chart_event_item(event);
            if (!item) return;
            var at = new Date(item.datapoint[0] * 1000);

            if (chart.view === "years") zoom_months_view(chart, at.getFullYear());
            else if (chart.view === "months") zoom_days_view(chart, at.getMonth(), at.getFullYear());
            else if (chart.view === "days" && chart.power !== "") {
                zoom_power(chart, { start: item.datapoint[0], end: item.datapoint[0] + CHART_DAY });
            }
        });

    chart.plotel.addEventListener("plotselected", function(event){
            if (chart.view !== "power") return;
            var ranges = chart_event_ranges(event);
            if (!ranges || !ranges.xaxis) return;
            zoom_power(chart, { start: ranges.xaxis.from, end: ranges.xaxis.to });
        });

    chart.plotel.addEventListener("plothover", function(event){
            var item = chart_event_item(event);
            if (!item) { zoom_title(chart); return; }
            chart.top.text(zoom_reading(chart, item));
        });
}

// The name of the view and the window it is on, put back when the pointer
// leaves a bar.
function zoom_title(chart){
    var names = { years: _Tr("Annual"), months: _Tr("Monthly"),
        days: _Tr("Daily"), power: _Tr("Power") };
    var w = chart.windows[chart.view];
    if (!w || chart.view === "power") { chart.top.text(names[chart.view] || ""); return; }

    var span;
    if (chart.view === "years") {
        span = chart_date(w.start, "year") + " - " + chart_date(zoom_step("years", w.end, -1), "year");
    } else if (chart.view === "months") {
        span = chart_date(w.start, "month") + " - " + chart_date(zoom_step("months", w.end, -1), "month");
    } else {
        span = chart_date(w.start, "day") + " - " + chart_date(zoom_step("days", w.end, -1), "day");
    }
    chart.top.text(names[chart.view] + ": " + span);
}

// The bar under the pointer, said the way the view says it: what it comes to,
// what it costs, and what that is per day, per month or per year.
function zoom_reading(chart, item){
    var value = item.datapoint[1];
    if (typeof value !== "number" || !isFinite(value)) return "";

    var kwh = _Tr("kWh");
    var average = _Tr("Average:");

    if (chart.view === "power") return value.toFixed(0) + "W | " + chart_date(item.datapoint[0]);

    if (chart.view === "years") {
        var ydays = (chart.years && chart.years.days[item.dataIndex]) || 365;
        return chart_date(item.datapoint[0], "year") + " | " + value.toFixed(0) + " " + kwh
        + zoom_priced(chart, value, 2)
        + " | " + average + " " + (value / ydays).toFixed(1) + " " + kwh + "/d, "
        + (value / ydays * 30).toFixed(0) + " " + kwh + "/m";
    }

    if (chart.view === "months") {
        var mdays = (chart.months && chart.months.days[item.dataIndex]) || 30;
        return chart_date(item.datapoint[0], "month") + " | " + value.toFixed(0) + " " + kwh
        + zoom_priced(chart, value, 2)
        + " | " + average + " " + (value / mdays).toFixed(1) + " " + kwh + "/d, "
        + (value * 12).toFixed(0) + " " + kwh + "/y";
    }

    return chart_date(item.datapoint[0], "day") + " | " + value.toFixed(1) + " " + kwh
    + zoom_priced(chart, value, 2)
    + " | " + average + " " + (value * 30).toFixed(0) + " " + kwh + "/m, "
    + (value * 365).toFixed(0) + " " + kwh + "/y";
}

/* ── The dashboard update cycle ──────────────────────────────────────────── */

// Called after each poll. Reads the daily feed again once ZOOM_REFRESH has
// passed since the last read. Reading again goes back to the view the widget
// opened on, so it waits while somebody is looking at anything else, or has
// moved the window by hand.
function zoom_refresh(chart){
    if (chart.view !== chart.open || chart.moved) return;
    chart_refresh(chart, ZOOM_REFRESH, zoom_fetch);
}
