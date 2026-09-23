/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org
*/

function button_widgetlist()
{
    var widgets = {
        "button":
        {
            "offsetx":-40,"offsety":-40,"width":80,"height":80,
            "menu":"Widgets",
            "options":["feedid","value"],
            "optionstype":["feedid_realtime","value"],
            "optionsname":[_Tr("Feed"),_Tr("Value")],
            "optionshint":[_Tr("Feed to set, control with caution, make sure device being controlled can operate safely in event of emoncms failure."),_Tr("Starting value")]
        }
    };

    return widgets;
}

var button_widget = {
    mount: function(el, config, ctx) {
        var canvas = ctx.canvas(el);
        var feeds = ctx.feeds;

        var draw = function() {
            var val = 0;
            var feed = feeds.get(config.feedid);
            if (feed) val = feed["value"]*1;
            draw_button(canvas.canvas, val);
        };

        // A click toggles the value, writes it to the feed and shows it at once.
        // Live row is set too, so the next poll does not show the old value.
        var click = function(event) {
            var feedid = feeds.id(config.feedid);

            var value = config.value;
            if (value == 0) value = 1; else value = 0;

            $.ajax({
                    url: path+"feed/insert.json",
                    data: "id="+feedid+"&time="+parseInt((new Date()).getTime()/1000)+"&value="+value,
                    dataType: "json",
                    async: false,
                    success: function(result){
                        if (result!=value) {
                            alert(JSON.stringify(result));
                        }
                    }
                });

            config.value = value;

            draw_button(canvas.canvas, value);
            var feed = feeds.get(config.feedid);
            if (feed) feed["value"] = value;
        };
        el.addEventListener("click", click);

        return {
            update: function(live) { feeds = live; draw(); },
            resize: function() { canvas.fit(); draw(); },
            destroy: function() { el.removeEventListener("click", click); }
        };
    }
};


// Drawn to the canvas size, as an 80px button scaled.
function draw_button(canvas,status)
{
    var circle = canvas.getContext("2d");
    if (!circle) return;
    var w = canvas.width;
    var h = canvas.height;
    var s = Math.min(w, h) / 80;
    var cx = w / 2;
    var cy = h / 2;

    circle.clearRect(0,0,w,h);

    circle.fillStyle = "#ddd";
    circle.beginPath();
    circle.arc(cx,cy,25*s, 0,Math.PI * 2,false);
    circle.closePath();
    circle.fill();

    var radgrad = circle.createRadialGradient(cx,cy,0,cx,cy,20*s);

    if (status==0) {                              // red
        radgrad.addColorStop(0, "#F75D59");
        radgrad.addColorStop(0.9, "#C11B17");
    } else if (status>0 && status <=1) {          // green
        radgrad.addColorStop(0, "#A7D30C");
        radgrad.addColorStop(0.9, "#019F62");
    } else if (status>1 && status <=2) {          // grey
        radgrad.addColorStop(0, "#736F6E");
        radgrad.addColorStop(0.9, "#4A4344");
    } else if (status>2 && status <=3) {          // Blue
        radgrad.addColorStop(0, "#00C9FF");
        radgrad.addColorStop(0.9, "#00B5E2");
    } else if (status>3 && status <=4) {          // Purple
        radgrad.addColorStop(0, "#FF5F98");
        radgrad.addColorStop(0.9, "#FF0188");
    } else if (status>4 && status <=5)   {        // yellow
        radgrad.addColorStop(0, "#F4F201");
        radgrad.addColorStop(0.9, "#E4C700");
    } else {                    // Black
        radgrad.addColorStop(0, "#000000");
        radgrad.addColorStop(0.9, "#000000");
    }

    radgrad.addColorStop(1, "rgba(1,159,98,0)");
    // draw shapes
    circle.fillStyle = radgrad;
    circle.fillRect(cx-20*s,cy-20*s,40*s,40*s);
}
