/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:  http://openenergymonitor.org
*/

/*
  What the ported vis widgets share.

  Each of them was a page of its own in an iframe, so each one read the time
  window, the feed and the formatting from globals. On a dashboard there are
  several of them in one document, so the window becomes an object a widget
  holds one of, and the rest are functions taking what they work on.

  This is Lib/vis.helper.js and visualisations/common/daysmonthsyears.js
  brought over, minus the view singleton, which is now ChartView, and minus the
  url parameter reading, which a widget in the page does not need.

  Times are in seconds throughout, which is what the feed API returns with
  timeformat=unix and what flot 5.1 plots on a time axis.
*/

var CHART_DAY = 86400;

// The colour of the axes, tick labels and readouts when a widget is given
// none, and the line around the plot area, matching the graph widget.
var CHART_AXIS_COLOUR = "#333";
var CHART_GRID_BORDER = "#ccc";

/* ── Colours ─────────────────────────────────────────────────────────────── */

// A colour option as a hex colour, with the hash the designer strips put
// back, or an empty string when it is empty or not a colour.
function chart_hex(value){
    var hex = String(value === undefined || value === null ? "" : value).trim();
    if (hex !== "" && hex.charAt(0) !== "#") hex = "#" + hex;
    return /^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/.test(hex) ? hex : "";
}

// The background of a box. The designer writes none for no colour at all,
// which shows the dashboard behind the chart. An empty option takes the
// fallback, white for the widgets that drew on a white iframe body.
function chart_background(element, colour, fallback){
    var value = String(colour === undefined || colour === null ? "" : colour).trim().toLowerCase();
    var hex = value === "none" ? "" : (chart_hex(value) || fallback || "");
    element.style.backgroundColor = hex;
}

// Axis colour of a box, from its colouraxis option. Set as the text colour
// of the box so the readouts and the legend follow it, and read back from
// there by chart_axis_options when the chart is drawn.
function chart_axis(element, colour){
    element.style.color = chart_hex(colour);
}

// Font, grid colour and border for a plot, in the axis colour chart_axis put
// on the box, or the defaults.
function chart_axis_options(element){
    var set = element.style.color;
    var colour = set || CHART_AXIS_COLOUR;
    return {
        font: { color: colour, fill: colour },
        color: colour,
        border: set || CHART_GRID_BORDER
    };
}

// How many datapoints to read for a box, from its width: the same numbers
// the graph renderer uses, see POINTS_PER_PIXEL in graph.render.js. An
// npoints above zero is taken as it is.
var CHART_POINTS_PER_PIXEL = 2;
var CHART_POINTS_MIN = 300;
var CHART_POINTS_MAX = 2000;
var CHART_POINTS_DEFAULT = 600;

function chart_points(element, npoints){
    if (npoints > 0) return npoints;
    var width = element ? element.clientWidth : 0;
    if (!width) return CHART_POINTS_DEFAULT;
    return Math.min(CHART_POINTS_MAX,
        Math.max(CHART_POINTS_MIN, Math.round(width * CHART_POINTS_PER_PIXEL)));
}

// The steps a window is read at, as vis.helper.js rounded them.
var CHART_INTERVALS = [
    5, 10, 15, 20, 30, 60, 120, 180, 300, 600, 900, 1200, 1800,
    3600, 7200, 10800, 14400, 18000, 21600, 43200, 86400, 129600, 172800, 259200
];

/* ── The window ──────────────────────────────────────────────────────────── */

// The time window of one widget, in seconds. The visualisations shared a
// single view object because each one had a page to itself.
function ChartView(start, end){
    this.start = start;
    this.end = end;
    this.interval = 60;
    this.pan_speed = 0.2;
}

ChartView.prototype.length = function(){
    return this.end - this.start;
};

ChartView.prototype.zoom = function(by){
    var middle = this.start + this.length() / 2;
    var half = this.length() * by / 2;
    this.start = middle - half;
    this.end = middle + half;
};

ChartView.prototype.pan = function(direction){
    var shift = this.length() * this.pan_speed * direction;
    this.start += shift;
    this.end += shift;
};

// A window of so many days, ending now.
ChartView.prototype.days = function(days){
    this.end = Date.now() / 1000;
    this.start = this.end - (CHART_DAY * days);
};

