
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

  curl_events();

  return widgets;
}

function curl_events()
{
  $('.curl').on("click", function(event) {

    var el = $(this);
    var method = (el.attr("method") === "" || el.attr("method") === undefined) ? "GET" : el.attr("method");
    var payload = el.attr("payload") === undefined ? "" : el.attr("payload");
    var timeout = (el.attr("timeout") > 0) ? el.attr("timeout") : 1000;
    var url = "http" + ((el.attr("https") == "yes") ? "s" : "") + "://"
        + el.attr("ip") + ":" + el.attr("port") + "/" + el.attr("url");

    // The request is sent by the browser of whoever is looking at the
    // dashboard, to whatever address the author typed. On a dashboard the
    // visitor does not own that is a request to the visitor's own network,
    // from a button whose caption the author also wrote, so the destination
    // is shown and the visitor has to agree to it. The author gets the
    // confirmation they asked for on their own dashboard and nothing more.
    var owner = (typeof dashboard_owner !== "undefined" && dashboard_owner === true);

    if (owner) {
      if (el.attr("confirm") == "yes" && !confirm(_Tr("Do you want to continue?"))) return;
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

    var jqxhr = $.ajax({type: method, url: url, data: data, timeout: timeout});

    console.log(jqxhr);

  });
}

function curl_init()
{
  setup_widget_canvas('curl');
}

function curl_draw()
{
  $('.curl').each(function(index)
  {
    var id = "can-"+$(this).attr("id");
    var colour = $(this).attr("colour");
    var caption = $(this).attr("caption");
    draw_curl(widgetcanvas[id], colour, caption);
  });
}

function curl_slowupdate()
{
  curl_draw();
}

function curl_fastupdate()
{
}

function draw_curl(button,status,text)
{
  if (!button) return;
  button.clearRect(0,0,120,40);

  button.fillStyle = "#8F8F8F";
  button.beginPath();
  button.rect(0, 0, 120, 40);
  button.closePath();
  button.fill();

  var mycolour;
  var mycolourtxt;
  
  if (status==0) {                              // 0=red
    mycolour = '#FF6347';
    mycolourtxt = '#000000';
  } else if (status>0 && status <=1) {          // 1=green
    mycolour = '#7FFF00';
    mycolourtxt = '#000000';
  } else if (status>1 && status <=2) {          // 2=grey
    mycolour = '#CCCCCC';
    mycolourtxt = '#000000';
  } else if (status>2 && status <=3) {          // 3=blue
    mycolour = '#87CEFF';
    mycolourtxt = '#000000';
  } else if (status>3 && status <=4) {          // 4=purple
    mycolour = '#9B30FF';
    mycolourtxt = '#FFFFFF';
  } else if (status>4 && status <=5)   {        // 5=yellow
    mycolour = '#FFFF00';
    mycolourtxt = '#000000';
  } else {                                      // >6=black
    mycolour = '#000000';
    mycolourtxt = '#FFFFFF';
  }

  // draw shapes
  button.fillStyle = mycolour;
  button.fillRect(3,3,114,34);
  button.font = '16pt Arial';
  button.textAlign = 'center';
  button.textBaseline = 'middle';
  button.fillStyle = mycolourtxt; 
  button.fillText(text, 60, 20, 110);
}

