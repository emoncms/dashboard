/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
   Part of the OpenEnergyMonitor project:  http://openenergymonitor.org
 */

function jgauge_widgetlist()
{
    var widgets = {
        "jgauge":
        {
            "offsetx":-80,"offsety":-80,"width":160,"height":160,
            "menu":"Widgets",
            "options":["feedid", "scale", "max", "min", "units","timeout","errormessagedisplayed"],
            "optionstype":["feedid","value","value","value","value","value","value"],
            "optionsname":[_Tr("Feed"),_Tr("Scale"),_Tr("Max value"),_Tr("Min value"),_Tr("Units"),_Tr("Timeout"),_Tr("Error Message")],
            "optionshint":[_Tr("Feed"),_Tr("Scale applied to value"),_Tr("Max value to show"),_Tr("Min value to show"),_Tr("Units to show"),_Tr("Timeout without feed update in seconds (empty is never)"),_Tr("Error message displayed when timeout is reached")]

        }
    };
    return widgets;
}

// Face, ticks and needle handling shared with jgauge2, which loads after
// this file.

// Images by path under widget/, loaded once for every gauge on the page.
var jgauge_images = {};

function jgauge_image(file){
    if (!jgauge_images[file]) {
        var image = new Image();
        image.src = path + "Modules/dashboard/widget/" + file;
        jgauge_images[file] = image;
    }
    return jgauge_images[file];
}

// Side of the square the gauge is drawn in, 120 to 170px.
function jgauge_size(width, height){
    var size = width > height ? height : width;
    if (size>170) size=170;
    if (size<120) size=120;
    return size;
}

// Decimal places for the labels, from the span of the scale.
function jgauge_decimals(max, min){
    var span = Math.abs(max - min);
    if (span <= 1.2) return 2;
    if (span <= 12) return 1;
    return 0;
}

// Needle angle in degrees for a value on the scale, 0 to 270.
function jgauge_position(value, max, min){
    var position = (((value-min)*270)/(max - min));
    if (position > 270) position = 270;
    if (position < 0) position = 0;
    return position;
}

function jgauge_ticks(ctx, size, max, min, decimalPlaces){
    var step = ((max - min)/6);
    ctx.textAlign="center";
    ctx.font = "8pt Arial";
    ctx.fillStyle = "rgb(34,198,252)";
    ctx.fillText((Number(min + (step*0)).toFixed(decimalPlaces)), 30*(size/100), 72*(size/100)); // 1st tick
    ctx.fillText((Number(min + (step*1)).toFixed(decimalPlaces)), 25*(size/100), 52*(size/100)); // 2nd tick
    ctx.fillText((Number(min + (step*2)).toFixed(decimalPlaces)), 30*(size/100), 32*(size/100)); // 3rd tick
    ctx.fillText((Number(min + (step*3)).toFixed(decimalPlaces)), 50*(size/100), 27*(size/100)); // 4th tick
    ctx.fillText((Number(min + (step*4)).toFixed(decimalPlaces)), 70*(size/100), 32*(size/100)); // 5th tick
    ctx.fillStyle = "rgb(245,144,0)";
    ctx.fillText((Number(min + (step*5)).toFixed(decimalPlaces)), 75*(size/100), 52*(size/100)); // 6th tick
    ctx.fillStyle = "rgb(255,0,0)";
    ctx.fillText((Number(min + (step*6)).toFixed(decimalPlaces)), 70*(size/100), 72*(size/100)); // 7th tick
}

// Needle image turned to a position about the centre of the gauge.
function jgauge_needle(ctx, image, size, position){
    var offset = 45;
    ctx.save();
    ctx.translate((size/2), (size/2));
    ctx.rotate((position + offset) * (Math.PI / 180));
    ctx.drawImage(image, -(size/2), -(size/2), size, size);
    ctx.restore();
}

function draw_jgauge(ctx,width,height,value,max,min,units,errorCode,errorMessage)
{
    if (!max) max = 1000;
    if (!min) min = 0;
    min = Number(min);
    max = Number(max);
    if (!value) value = 0;
    if (!units) units = " ";
    var position = jgauge_position(value, max, min);
    var size = jgauge_size(width, height);
    var decimalPlaces = jgauge_decimals(max, min);

    ctx.clearRect(0,0,width,height);

    ctx.drawImage(jgauge_image("jgauge/jgauge.png"), 0, 0, size, size);

    jgauge_ticks(ctx, size, max, min, decimalPlaces);

    // main label
    ctx.font = "14pt Calibri,Geneva,Arial";
    ctx.strokeStyle = "rgb(255,255,255)";
    ctx.fillStyle = "rgb(255,255,255)";
    if (errorCode!= "1"){
        value = Number(value.toFixed(decimalPlaces));
        ctx.fillText(value+units, 50*(size/100), 85*(size/100));
    }
    else
    {
        ctx.fillText(errorMessage, 50*(size/100), 85*(size/100));
    }

    if (errorCode!= "1"){
        jgauge_needle(ctx, jgauge_image("jgauge/needle2.png"), size, position);
    }
}

var jgauge_widget = {
    mount: function(el, config, ctx){
        var canvas = ctx.canvas(el);
        var feeds = ctx.feeds;

        // Needle eases towards its value over several frames.
        var curve = 0;
        var last_errorCode = null;
        var force = true;

        var draw = function(){
            var feed = feeds.get(config.feedid);

            var val = 0;
            var curve_val = 0;

            if (feed) {
                val = (feed.value * 1).toFixed(3);
                curve = render_curve(curve, feed.value);
                curve_val = curve.toFixed(3);
            }

            var timeout = widget_timeout(config, feed);
            var errorCode = timeout.code;
            if (last_errorCode==null) last_errorCode = errorCode;

            // ONLY UPDATE ON CHANGE
            if (curve_val!=val || force || errorCode != last_errorCode)
            {
                var scale = 1*config.scale || 1;
                draw_jgauge(canvas.context,el.clientWidth,el.clientHeight,curve_val*scale,config.max,config.min,config.units,errorCode,timeout.message);
                force = false;
            }

            last_errorCode = errorCode;
        };

        return {
            update: function(live){ feeds = live; },
            frame: function(){ draw(); },
            resize: function(){ canvas.fit(); force = true; draw(); },
            destroy: function(){}
        };
    }
};
