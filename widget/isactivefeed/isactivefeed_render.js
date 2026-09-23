/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
    Widget initially created by Aymeric Thibaut
 */
var shapeOptionsIsActive = [
    [2, _Tr("Circle")],
    [1, _Tr("Triangle &#9650;")],
    [5, _Tr("Triangle &#9654;")],
    [6, _Tr("Triangle &#9660;")],
    [7, _Tr("Triangle &#x25C0;")],
    [0, _Tr("Square")],
    [3, _Tr("Star 5 spikes")],
    [4, _Tr("Star 6 spikes")]
];

function isactivefeed_widgetlist()
{
    var widgets = {
        "isactivefeed":
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
    addOption(widgets["isactivefeed"], "feedid",      "feedid",         _Tr("Feed"),       _Tr("Feed value"),                                         []);
    addOption(widgets["isactivefeed"], "threshold1",  "value",          _Tr("Threshold1"), _Tr("Threshold1 in seconds"),                              []);
    addOption(widgets["isactivefeed"], "threshold2",  "value",          _Tr("Threshold2"), _Tr("Threshold2 in seconds"),                              []);
    addOption(widgets["isactivefeed"], "colour1",     "colour_picker",  _Tr("Colour1"),    _Tr("Colour for range below Threshold1"),                  []);
    addOption(widgets["isactivefeed"], "colour2",     "colour_picker",  _Tr("Colour2"),    _Tr("Colour for range between Threshold1 and Threshold2"), []);
    addOption(widgets["isactivefeed"], "colour3",     "colour_picker",  _Tr("Colour3"),    _Tr("Colour for range above Threshold2"),                  []);
    addOption(widgets["isactivefeed"], "shapetype",   "dropbox",        _Tr("Shape"),      _Tr("Shape"),                                              shapeOptionsIsActive);
    return widgets;
}

// Shape drawing shared with thresholds, which loads after this file.

function draw_status_star(ctx, cx, cy, spikes, outerRadius, innerRadius, colour) {
    var rot = Math.PI / 2 * 3;
    var x = cx;
    var y = cy;
    var step = Math.PI / spikes;
    var i;

    ctx.strokeStyle = "#000";
    ctx.beginPath();
    ctx.moveTo(cx, cy - outerRadius);

    for (i = 0; i < spikes; i++) {
        x = cx + Math.cos(rot) * outerRadius;
        y = cy + Math.sin(rot) * outerRadius;
        ctx.lineTo(x, y);
        rot += step;

        x = cx + Math.cos(rot) * innerRadius;
        y = cy + Math.sin(rot) * innerRadius;
        ctx.lineTo(x, y);
        rot += step;
    }
    ctx.lineTo(cx, cy - outerRadius);
    ctx.closePath();
    ctx.lineWidth=1;
    ctx.strokeStyle= colour;
    ctx.stroke();
    ctx.fillStyle= colour;
    ctx.fill();

}

// Fills the canvas with the shape in the colour of the status: 0 above
// threshold2, 1 between the thresholds, 2 below threshold1.
function draw_status_shape(shape,feedstatus, colour3, colour2, colour1, shapetype){
    if (!shape) {return;}
    var width = shape.canvas.width;
    var height = shape.canvas.height;
    var borderx = Math.min(40, Math.floor(width/2));
    var bordery = Math.min(40, Math.floor(height/2));
    var dimension = Math.max(10, Math.min(width-borderx, height-bordery));
    var offsetx = Math.floor((width - dimension) / 2.0);
    var offsety = Math.floor((height - dimension) / 2.0);
    shape.clearRect(0,0,width,height);
    var fillcolor;
    if (feedstatus===0) {
        fillcolor= colour3;
    } else if (feedstatus===1) {
        fillcolor= colour2;
    } else if (feedstatus===2) {
        fillcolor= colour1;
    } else {
        fillcolor= "#000000";
    }
    if (shapetype==="2"){ // Circle

        shape.beginPath();
        shape.fillStyle=fillcolor;
        shape.arc(width/2, height/2, dimension/2, 0, 2 * Math.PI);
        shape.fill();
    }

    if (shapetype==="1"){ //Triangle 1

        shape.beginPath();
        shape.fillStyle=fillcolor;
        shape.moveTo(offsetx, offsety+dimension);
        shape.lineTo(offsetx+dimension, offsety+dimension);
        shape.lineTo(offsetx+dimension/2,offsety);
        shape.fill();}

    if (shapetype==="5"){ //Triangle 2

        shape.beginPath();
        shape.fillStyle=fillcolor;
        shape.moveTo(offsetx,offsety);
        shape.lineTo(offsetx+dimension, offsety+dimension/2);
        shape.lineTo(offsetx,offsety+dimension);
        shape.fill();}

    if (shapetype==="6"){ //Triangle 3

        shape.beginPath();
        shape.fillStyle=fillcolor;
        shape.moveTo(offsetx, offsety);
        shape.lineTo(offsetx+dimension, offsety);
        shape.lineTo(offsetx+dimension/2,offsety+dimension);
        shape.fill();}

    if (shapetype==="7"){ //Triangle 4

        shape.beginPath();
        shape.fillStyle=fillcolor;
        shape.moveTo(offsetx+dimension,offsety);
        shape.lineTo(offsetx, offsety+dimension/2);
        shape.lineTo(offsetx+dimension,offsety+dimension);
        shape.fill();}

    if (shapetype==="0"){ //Square

        shape.fillStyle=fillcolor;
        shape.fillRect(offsetx,offsety,dimension,dimension);}

    if (shapetype==="3"){ //Star 5 spikes

        draw_status_star(shape, width/2, height/2, 5, dimension/2,dimension/4, fillcolor);}

    if (shapetype==="4"){ //Star 6 spikes

        draw_status_star(shape, width/2, height/2, 6, dimension/2,dimension/4, fillcolor);}
}

var isactivefeed_widget = {
    mount: function(el, config, ctx) {
        var canvas = ctx.canvas(el);
        var feeds = ctx.feeds;

        // Colours and shape from the options.
        var settings = function() {
            return {
                colour1: widget_colour(config.colour1, "#019F62"),
                colour2: widget_colour(config.colour2, "#FF8425"),
                colour3: widget_colour(config.colour3, "#F70511"),
                shapetype: config.shapetype|| "2"
            };
        };

        var draw = function() {
            var limit1 = config.threshold1*1 || 60;
            var limit2 = config.threshold2*1 || 120;
            var s = settings();
            var val;

            var feed = feeds.get(config.feedid);
            if (!feed) return;
            var delay =  Math.round(Date.now() /1000 - offsetofTime - feed["time"]);

            if (delay > limit2){
                val=0;}
            else if (delay > limit1){
                val=1;}
            else {
                val=2;}

            draw_status_shape(canvas.context, val, s.colour3, s.colour2, s.colour1, s.shapetype);
        };

        // Shape in the above threshold2 colour with a "!" over it, after a failed poll.
        var nonetwork = function() {
            var s = settings();
            var shape = canvas.context;
            var width = shape.canvas.width;
            var height = shape.canvas.height;
            var borderx = Math.min(40, Math.floor(width/2));
            var bordery = Math.min(40, Math.floor(height/2));
            var dimension = Math.max(10, Math.min(width-borderx, height-bordery));
            var offsetx = Math.floor((width - dimension) / 2.0);
            var offsety = Math.floor((height - dimension) / 2.0);

            draw_status_shape(shape, 0, s.colour3, s.colour2, s.colour1, s.shapetype);

            shape.font = "bold "+ dimension +"px Arial";
            shape.fillStyle = "#000000";
            shape.fillText("!", (offsetx+dimension/2)*0.85, offsety+dimension*7/8);
        };

        var paint = function() {
            if (feeds.online === false) nonetwork(); else draw();
        };

        return {
            update: function(live) { feeds = live; paint(); },
            resize: function() { canvas.fit(); paint(); },
            destroy: function() {}
        };
    }
};
