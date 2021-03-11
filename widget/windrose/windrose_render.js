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
  }
  return widgets;
}

function windrose_init()
{
  setup_widget_canvas('windrose');

  // Load the needle image
  windrose_needle = new Image();
  windrose_needle.src = path+'Modules/dashboard/widget/windrose/needle.png';
  
  // Load the windrose image
  windrose_windrose = new Image();
  windrose_windrose.src = path+'Modules/dashboard/widget/windrose/windrose.png';
}

function windrose_draw()
{
  $('.windrose').each(function(index)
  {
    // Feed 1:
    var feedid = $(this).attr("feedid");
    if (assocfeed[feedid]!=undefined) feedid = assocfeed[feedid]; // convert tag:name to feedid

    var val = 0;
    var curve_val = 0;
    if (associd[feedid] != undefined) { 
        val = (associd[feedid]["value"] * 1).toFixed(3);
        curve_val = curve_value(feedid,dialrate).toFixed(3);
    }

    // Feed 2:
    var feedid2 = $(this).attr("feedid2");
    if (assocfeed[feedid2]!=undefined) feedid2 = assocfeed[feedid2]; // convert tag:name to feedid
        
    var val2 = 0;
    var curve_val2 = 0;
    if (associd[feedid2] != undefined) { 
        val2 = (associd[feedid2]["value"] * 1).toFixed(3);
        curve_val2 = curve_value(feedid2,dialrate).toFixed(3);
    }
    
    // ONLY UPDATE ON CHANGE
    if (curve_val!=val || curve_val2!=val2 || redraw == 1) {
      var id = "can-"+$(this).attr("id");
      var scale = 1*$(this).attr("scale") || 1;
      draw_windrose(widgetcanvas[id],0,0,$(this).width(),$(this).height(),curve_val,curve_val2*scale,$(this).attr("units"));
    }
  });
}

function windrose_slowupdate()
{

}

function windrose_fastupdate()
{
  windrose_draw();
}

function draw_windrose(ctx,x,y,width,height,value,value2,units)
{
  var max = 360;
  if (!value) value = 0;
  if (!value2) value2 = 0;
  if (!units) units = " ";
  var offset = 180;
  var position = ((value*360)/max);
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

  decimalPlaces = 0;
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
