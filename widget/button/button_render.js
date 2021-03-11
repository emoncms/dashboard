
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
  }

  button_events();

  return widgets;
}

function button_events()
{
  $('.button').on("click", function(event) {
    var feedid = $(this).attr("feedid");
    if (assocfeed[feedid]!=undefined) feedid = assocfeed[feedid]; // convert tag:name to feedid

    var value = $(this).attr("value");
    if (value == 0) value = 1; else value = 0;
    
    $.ajax({ 
        url: path+"feed/insert.json", 
        data: "id="+feedid+"&time="+parseInt((new Date()).getTime()/1000)+"&value="+value, 
        dataType: 'json', 
        async: false, 
        success: function(result){
            if (result!=value) {
                alert(JSON.stringify(result));
            }
        }
    });
    
    $(this).attr("value",value);

    var id = "can-"+$(this).attr("id");
    draw_button(widgetcanvas[id], value);
    associd[feedid]['value'] = value;
  });
}

function button_init()
{
  setup_widget_canvas('button');
}

function button_draw()
{
  $('.button').each(function(index)
  {
    var feedid = $(this).attr("feedid");
    if (assocfeed[feedid]!=undefined) feedid = assocfeed[feedid]; // convert tag:name to feedid
    
    var val = 0;
    if (associd[feedid] != undefined) val = associd[feedid]['value']*1;
    
    var id = "can-"+$(this).attr("id");
    draw_button(widgetcanvas[id], val);
  });
}

function button_slowupdate()
{
  button_draw();
}

function button_fastupdate()
{
  if (redraw) button_draw();
}


function draw_button(circle,status)
{
  if (!circle) return;
  circle.clearRect(0,0,80,80);

  circle.fillStyle = "#ddd";
  circle.beginPath();
  circle.arc(40,40,25, 0,Math.PI * 2,false);
  circle.closePath();
  circle.fill()

  var radgrad = circle.createRadialGradient(40,40,0,40,40,20);

  if (status==0) {                              // red
    radgrad.addColorStop(0, '#F75D59');
    radgrad.addColorStop(0.9, '#C11B17');
  } else if (status>0 && status <=1) {          // green
    radgrad.addColorStop(0, '#A7D30C');
    radgrad.addColorStop(0.9, '#019F62');
  } else if (status>1 && status <=2) {          // grey
    radgrad.addColorStop(0, '#736F6E');
    radgrad.addColorStop(0.9, '#4A4344');
  } else if (status>2 && status <=3) {          // Blue
    radgrad.addColorStop(0, '#00C9FF');
    radgrad.addColorStop(0.9, '#00B5E2');
  } else if (status>3 && status <=4) {          // Purple
    radgrad.addColorStop(0, '#FF5F98');
    radgrad.addColorStop(0.9, '#FF0188');
  } else if (status>4 && status <=5)   {        // yellow
    radgrad.addColorStop(0, '#F4F201');
    radgrad.addColorStop(0.9, '#E4C700');
  } else {					  // Black
    radgrad.addColorStop(0, '#000000');
    radgrad.addColorStop(0.9, '#000000');
  }

  radgrad.addColorStop(1, 'rgba(1,159,98,0)');
  // draw shapes
  circle.fillStyle = radgrad;
  circle.fillRect(20,20,60,60);
}

