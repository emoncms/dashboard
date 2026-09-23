/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
    Author: Trystan Lea: trystan.lea@googlemail.com
    If you have any questions please get in touch, try the forums here:
    http://openenergymonitor.org/emon/forum
 */

function dial_widgetlist(){
    var widgets =
    {
        "dial":
        {
            "offsetx":-80,"offsety":-80,"width":160,"height":160,
            "menu":"Widgets",
            "options":    [],
            "optionstype":[],
            "optionsname":[],
            "optionshint":[],
            "optionsdata":[]

        }
    };

    var typeDropBoxOptions = [        // Options for the type combobox. Each item is [typeID, "description"]
        [10,   _Tr("Black <-> White, Zero at left")],
        [1,    _Tr("Red <-> Green, Zero at center")],
        [2,    _Tr("Green <-> Red, Zero at left")],
        [3,    _Tr("Green <-> Red, Zero at center")],
        [4,    _Tr("Red <-> Green, Zero at left")],
        [5,    _Tr("Red <-> Green, Zero at center")],
        [6,    _Tr("Green center <-> orange edges, Zero at center")],
        [9,    _Tr("Red <-> Dark Red, Zero at left")],
        [11,   _Tr("Blue <-> Red, Zero at upper-left")],
        [8,    _Tr("Light blue <-> Red, Zero at mid-left")],
        [12,   _Tr("Light <-> dark red, Zero at left")],
        [13,   _Tr("Light <-> dark orange, Zero at left")],
        [14,   _Tr("Light <-> dark yellow, Zero at left")],
        [0,    _Tr("Light <-> dark green, Zero at left")],
        [18,   _Tr("Light <-> dark lime, Zero at left")],
        [19,   _Tr("Light <-> dark mint, Zero at left")],
        [15,   _Tr("Light <-> dark cyan, Zero at left")],
        [7,    _Tr("Light <-> dark blue, Zero at left")],
        [20,   _Tr("Light <-> dark royal blue, Zero at left")],
        [16,   _Tr("Light <-> dark purple, Zero at left")],
        [17,   _Tr("Light <-> dark pink, Zero at left")],
        [21,   _Tr("Rainbow!, Zero at left")],
        [22,   _Tr("Reverse Rainbow!, Zero at left")],
        [23,   _Tr("Light <-> dark grey, alternating, Zero at left")]
    ];

    addOption(widgets["dial"], "feedid",                "feedid",         _Tr("Feed"),          _Tr("Feed value"),                                                            []);
    addOption(widgets["dial"], "max",                   "value",          _Tr("Max value"),     _Tr("Max value to show"),                                                     []);
    addOption(widgets["dial"], "scale",                 "value",          _Tr("Scale"),         _Tr("Value is multiplied by scale before display"),                           []);
    addOption(widgets["dial"], "units",                 "dropbox_other",  _Tr("Units"),         _Tr("Units to show"),                                                         _SI);
    addOption(widgets["dial"], "decimals",              "dropbox",        _Tr("Decimals"),      _Tr("Decimals to show"),                                                      widget_decimals_options());
    addOption(widgets["dial"], "offset",                "value",          _Tr("Offset"),        _Tr("Static offset. Subtracted from value before computing needle position"), []);
    addOption(widgets["dial"], "type",                  "dropbox",        _Tr("Type"),          _Tr("Type to show"),                                                          typeDropBoxOptions);
    addOption(widgets["dial"], "graduations",           "dropbox",        _Tr("Graduations"),   _Tr("Should the graduation limits be shown"),                                 widget_yesno_options(true));
    addOption(widgets["dial"], "unitend",               "dropbox",        _Tr("Unit position"), _Tr("Where should the unit be shown"),                                        widget_unitend_options());
    addOption(widgets["dial"], "displayminmax",         "dropbox",        _Tr("Min / Max ?"),   _Tr("Display Min. and Max. ?"),                                               widget_yesno_options());
    addOption(widgets["dial"], "minvaluefeed",          "feedid",         _Tr("Min. feed"),     _Tr("The feed for the minimum value"),                                        []);
    addOption(widgets["dial"], "maxvaluefeed",          "feedid",         _Tr("Max. feed"),     _Tr("The feed for the maximum value"),                                        []);
    addOption(widgets["dial"], "timeout",               "value",          _Tr("Timeout"),       _Tr("Timeout without feed update in seconds (empty is never)"),               []);
    addOption(widgets["dial"], "errormessagedisplayed", "value",          _Tr("Error Message"), _Tr("Error message displayed when timeout is reached"),                       []);

    return widgets;
}