// The step to read this window at, and the window snapped to it. npoints is
// how many datapoints the caller wants across the window.
ChartView.prototype.calc_interval = function(npoints, min_interval){
    var wanted = Math.round(this.length() / (npoints || 600));
    var interval = CHART_INTERVALS[0];
    for (var i = 0; i < CHART_INTERVALS.length; i++){
        if (wanted > CHART_INTERVALS[i]) interval = CHART_INTERVALS[i];
    }
    if (interval < (min_interval || 5)) interval = min_interval || 5;

    this.interval = interval;
    this.start = Math.floor(this.start / interval) * interval;
    this.end = Math.ceil(this.end / interval) * interval;
    return interval;
};

/* ── Feed data ───────────────────────────────────────────────────────────── */

// One feed over a window. The answer is [[seconds, value], ...], and the
// caller is given an AbortController to drop a request it no longer wants.
//
//   chart_feed_data({ id: 3, start: s, end: e, interval: "daily" })
//     .then(function(data){ ... })
function chart_feed_data(options){
    var params = "ids=" + encodeURIComponent(options.id)
    + "&start=" + Math.round(options.start * 1000)
    + "&end=" + Math.round(options.end * 1000)
    + "&interval=" + encodeURIComponent(options.interval)
    + "&skipmissing=" + (options.skipmissing ? 1 : 0)
    + "&limitinterval=" + (options.limitinterval ? 1 : 0)
    + "&delta=" + (options.delta ? 1 : 0)
    + "&timeformat=unix";
    if (typeof apikey === "string" && apikey) params += "&apikey=" + encodeURIComponent(apikey);

    var signal = options.signal;
    return window.fetch(path + "feed/data.json?" + params, { signal: signal })
    .then(function(response){
            if (!response.ok) throw new Error("HTTP " + response.status);
            return response.json();
        })
    .then(function(response){
            if (!Array.isArray(response)) {
                throw new Error(response && response.message ? response.message : _Tr("no data"));
            }
            for (var i = 0; i < response.length; i++){
                if (response[i] && String(response[i].feedid) === String(options.id)) {
                    return Array.isArray(response[i].data) ? response[i].data : [];
                }
            }
            return [];
        });
}

// One read of a chart's feeds. options is what chart_feed_data takes, or an
// array of them read together, and done is given the answer, or the array
// of answers in the same order. The AbortController is held on the chart
// under slot, request when none is named, and a read already in that slot is
// dropped. An answer that arrives after a later read was started is ignored.
// An error is written into the box, prefixed with chart.name.
function chart_fetch(chart, options, done, slot){
    slot = slot || "request";
    chart_abort(chart, slot);
    var controller = new AbortController();
    chart[slot] = controller;
    chart.read = Date.now();

    var ask = function(one){
        return chart.ctx.history($.extend({ signal: controller.signal }, one));
    };
    var reading = Array.isArray(options) ? Promise.all(options.map(ask)) : ask(options);

    reading.then(function(answer){
            if (chart[slot] !== controller) return;
            chart[slot] = null;
            done(answer);
        }).catch(function(err){
            if (err && err.name === "AbortError") return;
            if (chart[slot] === controller) chart[slot] = null;
            chart_message(chart.element, chart.name + ": " + (err && err.message ? err.message : err));
        });
}

// Drops the read held in a slot, if any.
function chart_abort(chart, slot){
    slot = slot || "request";
    if (chart[slot]) chart[slot].abort();
    chart[slot] = null;
}

// Called on the dashboard cycle. Reads again through fetch once seconds have
// passed since the last read, and not while a read is in flight or before the
// chart has a frame.
function chart_refresh(chart, seconds, fetch){
    if (!chart.plotel || chart.request) return;
    if (Date.now() - chart.read < seconds * 1000) return;
    fetch(chart);
}

// Takes a chart down: the read in flight, the plot and the box contents.
function chart_destroy(chart){
    chart_abort(chart, "request");
    if (chart.plot && typeof chart.plot.shutdown === "function") chart.plot.shutdown();
    chart.plot = null;
    if (chart.element) chart.element.innerHTML = "";
}

// Mean and energy of a window of power readings, as vis.helper.js worked
// them out. Times in seconds, so kwh is the mean over the time the readings
// span.
function chart_stats(data){
    var sum = 0, n = 0;
    var first = null, last = 0;

    for (var i = 0; i < data.length; i++){
        var value = data[i][1];
        if (value === null || !isFinite(value)) continue;
        sum += value;
        n++;
        if (first === null) first = data[i][0];
        last = data[i][0];
    }

    var mean = n ? sum / n : 0;
    var elapsed = first === null ? 0 : last - first;
    return { mean: mean, kwh: (mean * elapsed) / 3600000 };
}

/* ── Days, months and years ──────────────────────────────────────────────── */

