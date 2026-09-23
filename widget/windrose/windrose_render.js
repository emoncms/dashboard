/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
   Part of the OpenEnergyMonitor project:  http://openenergymonitor.org
 */

// Global variables
var windrose_needle = null,
windrose_windrose = null;


function windrose_widgetlist()
{
    var widgets = {
        "windrose":
        {
            "offsetx":-80,"offsety":-80,"width":160,"height":160,
            "menu":"Widgets",
            "options":["feedid", "feedid2", "scale", "units"],
            "optionstype":["feedid","feedid","value","value"],
            "optionsname":[_Tr("Feed Wind"),_Tr("Feed value"),_Tr("Scale"),_Tr("Units")],
            "optionshint":[_Tr("Wind direction"),_Tr("Value shown (wind speed)"),_Tr("Scale applied to value"),_Tr("Units to show for value")]

        }
    };
    return widgets;
}

function draw_windrose(ctx,width,height,value,value2,units)
{
    if (!value) value = 0;
    if (!value2) value2 = 0;
    if (!units) units = " ";
    var offset = 180;
    var position = value*1;
    if (position > 360) {
        position = 360;
    }
    var size = 0;
    if (width>height) {
        size = height;
    } else {
        size = width;
    }
    if (size>170) size=170;
    if (size<120) size=120;

    var decimalPlaces = 0;
    if (value2 <= 1.2)  decimalPlaces = 2;
    else if (value2 <= 12)  decimalPlaces = 1;

    ctx.clearRect(0,0,width,height);

    // Draw the windrose onto the canvas
    ctx.drawImage(windrose_windrose, 0, 0, size, size);

    // main label
    ctx.font = "14pt Calibri,Geneva,Arial";
    ctx.strokeStyle = "rgb(255,255,255)";
    ctx.fillStyle = "rgb(66,200,250)";
    ctx.textAlign="center"; 
    value2 = Number(value2.toFixed(decimalPlaces));
    ctx.fillText(value2+units, 50*(size/100), 66*(size/100));

    // Save the current drawing state
    ctx.save();
    // move to the middle of the image
    ctx.translate((size/2), (size/2));
    // Rotate around this point
    ctx.rotate((position + offset) * (Math.PI / 180));
    // Draw the image back and up
    ctx.drawImage(windrose_needle, -(size/2), -(size/2), size, size);
    // Restore the previous drawing state
    ctx.restore(); 

}

// Needle and rose images, loaded once for every windrose on the page
function windrose_load_images(){
    if (windrose_needle==null) {
        windrose_needle = new Image();
        windrose_needle.src = path+"Modules/dashboard/widget/windrose/needle.png";
    }
    if (windrose_windrose==null) {
        windrose_windrose = new Image();
        windrose_windrose.src = path+"Modules/dashboard/widget/windrose/windrose.png";
    }
}

var windrose_widget = {
    mount: function(el, config, ctx){
        windrose_load_images();
        var canvas = ctx.canvas(el);
        var feeds = ctx.feeds;

        // Needle and value ease towards their readings over several frames.
        var curve = { val: 0, val2: 0 };
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

            // ONLY UPDATE ON CHANGE
            if (curve_val!=val || curve_val2!=val2 || force) {
                var scale = 1*config.scale || 1;
                draw_windrose(canvas.context,el.clientWidth,el.clientHeight,curve_val,curve_val2*scale,config.units);
                force = false;
            }
        };

        return {
            update: function(live){ feeds = live; },
            frame: function(){ draw(); },
            resize: function(){ canvas.fit(); force = true; draw(); },
            destroy: function(){}
        };
    }
};
