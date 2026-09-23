/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    Author: Trystan Lea: trystan.lea@gmail.com
    Fix for async non blocking and enhancements done by: nchaveiro@gmail.com
    Enhancements done by: Andreas Messerli firefox7518@gmail.com
    kWhPeriod widget implemented by: Daniel Bates dgbates@mail.uk
    If you have any questions please get in touch, try the forums here:
    http://openenergymonitor.org/emon/forum
 */

const msToDayConversion = 1000*60*60*24;

function kwhperiod_widgetlist () {
    var widgets = {
        kwhperiod: {
            offsetx: -40,
            offsety: -30,
            width: 120,
            height: 60,
            menu: "Widgets",
            options: [],
            optionstype: [],
            optionsname: [],
            optionshint: [],
            optionsdata: []
        }
    };

    var lastYearDropBoxOptions = [
        // Options for the type combobox. Each item is [typeID, "description"]
        [0, _Tr("False")],
        [1, _Tr("True")]
    ];

    var kwhperdayDropBoxOptions = [
        // Options for the type combobox. Each item is [typeID, "description"]
        [0, _Tr("False")],
        [1, _Tr("True")]
    ];

    var quantisationDropBoxOptions = [
        // Options for the type combobox. Each item is [typeID, "description"]
        [1, _Tr("True")],
        [0, _Tr("False")]
    ];

    var periodDropBoxOptions = [
        // Options for the type combobox. Each item is [typeID, "description"]
        [0, "Hour (usage in hour just been)"],
        [1, "Day (since midnight)"],
        [2, "Week (7 days exactly"],
        [3, "Month (Calendar month, average/day)"],
        [4, "Year (usage in one year exactly)"]
    ];

    addOption(
        widgets["kwhperiod"],
        "feedid",
        "feedid",
        _Tr("Feed"),
        _Tr("Feed value"),
        []
    );
    addOption(
        widgets["kwhperiod"],
        "periodLength",
        "dropbox",
        _Tr("Period length"),
        _Tr("Period length"),
        periodDropBoxOptions
    );
    addOption(
        widgets["kwhperiod"],
        "periodMultiplier",
        "value",
        _Tr("Period multiplier"),
        _Tr(
            "Number of Periods, this defines the width of the time window. 0 and 1 are the same, both default to the length of period."
        ),
        []
    );
    addOption(
        widgets["kwhperiod"],
        "periodsAgo",
        "value",
        _Tr("Periods ago"),
        _Tr(
            "Periods ago, for past periods. Shifts time window into the past by units of hour/day/month etc.. Set to Zero for current period only (i.e. today). Hour is reset on the hour, day reset at midnight, week reset on Monday, month on the 1st of the month, Year from the 1st January."
        ),
        []
    );
    addOption(
        widgets["kwhperiod"],
        "periodOffset",
        "value",
        _Tr("Period offset"),
        _Tr(
            "Period start offset in seconds, positive number shifts time forwards."
        ),
        []
    );
    addOption(
        widgets["kwhperiod"],
        "periodQuantise",
        "dropbox",
        _Tr("Period quantise"),
        _Tr(
            "True sets the time window according to the full period length, i.e. the beginning of the day i.e. hour starts from x:00 minutes, day from 00:00 hours, week from Monday 00:00, Month from 1st of the Month 00:00, and Year from 1st of January 00:00. Similarly, end will be on the last second of the period at 23:59:59. No quantisation will result in a time window with a start point in the past strictly as units of the period length, i.e today at 13:30 -> yesterday at 13:30."
        ),
        quantisationDropBoxOptions
    );
    addOption(
        widgets["kwhperiod"],
        "kwhPerDayConversion",
        "dropbox",
        _Tr("Period kWh/day"),
        _Tr("Convert the periodic energy use to kWh per Day"),
        kwhperdayDropBoxOptions
    );
    addOption(
        widgets["kwhperiod"],
        "useLastYear",
        "dropbox",
        _Tr("Annual data"),
        _Tr("Set to True to use last year's data"),
        lastYearDropBoxOptions
    );
    addOption(
        widgets["kwhperiod"],
        "prepend",
        "value",
        _Tr("Prepend Text"),
        _Tr("Prepend Text"),
        []
    );
    addOption(
        widgets["kwhperiod"],
        "append",
        "value",
        _Tr("Append Text"),
        _Tr("Append Text (Units)"),
        []
    );
    addOption(
        widgets["kwhperiod"],
        "decimals",
        "dropbox",
        _Tr("Decimals"),
        _Tr("Decimals to show"),
        widget_decimals_options()
    );
    addOption(
        widgets["kwhperiod"],
        "colour",
        "colour_picker",
        _Tr("Colour"),
        _Tr("Colour used for display"),
        []
    );
    addOption(
        widgets["kwhperiod"],
        "font",
        "dropbox",
        _Tr("Font"),
        _Tr("Font used for display"),
        widget_font_options()
    );
    addOption(
        widgets["kwhperiod"],
        "fstyle",
        "dropbox",
        _Tr("Font style"),
        _Tr("Font style used for display"),
        widget_style_options()
    );
    addOption(
        widgets["kwhperiod"],
        "fweight",
        "dropbox",
        _Tr("Font weight"),
        _Tr("Font weight used for display"),
        widget_weight_options()
    );
    addOption(
        widgets["kwhperiod"],
        "size",
        "dropbox",
        _Tr("Size"),
        _Tr("Text size in px to use"),
        widget_size_options()
    );
    addOption(
        widgets["kwhperiod"],
        "align",
        "dropbox",
        _Tr("Alignment"),
        _Tr("Alignment"),
        widget_align_options()
    );
    addOption(
        widgets["kwhperiod"],
        "timeout",
        "value",
        _Tr("Timeout"),
        _Tr("Timeout without feed update in seconds (empty is never)"),
        []
    );
    addOption(
        widgets["kwhperiod"],
        "errormessagedisplayed",
        "value",
        _Tr("Error Message"),
        _Tr("Error message displayed when timeout is reached"),
        []
    );

    return widgets;
}

