/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:  http://openenergymonitor.org

  What the data widgets share: the option lists their widget lists offer, and
  the font, number, colour and timeout handling their drawing does. Loaded
  before the widgets by both dashboard views. Nothing here touches the page.
*/

/* ── Option lists ────────────────────────────────────────────────────── */

// Font names by the number the font option stores. Two tables exist because
// battery, signal and sun stored the shorter one before the rest were
// written, and the stored numbers cannot change.
var WIDGET_FONTS = ["Impact", "Georgia", "Arial", "Courier New", "Comic Sans MS",
    "Helvetica", "Helvetica Neue", "sans-serif", "Arial Narrow", "Arial Black"];
var WIDGET_FONTS_SHORT = ["Impact", "Georgia", "Arial", "Courier New", "Comic Sans MS",
    "Helvetica", "sans-serif", "Arial Narrow", "Arial Black"];

// Text size in px by the number the size option stores. 14 is 18px again, and
// is listed first so a new widget opens on it rather than on 40px.
var WIDGET_SIZES = [6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 28, 32, 36, 40, 18];

// [value, label] pairs for a dropbox, largest first as the widgets have
// always offered them.
function widget_font_options(short){
    var names = short ? WIDGET_FONTS_SHORT : WIDGET_FONTS;
    var options = [];
    for (var i = names.length - 1; i >= 0; i--) options.push([i, names[i]]);
    return options;
}

function widget_style_options(){
    return [[2, _Tr("Normal")], [1, _Tr("Italic")], [0, _Tr("Oblique")]];
}

function widget_weight_options(){
    return [[1, _Tr("Bold")], [0, _Tr("Normal")]];
}

function widget_size_options(){
    var options = [[14, "18"]];
    for (var i = 13; i >= 0; i--) options.push([i, String(WIDGET_SIZES[i])]);
    return options;
}

function widget_align_options(){
    return [["center", _Tr("Center")], ["left", _Tr("Left")], ["right", _Tr("Right")]];
}

function widget_decimals_options(){
    return [[-1, _Tr("Automatic")], [0, "0"], [1, "1"], [2, "2"], [3, "3"],
        [4, "4"], [5, "5"], [6, "6"]];
}

function widget_unitend_options(){
    return [[0, _Tr("Back")], [1, _Tr("Front")]];
}

// No first, so a new widget opens with the feature off. Pass true for a
// list that opens on Yes.
function widget_yesno_options(yesfirst){
    var options = [[0, _Tr("No")], [1, _Tr("Yes")]];
    return yesfirst ? options.reverse() : options;
}

/* ── Tooltips ────────────────────────────────────────────────────────── */

// Tooltips beside a canvas, one per hotspot the draw returns. A hotspot is
// {x, y, w, h, tip} in canvas pixels. Divs are given ids when canvasid is
// passed, as stored dashboards and tools/roundtrip.php know them. Returns
// set(hotspots), called after each draw, and destroy().
function widget_tooltips(el, canvas, count, canvasid){
    var tips = [];
    for (var n = 1; n <= count; n++){
        var div = document.createElement("div");
        if (canvasid) div.id = canvasid + "-tooltip-" + n;
        el.appendChild(div);
        tips.push(div);
    }
    var hotspots = [];

    var onmove = function(event){
        var rect = canvas.getBoundingClientRect();
        var x = event.clientX - rect.left;
        var y = event.clientY - rect.top;
        for (var i = 0; i < tips.length; i++){
            var tip = tips[i];
            var h = hotspots[i];
            if (h && x >= h.x && x < h.x + h.w && y >= h.y && y < h.y + h.h){
                tip.style.cssText = "position:fixed;background-color:#DDDDDD;opacity:0.8;border: 1px solid rgb(255, 221, 221);pointer-events:none;font-weight: bold; z-index: 100;";
                tip.style.left = event.clientX + 15 + "px";
                tip.style.top = event.clientY + 15 + "px";
                tip.style.visibility = "visible";
                tip.textContent = "\u00a0" + h.tip + "\u00a0";
            } else {
                tip.style.visibility = "hidden";
            }
        }
    };
    canvas.addEventListener("mousemove", onmove);

    return {
        set: function(list){ hotspots = list || []; },
        destroy: function(){ canvas.removeEventListener("mousemove", onmove); }
    };
}

/* ── Drawing ─────────────────────────────────────────────────────────── */

// The css font of a widget from its font, fstyle, fweight and size options.
// Each argument is the stored option value, or undefined. Returns the parts
// and the shorthand, with the defaults the widgets have always drawn with.
function widget_font(font, fstyle, fweight, size, short){
    var names = short ? WIDGET_FONTS_SHORT : WIDGET_FONTS;
    var name = names[parseInt(font, 10)];
    if (name === undefined) name = "Helvetica";

    var style = { "0": "oblique", "1": "italic", "2": "normal" }[fstyle];
    if (style === undefined) style = "normal";

    var weight = { "0": "normal", "1": "bold" }[fweight];
    if (weight === undefined) weight = "bold";

    var px = WIDGET_SIZES[parseInt(size, 10)];
    if (px === undefined) px = 22;

    return {
        name: name, style: style, weight: weight, size: px,
        css: style + " " + weight + " " + px + "px " + name
    };
}

// A value formatted to the decimals option. Below zero the number of places
// follows the size of the value, none from 100, one from 10 and two below
// that, with trailing zeros dropped. Unset is no places, which is what the
// widgets drew before the option existed. Returns a string.
function widget_decimals(val, decimals){
    val = parseFloat(val);
    if (!isFinite(val)) val = 0;
    var places = parseInt(decimals, 10);
    if (!isFinite(places)) places = 0;
    if (places >= 0) return val.toFixed(places);
    var size = Math.abs(val);
    var fixed = size >= 100 ? val.toFixed(0) : size >= 10 ? val.toFixed(1) : val.toFixed(2);
    return String(parseFloat(fixed));
}

// A colour option as css. Stored without the hash, which is put back, and
// the fallback is used when the option is unset.
function widget_colour(colour, fallback){
    if (colour === undefined || colour === null || colour === "") colour = fallback || "";
    colour = String(colour);
    if (colour === "" || colour === "none") return colour;
    return colour.charAt(0) === "#" ? colour : "#" + colour;
}

// Whether a feed has gone quiet for longer than the timeout option allows.
// Returns the error code the widgets test, "1" for a timeout, and the message
// to show in its place. No timeout, or no feed yet, is never an error.
function widget_timeout(config, feed){
    var message = config.errormessagedisplayed;
    if (message === undefined || message === "") message = "TO Error";

    var timeout = parseFloat(config.timeout);
    if (!isFinite(timeout) || timeout <= 0 || !feed) return { code: "0", message: message };

    var now = Date.now() / 1000;
    var offset = typeof offsetofTime === "number" ? offsetofTime : 0;
    var code = (now - offset - feed.time * 1) > timeout ? "1" : "0";
    return { code: code, message: message };
}
