/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
   Part of the OpenEnergyMonitor project:  http://openenergymonitor.org
 */

function jgauge2_widgetlist()
{
    var widgets = {
        "jgauge2":
        {
            "offsetx":-80,"offsety":-80,"width":160,"height":160,
            "menu":"Widgets",
            "options":["feedid", "feedid2", "scale", "max", "min", "units","timeout","errormessagedisplayed"],
            "optionstype":["feedid","feedid","value","value","value","value","value","value"],
            "optionsname":[_Tr("Feed 1"),_Tr("Feed 2"),_Tr("Scale"),_Tr("Max value"),_Tr("Min value"),_Tr("Units"),_Tr("Timeout"),_Tr("Error Message")],
            "optionshint":[_Tr("Feed 1"),_Tr("Feed 2 (Min/Max for example)"),_Tr("Scale applied to value"),_Tr("Max value to show"),_Tr("Min value to show"),_Tr("Units to show"),_Tr("Timeout without feed update in seconds (empty is never)"),_Tr("Error message displayed when timeout is reached")]

        }
    };
    return widgets;
}

// Face, ticks and needles drawn with the jgauge_* functions in
// jgauge_render.js, which loads first.
function draw_jgauge2(ctx,width,height,value,value2,max,min,units,errorCode,errorMessage)
{
    if (!max) max = 1000;
    if (!min) min = 0;
    min = Number(min);
    max = Number(max);
    if (!value) value = 0;
    if (!value2) value2 = 0;
    if (!units) units = " ";
    var position = jgauge_position(value, max, min);
    var position2 = jgauge_position(value2, max, min);
    var size = jgauge_size(width, height);
    var decimalPlaces = jgauge_decimals(max, min);

    ctx.clearRect(0,0,width,height);

    ctx.drawImage(jgauge_image("jgauge2/jgauge2.png"), 0, 0, size, size);

    jgauge_ticks(ctx, size, max, min, decimalPlaces);

    // main label
    ctx.font = "14pt Calibri,Geneva,Arial";
    ctx.strokeStyle = "rgb(255,255,255)";
    ctx.fillStyle = "rgb(255,255,255)";
    if (errorCode!= "1"){
        value = Number(value.toFixed(decimalPlaces));
        ctx.fillText(value+units, 50*(size/100), 88*(size/100));
    }
    else
    {
        ctx.fillText(errorMessage, 50*(size/100), 85*(size/100));
    }
    // max label
    ctx.font = "10pt Calibri,Geneva,Arial";
    ctx.strokeStyle = "rgb(255,255,255)";
    ctx.fillStyle = "rgb(255,1,1)";
    value = Number(value2.toFixed(decimalPlaces));
    ctx.fillText(value+units, 50*(size/100), 75*(size/100));

    if (errorCode!= "1"){
        jgauge_needle(ctx, jgauge_image("jgauge2/needle2.png"), size, position);
    }
    jgauge_needle(ctx, jgauge_image("jgauge2/needle.png"), size, position2);
}

var jgauge2_widget = {
    mount: function(el, config, ctx){
        var canvas = ctx.canvas(el);
        var feeds = ctx.feeds;

        // Each needle eases towards its value over several frames.
        var curve = { val: 0, val2: 0 };
        var last_errorCode = null;
        var force = true;

        var draw = function(){
            // Feed 1:
            var feed = feeds.get(config.feedid);

            var val = 0;
            var curve_val = 0;

            if (feed) {
                val = (feed.value * 1).toFixed(3);
                curve.val = render_curve(curve.val, feed.value);
                curve_val = curve.val.toFixed(3);
            }

            // Feed 2:
            var feed2 = feeds.get(config.feedid2);

            var val2 = 0;
            var curve_val2 = 0;

            if (feed2) {
                val2 = (feed2.value * 1).toFixed(3);
                curve.val2 = render_curve(curve.val2, feed2.value);
                curve_val2 = curve.val2.toFixed(3);
            }

            // Timeout on feed 1 only
            var timeout = widget_timeout(config, feed);
            var errorCode = timeout.code;
            if (last_errorCode==null) last_errorCode = errorCode;

            // ONLY UPDATE ON CHANGE
            if (val!=curve_val || val2!=curve_val2 || force || errorCode != last_errorCode)
            {
                var scale = 1*config.scale || 1;
                draw_jgauge2(canvas.context,el.clientWidth,el.clientHeight,curve_val*scale,curve_val2*scale,config.max,config.min,config.units,errorCode,timeout.message);
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