function draw_kwhperiod (
    el,
    config,
    prepend,
    val,
    append,
    errorCode,
    errorMessage
) {
    var font = widget_font(config.font || "5", config.fstyle || "2", config.fweight || "1", config.size || "8");

    el.style.color = widget_colour(config.colour, "4444CC");
    el.style.font = font.css;
    el.style.textAlign = config.align || "center";
    el.style.lineHeight = el.clientHeight + "px";

    if (errorCode === "1") {
        el.textContent = errorMessage;
    } else {
        // Text, not html. prepend, append and units are free text an author
        // types, see the option values section of tools/SCHEMA.md.
        el.textContent = prepend + widget_decimals(val, config.decimals) + append;
    }
}

// Time window of the period the widget shows, in local time. thisPeriodStart
// is the start of the current period, pastPeriodStart and pastPeriodEnd the
// window shifted back by periodsAgo and, with useLastYear, one year.
function kwhperiod_period (config, now) {
    var periodLength = config.periodlength * 1;
    if (isNaN(periodLength)) periodLength = 1;

    var periodMultiplier = config.periodmultiplier * 1;
    if (periodMultiplier === 0) periodMultiplier = 1;
    if (isNaN(periodMultiplier)) periodMultiplier = 1;

    var periodsAgo = config.periodsago * 1;
    if (isNaN(periodsAgo)) periodsAgo = 0;

    var periodOffset = config.periodoffset * 1;
    if (isNaN(periodOffset)) periodOffset = 0;

    var periodQuantise = config.periodquantise * 1;
    if (isNaN(periodQuantise)) periodQuantise = 1;

    var kwhPerDayConversion = config.kwhperdayconversion * 1;
    if (isNaN(kwhPerDayConversion)) kwhPerDayConversion = 0;

    var useLastYear = config.uselastyear * 1;
    if (isNaN(useLastYear)) useLastYear = 0;

    //------------------------------------------
    // Find period times in milliseconds.
    //------------------------------------------
    var pastPeriodStartTime;
    var pastPeriodEndTime;
    var thisPeriodStartTime;
    var day;

    if (periodLength === 0) {
        // hour
        if (periodQuantise) {
            thisPeriodStartTime = new Date(now);
            thisPeriodStartTime.setMinutes(0, 0, 0); // reset minutes and seconds of current hour
        } else {
            thisPeriodStartTime = new Date(now);
            thisPeriodStartTime.setHours(thisPeriodStartTime.getHours() - 1); // go back one hour exactly
        }

        thisPeriodStartTime.setHours(thisPeriodStartTime.getHours() - (periodMultiplier - 1)); // apply multiplier
        thisPeriodStartTime.setSeconds(thisPeriodStartTime.getSeconds() + periodOffset); // apply offset

        pastPeriodStartTime = new Date(thisPeriodStartTime);
        pastPeriodStartTime.setHours(pastPeriodStartTime.getHours() - periodsAgo);
        if (useLastYear)
        pastPeriodStartTime.setFullYear(pastPeriodStartTime.getFullYear() - 1);

        pastPeriodEndTime = new Date(pastPeriodStartTime);
        pastPeriodEndTime.setHours(pastPeriodEndTime.getHours() + periodMultiplier);
    }
    else if (periodLength === 1) {
        // day
        if (periodQuantise) {
            thisPeriodStartTime = new Date(now);
            thisPeriodStartTime.setHours(0, 0, 0, 0);
        } else {
            thisPeriodStartTime = new Date(now);
            thisPeriodStartTime.setDate(thisPeriodStartTime.getDate() - 1);
        }

        thisPeriodStartTime.setDate(thisPeriodStartTime.getDate() - (periodMultiplier - 1)); // apply multiplier
        thisPeriodStartTime.setSeconds(thisPeriodStartTime.getSeconds() + periodOffset); // apply offset

        pastPeriodStartTime = new Date(thisPeriodStartTime);
        pastPeriodStartTime.setDate(pastPeriodStartTime.getDate() - periodsAgo);
        if (useLastYear) {
            pastPeriodStartTime.setFullYear(pastPeriodStartTime.getFullYear() - 1);
        }

        pastPeriodEndTime = new Date(pastPeriodStartTime);
        pastPeriodEndTime.setDate(pastPeriodStartTime.getDate() + periodMultiplier);
    }
    else if (periodLength === 2) {
        // week
        if (periodQuantise) {
            thisPeriodStartTime = new Date(now);
            // https://stackoverflow.com/a/4156562
            day = now.getDay() || 7; // day is equal to 1 on a Monday, Sunday is converted to a 7.
            if (day !== 1) {
                // only if not Monday
                thisPeriodStartTime.setHours((day - 1) * -24); // Set to Monday
            }
            thisPeriodStartTime.setHours(0, 0, 0, 0); // Set to midnight
        } else {
            thisPeriodStartTime = new Date(now);
            thisPeriodStartTime.setHours(now.getHours() - 7 * 24);
        }

        thisPeriodStartTime.setDate(thisPeriodStartTime.getDate() - (periodMultiplier - 1) * 7); // apply multiplier
        thisPeriodStartTime.setSeconds(thisPeriodStartTime.getSeconds() + periodOffset); // apply offset

        pastPeriodStartTime = new Date(thisPeriodStartTime);
        pastPeriodStartTime.setDate(thisPeriodStartTime.getDate() - periodsAgo * 7);
        if (useLastYear)
        pastPeriodStartTime.setFullYear(pastPeriodStartTime.getFullYear() - 1);
        pastPeriodEndTime = new Date(pastPeriodStartTime);
        pastPeriodEndTime.setDate(pastPeriodStartTime.getDate() + periodMultiplier * 7);
    }
    else if (periodLength === 3) {
        // month, calendar month (30 days worth implementing?)
        if (periodQuantise) {
            thisPeriodStartTime = new Date(now);
            thisPeriodStartTime.setDate(1); //
            thisPeriodStartTime.setHours(0, 0, 0, 0);
        } else {
            thisPeriodStartTime = new Date(now);
            thisPeriodStartTime.setMonth(now.getMonth() - 1);
        }

        thisPeriodStartTime.setMonth(thisPeriodStartTime.getMonth() - (periodMultiplier - 1)); // apply multiplier
        thisPeriodStartTime.setSeconds(thisPeriodStartTime.getSeconds() + periodOffset); // apply offset

        pastPeriodStartTime = new Date(thisPeriodStartTime);
        pastPeriodStartTime.setMonth(thisPeriodStartTime.getMonth() - periodsAgo);
        if (useLastYear)
        pastPeriodStartTime.setFullYear(pastPeriodStartTime.getFullYear() - 1);

        pastPeriodEndTime = new Date(pastPeriodStartTime);
        pastPeriodEndTime.setMonth(pastPeriodStartTime.getMonth() + periodMultiplier);
    }
    else if (periodLength === 4) {
        // year
        if (periodQuantise) {
            thisPeriodStartTime = new Date(now);
            thisPeriodStartTime.setMonth(0, 1);
            thisPeriodStartTime.setHours(0, 0, 0, 0);
        } else {
            thisPeriodStartTime = new Date(now);
            thisPeriodStartTime.setFullYear(now.getFullYear() - 1);
        }

        thisPeriodStartTime.setFullYear(thisPeriodStartTime.getFullYear() - (periodMultiplier - 1)); // apply multiplier
        thisPeriodStartTime.setSeconds(thisPeriodStartTime.getSeconds() + periodOffset); // apply offset

        pastPeriodStartTime = new Date(thisPeriodStartTime);
        pastPeriodStartTime.setFullYear(
            thisPeriodStartTime.getFullYear() - periodsAgo
        );
        if (useLastYear)
        pastPeriodStartTime.setFullYear(pastPeriodStartTime.getFullYear() - 1);

        pastPeriodEndTime = new Date(pastPeriodStartTime);
        pastPeriodEndTime.setFullYear(pastPeriodStartTime.getFullYear() + periodMultiplier);
    }
    if (periodQuantise) {
        // Subtract one second from pastPeriodEndTime to make full 00:00:00 to 23:59:59 of the same periodLength
        pastPeriodEndTime.setSeconds(pastPeriodEndTime.getSeconds() - 1);
    }

    return {
        thisPeriodStartTime: thisPeriodStartTime,
        pastPeriodStartTime: pastPeriodStartTime,
        pastPeriodEndTime: pastPeriodEndTime,
        periodsAgo: periodsAgo,
        useLastYear: useLastYear,
        kwhPerDayConversion: kwhPerDayConversion
    };
}