function deg_to_radians(deg){
    return deg * (Math.PI / 180);
}
function polar_to_cart(mag, ang, xOff, yOff){
    ang = deg_to_radians(ang);
    var x = mag * Math.cos(ang) + xOff;
    var y = mag * Math.sin(ang) + yOff;
    return [x, y];
}
function round1decimal(x) {
    return Math.round(x * 10) / 10;
}

// Draws the dial. config holds the widget settings. reading holds the scaled
// feed values as {value, min, max, displayminmax, errorCode, errorMessage}.
function draw_gauge(ctx, width, height, config, reading){
    if (!ctx) {return;}

    var maxvalue = 1 * config.max || 3000;
    var units = config.units || "";
    var decimals = config.decimals || "-1";
    var type = config.type || "0";
    var graduationBool = config.graduations;
    var unitend = config.unitend || "0";
    var displayminmax = reading.displayminmax || "0";
    var errorCode = reading.errorCode;
    var errorMessage = reading.errorMessage;
    var offset = 1*config.offset || 0;
    var val = reading.value;
    var position = reading.value-offset;
    var minvaluefeed = reading.min-offset;
    var maxvaluefeed = reading.max-offset;

    var size = 0;
    if (width<height) {size = width/2;}
    else {size = height/2;}
    if(displayminmax==="1"){size = size - (size*0.13/2);}
    else {size = size - (size*0.058/2);}

    // Centre of the canvas
    var x = width/2;
    var y = height/2;

    ctx.clearRect(0,0,width,height);

    if (!position) {position = 0;}

    var angleOffset = 0;
    var segment = ["#c0e392","#9dc965","#87c03f","#70ac21","#378d42","#046b34"];

    // Depending on type the start value is calculated.
    // The maximum value is defined via settings and is defined as the value to reach needle's end limit.
    // Depending on dial's type start limit is either 0 or a negative value.
    // Note: Should consider a type which enables defiable min value in futur extension.  

    if (type === "0"){ // Standard dial from 0 to maxvalue if offset is not set.
        // TODO: seperate between needle position at maximum/minimum and the value displayed.
        // TODO: Do we need to limit the value being displayed? Only needle position should be limited.
    }
    else if (type === "1"){
        angleOffset = -0.75;
        segment = ["#e61703","#ff6254","#ffa29a","#70ac21","#378d42","#046b34"];
    }
    else if (type === "2"){
        segment = ["#046b34","#378d42","#87c03f","#f8a01b","#f46722","#bf2025"];
    }
    else if (type === "3"){
        angleOffset = -0.75;
        segment = ["#046b34","#378d42","#87c03f","#f8a01b","#f46722","#bf2025"];
    }
    else if (type === "4"){
        segment = ["#bf2025","#f46722","#f8a01b","#87c03f","#378d42","#046b34"];
    }
    else if (type === "5"){
        angleOffset = -0.75;
        segment = ["#bf2025","#f46722","#f8a01b","#87c03f","#378d42","#046b34"];
    }
    else if (type === "6"){
        angleOffset = -0.75;
        segment = ["#f46722","#f8a01b","#87c03f","#87c03f","#f8a01b","#f46722"];
    }
    else if (type === "7"){
        segment = ["#a7cbe2","#68b7eb","#0d97f3","#0f81d0","#0c6dae","#08578e"];
    }
    else if (type === "8"){  //temperature dial blue-red, first segment blue should mean below freezing C
        angleOffset = -0.25;
        segment = ["#b7beff","#ffd9d9","#ffbebe","#ff9c9c","#ff6e6e","#ff3d3d"];
    }
    else if (type === "9"){  //temperature dial blue-red, first segment blue should mean below freezing C
        angleOffset = 0;
        segment = ["#e94937","#da4130","#c43626","#ad2b1c","#992113","#86170a"];
    }
    else if (type === "10"){ //light: from dark grey to white
        segment = ["#202020","#4D4D4D","#7D7D7D","#EEF0F3","#F7F7F7", "#FFFFFF"];
    }
    else if (type === "11"){  //temperature dial blue-red, first 2 segments blue should mean below freezing C
        angleOffset = -0.5;
        segment = ["#0d97f3","#a7cbe2","#ffbebe","#ff8383","#ff6464","#ff3d3d"];
    }
    else if (type === "12"){ //  - from light to dark red
        if (position<0){position = 0;}
        segment = ["#FFCCCC", "#FFA3A3", "#FF7A7A", "#FF5151", "#FF2828", "#FF0000"];
    }
    else if (type === "13"){ //  - from light to dark orange
        if (position<0){position = 0;}
        segment = ["#FFE5CC", "#FFD0A3", "#FFBC7A", "#FFA851", "#FF9428", "#FF8000"];
    }
    else if (type === "14"){ //  - from light to dark yellow
        if (position<0){position = 0;}
        segment = ["#FFFFCC", "#FFFFA3", "#FFFF7A", "#FFFF51", "#FFFF28", "#FFFF00"];
    }
    else if (type === "15"){ //  - from light to dark cyan
        if (position<0){position = 0;}
        segment = ["#CCFFFF", "#A3FFFF", "#7AFFFF", "#51FFFF", "#28FFFF", "#00FFFF"];
    }
    else if (type === "16"){ //  - from light to dark purple
        if (position<0){position = 0;}
        segment = ["#E5CCFF", "#D0A3FF", "#BC7AFF", "#A851FF", "#9428FF", "#8000FF"];
    }
    else if (type === "17"){ //  - from light to dark pink
        if (position<0){position = 0;}
        segment = ["#FFCCFF", "#FFA3FF", "#FF7AFF", "#FF51FF", "#FF28FF", "#FF00FF"];
    }
    else if (type === "18"){ //  - from light to dark lime
        if (position<0){position = 0;}
        segment = ["#CCFFCC", "#A3FFA3", "#7AFF7A", "#51FF51", "#28FF28", "#00FF00"];
    }
    else if (type === "19"){ //  - from light to dark mint
        if (position<0){position = 0;}
        segment = ["#EEFCF5", "#E0F9ED", "#D2F7E6", "#C5F4DF", "#B7F2D8", "#AAF0D1"];
    }
    else if (type === "20"){ //  - from light to dark royal blue
        if (position<0){position = 0;}
        segment = ["#CCCCFF", "#A3A3FF", "#7A7AFF", "#5151FF", "#2828FF", "#0000FF"];
    }
    else if (type === "21"){ //  - rainbow!
        if (position<0){position = 0;}
        segment = ["#FF0000", "#FF8000", "#FFFF00", "#00FF00", "#0000FF", "#8000FF"];
    }
    else if (type === "22"){ //  - reverse rainbow!
        if (position<0){position = 0;}
        segment = ["#8000FF", "#0000FF", "#00FF00", "#FFFF00", "#FF8000", "#FF0000"];
    }
    else if (type === "23")  // can be used to emulate a barometer and display a pressure
    {
        if (position<0){position = 0;}
        segment = ["#C0C0C0","#868686","#C0C0C0","#868686","#C0C0C0","#868686"];
    }

    // needle values and their corresponding direction
    // South West (limit start) a = 1.75
    // West: .. ............... a = 1.5
    // North West: ............ a = 1.25
    // North: ................. a = 1
    // North East: ............ a = 0.75
    // East: .................. a = 0.5
    // South East (limit stop)  a = 0.25
    //
    // Calculation explanation:
    // Total needle space = 1.75 - 0.25 = 1.5 (from above).
    // (1.5 + angleOffset) = needle space from 0 to maxvalue (because angleOffset is negative).
    // (position / maxvalue) = fraction of how far round the needle space from 0 to maxvalue we need to be.
    // The rest follows from these.
    var needle = 1.75 - ((position/maxvalue) * (1.5+angleOffset)) + angleOffset;
    needle = Math.min(needle, 1.75);
    needle = Math.max(needle, 0.25);

    var minindicator = 1.75 - ((minvaluefeed/maxvalue) * (1.5+angleOffset)) + angleOffset;
    minindicator = Math.min(minindicator, 1.75);
    minindicator = Math.max(minindicator, 0.25);

    var maxindicator = 1.75 - ((maxvaluefeed/maxvalue) * (1.5+angleOffset)) + angleOffset;
    maxindicator = Math.min(maxindicator, 1.75);
    maxindicator = Math.max(maxindicator, 0.25);

    var step = 0.785;
    var c=3*0.785;
    var pos = 0;
    var inner = size * 0.48;

    // Segments
    for (var z in segment){
        ctx.fillStyle = segment[z];
        ctx.beginPath();
        ctx.arc(x,y,size,c+pos,c+pos+step+0.01,false);
        ctx.lineTo(x,y);
        ctx.closePath();
        ctx.fill();
        pos += step;
    }
    ctx.lineWidth = (size*0.058).toFixed(0);
    ctx.strokeStyle = "#fff";
    ctx.beginPath();
    ctx.arc(x,y,size,c,c+pos,false);
    ctx.lineTo(x,y);
    ctx.closePath();
    ctx.stroke();

    ctx.fillStyle = "#666867";
    ctx.beginPath();
    ctx.arc(x,y,inner,0,Math.PI*2,true);
    ctx.closePath();
    ctx.fill();

    ctx.lineWidth = (size*0.052).toFixed(0);
    //---------------------------------------------------------------
    if (errorCode != "1"){
        ctx.beginPath();
        ctx.moveTo(x+Math.sin(Math.PI*needle-0.2)*inner,y+Math.cos(Math.PI*needle-0.2)*inner);
        ctx.lineTo(x+Math.sin(Math.PI*needle)*size,y+Math.cos(Math.PI*needle)*size);
        ctx.lineTo(x+Math.sin(Math.PI*needle+0.2)*inner,y+Math.cos(Math.PI*needle+0.2)*inner);
        ctx.arc(x,y,inner,1-(Math.PI*needle-0.2),1-(Math.PI*needle+5.4),true);
        ctx.closePath();
        ctx.fill();
        ctx.stroke();
    }

    val = widget_decimals(val, decimals);

    var dialtext;
    if (errorCode == "1") {
        dialtext = errorMessage;
    } else {
        if (unitend ==="0"){
            dialtext=val+units;}
        if (unitend ==="1"){
            dialtext=units+val;}
    }

    var textsize = (size / (dialtext.length+2)) * 6;

    ctx.fillStyle = "#fff";
    ctx.textAlign = "center";
    ctx.font = "bold "+(textsize*0.26)+"px arial";
    ctx.fillText(dialtext,x,y+(textsize*0.125));
    ctx.fillStyle = "#000";
    var spreadAngle = 32;

    if (graduationBool === "1"){
        ctx.font = "bold "+(size*0.22)+"px arial";

        var posStrt = polar_to_cart(size/1.15, 90+spreadAngle, x, y);
        var posStop = polar_to_cart(size/1.15, 90-spreadAngle, x, y);

        ctx.save();
        ctx.translate(posStrt[0], posStrt[1]);
        ctx.rotate(deg_to_radians(-45));

        // graduation text - start limit
        // Calculation similar to the `needle` calculation above
        var start_limit = round1decimal((angleOffset * (maxvalue / (1.5 + angleOffset))) + offset);
        if (unitend ==="0"){
            ctx.fillText(""+start_limit+units, 0, 0);}
        if (unitend ==="1"){
            ctx.fillText(""+units+start_limit, 0, 0);}
        // Since we've translated the entire context, the coords we want to draw at are now at [0,0]  
        ctx.restore();

        ctx.save(); // each ctx.save is only good for one restore, apparently.
        ctx.translate(posStop[0], posStop[1]);
        ctx.rotate(deg_to_radians(45));

        // graduation text - end limit
        var end_limit = round1decimal(offset+maxvalue);
        if (unitend ==="0"){
            ctx.fillText(""+end_limit+units, 0, 0);}
        if (unitend ==="1"){
            ctx.fillText(""+units+end_limit, 0, 0);}
        ctx.restore();


        if(displayminmax==="1"){ //display min max circle

            ctx.beginPath();
            ctx.arc(x+Math.sin(Math.PI*minindicator)*size,y+Math.cos(Math.PI*minindicator)*size,size*0.052,0,2*Math.PI,true); // draw min circle
            ctx.closePath();
            ctx.fill();
            ctx.stroke();

            ctx.beginPath();
            ctx.arc(x+Math.sin(Math.PI*maxindicator)*size,y+Math.cos(Math.PI*maxindicator)*size,size*0.052,0,2*Math.PI,true); // draw max circle
            ctx.closePath();
            ctx.fill();
            ctx.stroke();

        }
    }
}

