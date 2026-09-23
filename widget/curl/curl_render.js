/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

*/

function curl_widgetlist()
{
    var widgets = {
        "curl":
        {
            "offsetx":-60,"offsety":-20,"width":120,"height":40,
            "menu":"Widgets",
            "options":["ip","port","url","payload","method", "https","timeout","colour","caption","confirm"],
            "optionsname":[_Tr("IP"),_Tr("Port"),_Tr("URL"),_Tr("Payload"),_Tr("Method"),_Tr("HTTPS"),_Tr("Timeout"),_Tr("Colour"),_Tr("Caption"),_Tr("Confirmation")],
            "optionshint":[_Tr("IP address of server (server must have cross origin allowed to work, will not generally work on servers other than localhost)"),_Tr("Listen port of server"),_Tr("URL example: node/param"),_Tr("Data to send"),_Tr("GET/POST"),_Tr("yes/no"),_Tr("in milliseconds"),_Tr("0=rd, 1=gn, 2=gy, 3=bu, 4=vio, 5=ye, >5=bk"),_Tr("Button Text"),_Tr("Confirmation Box: yes/no")]
        }
    };

    return widgets;
}

var curl_widget = {
    mount: function(el, config, ctx) {
        var canvas = ctx.canvas(el);

        var draw = function() {
            draw_curl(canvas.canvas, config.colour, config.caption);
        };

        var click = function(event) {
            var method = (config.method === "" || config.method === undefined) ? "GET" : config.method;
            var payload = config.payload === undefined ? "" : config.payload;
            var timeout = (config.timeout > 0) ? config.timeout : 1000;
            var url = "http" + ((config.https == "yes") ? "s" : "") + "://"
            + config.ip + ":" + config.port + "/" + config.url;

            // Request is sent by the browser of whoever is looking at the
            // dashboard, to whatever address the author typed. On a dashboard the
            // visitor does not own that is a request to the visitor's own network,
            // from a button whose caption the author also wrote, so the destination
            // is shown and the visitor has to agree to it. The author gets the
            // confirmation they asked for on their own dashboard and nothing more.
            var owner = (typeof dashboard_owner !== "undefined" && dashboard_owner === true);

            if (owner) {
                if (config.confirm == "yes" && !confirm(_Tr("Do you want to continue?"))) return;
            } else {
                if (!confirm(_Tr("This dashboard is asking your browser to send a request to") + ":\n\n"
                        + method + " " + url + "\n\n" + _Tr("Do you want to continue?"))) return;
            }

            var data;
            if (payload.trim().charAt(0) === "{") {
                data = {"data": payload.trim()};
            } else if (payload.indexOf("=") === -1) {
                data = {"data": payload};
            } else {
                data = payload;
            }

            $.ajax({type: method, url: url, data: data, timeout: timeout});
        };
        el.addEventListener("click", click);

        draw();

        // No feed. Drawn on mount and on a resize.
        return {
            update: function() {},
            resize: function() { canvas.fit(); draw(); },
            destroy: function() { el.removeEventListener("click", click); }
        };
    }
};

// Fills the canvas with the button and centres the caption in it.
function draw_curl(canvas,status,text)
{
    var button = canvas.getContext("2d");
    if (!button) return;
    var w = canvas.width;
    var h = canvas.height;

    button.clearRect(0,0,w,h);

    button.fillStyle = "#8F8F8F";
    button.beginPath();
    button.rect(0, 0, w, h);
    button.closePath();
    button.fill();

    var mycolour;
    var mycolourtxt;

    if (status==0) {                              // 0=red
        mycolour = "#FF6347";
        mycolourtxt = "#000000";
    } else if (status>0 && status <=1) {          // 1=green
        mycolour = "#7FFF00";
        mycolourtxt = "#000000";
    } else if (status>1 && status <=2) {          // 2=grey
        mycolour = "#CCCCCC";
        mycolourtxt = "#000000";
    } else if (status>2 && status <=3) {          // 3=blue
        mycolour = "#87CEFF";
        mycolourtxt = "#000000";
    } else if (status>3 && status <=4) {          // 4=purple
        mycolour = "#9B30FF";
        mycolourtxt = "#FFFFFF";
    } else if (status>4 && status <=5)   {        // 5=yellow
        mycolour = "#FFFF00";
        mycolourtxt = "#000000";
    } else {                                      // >6=black
        mycolour = "#000000";
        mycolourtxt = "#FFFFFF";
    }

    // draw shapes
    button.fillStyle = mycolour;
    button.fillRect(3,3,w-6,h-6);
    button.font = "16pt Arial";
    button.textAlign = "center";
    button.textBaseline = "middle";
    button.fillStyle = mycolourtxt;
    button.fillText(text, w/2, h/2, w-10);
}