// Energy used over the window, read through feed.getvalue, which gives a
// cumulative feed's value at a point in time. Resolves to a number, or to
// null when the poll has not yet given the feed's live value.
async function kwhperiod_value (config, feeds, now) {
    var period = kwhperiod_period(config, now);
    var feedid = feeds.id(config.feedid);
    var val;

    if (period.periodsAgo > 0 || period.useLastYear) {
        var pastPeriodEndValue = await feed.getvalue(feedid, period.pastPeriodEndTime.getTime()*0.001, function(data){ return data;} );
        var pastPeriodStartValue = await feed.getvalue(feedid, period.pastPeriodStartTime.getTime()*0.001, function(data){ return data;} );

        // Calculate the result of our time window.
        if (pastPeriodStartValue === null || pastPeriodEndValue === null) {
            val = 0;
        } else {
            val = pastPeriodEndValue - pastPeriodStartValue;
        }
        if (period.kwhPerDayConversion) {
            var periodMillis = period.pastPeriodEndTime.getTime() - period.pastPeriodStartTime.getTime();
            val = val / (periodMillis/msToDayConversion); // kWh over the window to kWh per day
        }
    } else {
        var live = feeds.get(config.feedid);
        if (!live) return null;
        val = live["value"] * 1;
        var thisPeriodStartValue = await feed.getvalue(feedid, period.thisPeriodStartTime.getTime()*0.001, function(data){ return data;} );
        if (thisPeriodStartValue === null) {
            val = 0;
        }
        else {
            val -= thisPeriodStartValue;
        }
        if (period.kwhPerDayConversion) {
            var periodMillis = now.getTime() - period.thisPeriodStartTime.getTime();
            val = val / (periodMillis/msToDayConversion); // kWh over the window to kWh per day
        }
    }
    return val;
}