// The readings that fall in a window, both bounds in seconds.
function chart_range(data, start, end){
    var kept = [];
    for (var i = 0; i < data.length; i++){
        if (data[i][0] >= start && data[i][0] < end) kept.push(data[i]);
    }
    return kept;
}

// Days of one month.
//
// Month boundaries are local, not UTC. The feed is read at a daily interval in
// the browser timezone, and the chart is drawn with timezone browser, so a
// month that began at UTC midnight sat an hour or two off its own bars for
// anybody not on UTC. daysmonthsyears.js used Date.UTC throughout.
function chart_days_month(data, month, year){
    return chart_range(data,
        new Date(year, month, 1).getTime() / 1000,
        new Date(year, month + 1, 1).getTime() / 1000);
}

// Daily readings summed into months, with how many days went into each. The
// days are what an average per day is worked out from.
function chart_months(data){
    return chart_group(data, function(date){
            return new Date(date.getFullYear(), date.getMonth(), 1).getTime() / 1000;
        });
}

// Daily readings summed into years.
function chart_years(data){
    return chart_group(data, function(date){
            return new Date(date.getFullYear(), 0, 1).getTime() / 1000;
        });
}

// One reading per period, summed, in the order the periods are in the data.
// A period with no reading in it is not in the answer, which is what the
// bargraph draws and how daysmonthsyears.js behaved.
function chart_group(data, periodof){
    var out = { data: [], days: [] };
    var current = null, sum = 0, days = 0;

    var close = function(){
        if (current === null) return;
        out.data.push([current, sum]);
        out.days.push(days);
    };

    for (var i = 0; i < data.length; i++){
        var period = periodof(new Date(data[i][0] * 1000));
        if (period !== current) {
            close();
            current = period;
            sum = 0;
            days = 0;
        }
        if (data[i][1] !== null && isFinite(data[i][1])) {
            sum += parseFloat(data[i][1]);
            days++;
        }
    }
    close();

    return out;
}

/* ── Formatting ──────────────────────────────────────────────────────────── */

var CHART_MONTHS = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
var CHART_DAYS = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];

function chart_pad(n){ return (n < 10 ? "0" : "") + n; }

// A time in seconds, in one of the shapes the visualisations asked
// date.format.min.js for. Written out rather than carrying that library over.
function chart_date(seconds, format){
    var d = new Date(seconds * 1000);
    switch (format) {
        case "year":      return String(d.getFullYear());
        case "month":     return CHART_MONTHS[d.getMonth()] + " " + d.getFullYear();
        case "day":       return chart_pad(d.getDate()) + " " + CHART_MONTHS[d.getMonth()] + " " + d.getFullYear();
        default:          return CHART_DAYS[d.getDay()] + ", " + CHART_MONTHS[d.getMonth()] + " " + d.getDate()
            + ", " + chart_pad(d.getHours()) + ":" + chart_pad(d.getMinutes());
    }
}

/* ── The box ─────────────────────────────────────────────────────────────── */

// The chart fills the box and a bar of buttons sits over the top right corner
// of it, out of sight until the pointer is over the box.
//
// Everything goes inside a frame that fills the box rather than on the box
// itself. The class attribute of a box is its widget type, and the designer
// reads it as such, so a second class on it is a widget type nothing declares.
//
// Returns the element to plot into, the bar to put buttons in, and the frame
// for anything else drawn over the chart.
function chart_frame(element){
    var frame = $('<div class="chart-widget"></div>');
    var plot = $('<div class="chart-plot"></div>');
    var bar = $('<div class="chart-bar"></div>');
    frame.append(plot).append(bar);
    $(element).empty().append(frame);
    return { plot: plot.get(0), bar: bar, frame: frame };
}

// A button in a bar, or in a group inside one.
function chart_button(group, label, title, handler){
    var button = $('<button type="button" class="btn btn-small"></button>')
    .attr("title", title || label).html(label);
    button.click(handler);
    group.append(button);
    return button;
}

function chart_group_of(bar){
    var group = $('<div class="btn-group"></div>');
    bar.append(group);
    return group;
}

// The bar and the readouts sit inside the plot area rather than in the corners
// of the box, so they keep off the border and the axis labels. Where the plot
// area starts is only known once the chart has been drawn, and it moves when
// the labels change width, so it is read again after each draw.
var CHART_BAR_INSET = 5;

function chart_place(plot, frame){
    if (!plot || !frame || typeof plot.getPlotOffset !== "function") return;
    var offset = plot.getPlotOffset();
    var inset = CHART_BAR_INSET;

    frame.find(".chart-bar").css({
            top: (offset.top + inset) + "px", right: (offset.right + inset) + "px"
        });
    frame.find(".chart-readout-top").css({
            top: (offset.top + inset) + "px", left: (offset.left + inset) + "px"
        });
    frame.find(".chart-readout-bottom").css({
            bottom: (offset.bottom + inset) + "px", left: (offset.left + inset) + "px"
        });
}