var dial_widget = {
    mount: function(el, config, ctx){
        var canvas = ctx.canvas(el);
        var feeds = ctx.feeds;

        // Each reading eases towards its value over several frames.
        var curve = { val: 0, minval: 0, maxval: 0 };
        var last = { val: null, minval: null, maxval: null, errorCode: null };
        var force = true;

        var draw = function(){
            var feed = feeds.get(config.feedid);
            var minfeed = feeds.get(config.minvaluefeed || "0");
            var maxfeed = feeds.get(config.maxvaluefeed || "0");

            var val = 0;
            if (feed) {
                val = (feed.value * 1).toFixed(3);
                curve.val = render_curve(curve.val, feed.value);
            }
            var val_curve = curve.val.toFixed(3);

            var timeout = widget_timeout(config, feed);
            var errorCode = timeout.code;

            // The minval and maxval feed settings default to the first feed in the
            // feedlist which may not be public for use in public dashboards. The
            // min/max values are 0 where the feed settings are not valid.
            var minval = 0;
            if (minfeed) {
                minval = (minfeed.value * 1).toFixed(3);
                curve.minval = render_curve(curve.minval, minfeed.value);
            }
            var minval_curve = curve.minval.toFixed(3);

            var maxval = 0;
            if (maxfeed) {
                maxval = (maxfeed.value * 1).toFixed(3);
                curve.maxval = render_curve(curve.maxval, maxfeed.value);
            }
            var maxval_curve = curve.maxval.toFixed(3);

            // Only drawn on change
            if (force || val_curve != val || minval_curve != minval || maxval_curve != maxval
                || errorCode != last.errorCode)
            {
                // Min/max is off when one of the feed settings is not valid
                var displayminmax = config.displayminmax || "0";
                if (!minfeed || !maxfeed) displayminmax = "0";

                var scale = 1*config.scale || 1;
                draw_gauge(canvas.context, el.clientWidth, el.clientHeight, config, {
                    value: val_curve*scale,
                    min: minval_curve*scale,
                    max: maxval_curve*scale,
                    displayminmax: displayminmax,
                    errorCode: errorCode,
                    errorMessage: timeout.message
                });
                force = false;
            }
            last.errorCode = errorCode;
        };

        return {
            update: function(live){ feeds = live; },
            frame: function(){ draw(); },
            resize: function(){ canvas.fit(); force = true; draw(); },
            destroy: function(){}
        };
    }
};