// Text, prepend and append from the options, with the units and unitend of
// dashboards saved before prepend and append existed.
function kwhperiod_affixes (config) {
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
    return { prepend: prepend, append: append };
}

var kwhperiod_widget = {
    mount: function (el, config, ctx) {
        el.innerHTML = "";
        var affixes = kwhperiod_affixes(config);
        var feeds = ctx.feeds;

        // Last value read, undefined until the first read.
        var result;
        // How often the value is read again, in ms. A tick every 2 s checks it.
        var refreshPeriod = 10000;
        var previousRefresh;
        var refreshing = false;
        var alive = true;

        var draw = function () {
            // Timeout is measured from the feed's last update, not from the read.
            var timeout = widget_timeout(config, feeds.get(config.feedid));
            var errorCode = timeout.code;
            var errorMessage = config.errormessagedisplayed || "Feed Timeout";

            var val;
            if (result === undefined) {
                // not loaded yet
                errorCode = "1";
                errorMessage = "..."; // loading
            } else {
                val = result["value"] * 1;
            }
            if (val === undefined || isNaN(val)) {
                val = 0;
            }

            draw_kwhperiod(el, config, affixes.prepend, val, affixes.append, errorCode, errorMessage);
        };

        // Reads the value again once refreshPeriod has passed since the last
        // read. One read at a time, and a read that ends after destroy draws
        // nothing.
        var refresh = function () {
            if (refreshing) return;
            var now = new Date();
            if (previousRefresh !== undefined && now - previousRefresh < refreshPeriod) return;
            refreshing = true;
            kwhperiod_value(config, feeds, now).then(function (val) {
                    refreshing = false;
                    if (!alive) return;
                    // No live value yet, try again on the next tick.
                    if (val === null) return;
                    previousRefresh = now.getTime();
                    result = { value: val };
                    draw();
                }).catch(function (error) {
                    refreshing = false;
                    if (!alive) return;
                    previousRefresh = now.getTime();
                    console.error("Error occurred kwhperiod:", error);
                });
        };

        refresh();
        var timer = setInterval(refresh, 2000);

        return {
            update: function (live) { feeds = live; draw(); refresh(); },
            resize: function () { draw(); },
            destroy: function () {
                alive = false;
                clearInterval(timer);
            }
        };
    }
};