// How wide a bar is drawn, as a share of the step between one and the next.
// Flot 5 reads barWidth that way, where flot 0.8 took it in axis units, so the
// visualisations gave it a number of seconds. The graph engine uses the same
// 0.8 for every mode it draws bars in.
var CHART_BAR_WIDTH = 0.8;

// Whether a chart can be drawn, and the options every chart widget draws
// with: no shadow, no legend, and the axis colour on the ticks, grid and
// border. Null when the box has no frame yet, has no size, or flot is not
// loaded. A widget extends these with its own.
function chart_plot_options(chart){
    if (!chart.plotel) return null;
    if (typeof Flot !== "object" || typeof Flot.plot !== "function") return null;
    if (!chart.plotel.clientWidth || !chart.plotel.clientHeight) return null;

    var axis = chart_axis_options(chart.element);
    return {
        series: { shadowSize: 0 },
        xaxis: { font: axis.font },
        yaxis: { font: axis.font },
        grid: { borderWidth: 1, borderColor: axis.border, color: axis.color },
        legend: { show: false }
    };
}

// A readout over the chart, which is where these drew their totals and the
// value under the pointer. Written to with .text, so a feed name or a currency
// an author typed never reaches the page as markup.
//
// frame is what chart_frame returned, and where is "top" or "bottom", the two
// places they used.
// The bottom one is a panel with a handle at its left edge. A press on the
// handle slides the text away to the left, leaving the handle, and another
// slides it back. What is returned is the element the text goes in.
function chart_readout(frame, where){
    if (where !== "bottom") {
        var readout = $('<div class="chart-readout chart-readout-top"></div>');
        frame.append(readout);
        return readout;
    }

    var panel = $('<div class="chart-readout chart-readout-bottom"></div>');
    var toggle = $('<button type="button" class="chart-readout-toggle"></button>')
    .attr("title", _Tr("Hide"));
    var text = $('<span class="chart-readout-text"></span>');
    panel.append(toggle).append(text);
    frame.append(panel);

    toggle.click(function(){
            var hidden = panel.toggleClass("chart-readout-hidden").hasClass("chart-readout-hidden");
            toggle.attr("title", hidden ? _Tr("Show") : _Tr("Hide"));
        });
    return text;
}

// The box on its own, filling the screen.
function chart_fullscreen(element){
    if (document.fullscreenElement === element) {
        if (document.exitFullscreen) document.exitFullscreen();
        return;
    }
    if (!element.requestFullscreen) return;
    var request = element.requestFullscreen();
    // A browser refuses full screen when the click did not reach it as a
    // gesture. An unanswered rejection is logged as an error by itself.
    if (request && typeof request.catch === "function") request.catch(function(){});
}

/* ── The tooltip ─────────────────────────────────────────────────────────── */

// One tooltip is on the page at a time, whichever chart drew it, so moving the
// pointer from one chart to another takes the first one with it.
var chart_tooltip_element = null;

function chart_tooltip(pageX, pageY, lines){
    chart_tooltip_hide();

    var box = document.createElement("div");
    box.className = "chart-tooltip";
    for (var i = 0; i < lines.length; i++){
        var line = document.createElement("div");
        line.textContent = lines[i];
        box.appendChild(line);
    }
    document.body.appendChild(box);
    chart_tooltip_element = box;

    var offset = 15;
    box.style.top = Math.max(0, pageY - box.offsetHeight - offset) + "px";
    box.style.left = Math.max(0, Math.min(pageX + offset,
            document.documentElement.clientWidth - box.offsetWidth)) + "px";
    box.style.visibility = "visible";
}

function chart_tooltip_hide(){
    if (chart_tooltip_element && chart_tooltip_element.parentNode) {
        chart_tooltip_element.parentNode.removeChild(chart_tooltip_element);
    }
    chart_tooltip_element = null;
}

/* ── Odds and ends ───────────────────────────────────────────────────────── */

// Text in the box, for an empty option or a failed request.
function chart_message(element, message){
    $(element).empty().append($('<div class="chart-message"></div>').text(message));
}

// The item and the position a flot 5 event carries. The library reports these
// as a dom CustomEvent whose detail is the arguments the jQuery version passed.
function chart_event_item(event){
    return event.detail ? event.detail[1] : null;
}

function chart_event_ranges(event){
    return event.detail ? event.detail[0] : null;
}
