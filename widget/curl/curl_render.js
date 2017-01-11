
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
      "optionshint":[_Tr("IP address of server"),_Tr("Listen port of server"),_Tr("URL example: node/param"),_Tr("Data to send"),_Tr("GET/POST"),_Tr("yes/no"),_Tr("in milliseconds"),_Tr("0=rd, 1=gn, 2=gy, 3=bu, 4=vio, 5=ye, >5=bk"),_Tr("Button Text"),_Tr("Confirmation Box: yes/no")]
    }
  };

  curl_events();

  return widgets;
}

function curl_events()
{
  $('.curl').on("click", function(event) {

    var jqxhr; //jQuery XMLHttpRequest

    if($(this).attr("confirm")=="yes"){
        
            var r = confirm(_Tr("Do you want to continue?"));
            if (r == true) {
                jqxhr =  $.ajax({type:$(this).attr("method"),
                    url:"http"+(($(this).attr("https")=="yes")?"s":"")+"://"+$(this).attr("ip")+":"+$(this).attr("port")+"/"+$(this).attr("url"),
                    data: ($(this).attr("payload").trim().charAt(0) === "{") ? {"data": $(this).attr("payload").trim()} : $(this).attr("payload"),
                    timeout: ($(this).attr("timeout") > 0) ? $(this).attr("timeout") : 1000 });                
            } else {
                // Nothing to do
            }
        
    }else{
        jqxhr =  $.ajax({type:$(this).attr("method"),
            url:"http"+(($(this).attr("https")=="yes")?"s":"")+"://"+$(this).attr("ip")+":"+$(this).attr("port")+"/"+$(this).attr("url"),
            data: ($(this).attr("payload").trim().charAt(0) === "{") ? {"data": $(this).attr("payload").trim()} : $(this).attr("payload"),
            timeout: ($(this).attr("timeout") > 0) ? $(this).attr("timeout") : 1000 });
    }
    
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

