/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
   Part of the OpenEnergyMonitor project:  http://openenergymonitor.org
 */

// Global variables
var jgauge2_needle2 = null,
  jgauge2_needle = null,
  jgauge2_jgauge2 = null;

function jgauge2_widgetlist()
{
  var widgets = {
    "jgauge2":
    {
      "offsetx":-80,"offsety":-80,"width":160,"height":160,
      "menu":"Widgets",
      "options":["feedid", "feedid2", "scale", "max", "min", "units","timeout","errormessagedisplayed"],
      "optionstype":["feedid","feedid","value","value","value","value","value"],
      "optionsname":[_Tr("Feed 1"),_Tr("Feed 2"),_Tr("Scale"),_Tr("Max value"),_Tr("Min value"),_Tr("Units"),_Tr("Timeout"),_Tr("Error Message")],
      "optionshint":[_Tr("Feed 1"),_Tr("Feed 2 (Min/Max for example)"),_Tr("Scale applied to value"),_Tr("Max value to show"),_Tr("Min value to show"),_Tr("Units to show"),_Tr("Timeout without feed update in seconds (empty is never)"),_Tr("Error message displayed when timeout is reached")]

    }
  }
  return widgets;
}

function jgauge2_init()
{
  setup_widget_canvas('jgauge2');

  // Load the needle image
  if (jgauge2_needle2==null) {
    jgauge2_needle2 = new Image();
    jgauge2_needle2.src = path+'Modules/dashboard/widget/jgauge2/needle2.png';
  }
  
  if (jgauge2_needle==null) {
    jgauge2_needle = new Image();
    jgauge2_needle.src = path+'Modules/dashboard/widget/jgauge2/needle.png';
  }

  // Load the jgauge2 image
  if (jgauge2_jgauge2==null) {
    jgauge2_jgauge2 = new Image();
    jgauge2_jgauge2.src = path+'Modules/dashboard/widget/jgauge2/jgauge2.png';
  }
}

function jgauge2_draw()
{
  var now = (new Date()).getTime()*0.001;
  
  $('.jgauge2').each(function(index)
  {
    // Feed 1:
    var feedid = $(this).attr("feedid");
    if (assocfeed[feedid]!=undefined) feedid = assocfeed[feedid]; // convert tag:name to feedid

    var val = 0;
    var curve_val = 0;
    var feed_update_time = now;
    
    if (associd[feedid] != undefined) { 
        val = (associd[feedid]["value"] * 1).toFixed(3);
        curve_val = curve_value(feedid,dialrate).toFixed(3);
        feed_update_time = 1*associd[feedid]["time"];
    }

    // Feed 2:
    var feedid2 = $(this).attr("feedid2");
    if (assocfeed[feedid2]!=undefined) feedid2 = assocfeed[feedid2]; // convert tag:name to feedid
        
    var val2 = 0;
    var curve_val2 = 0;
    var feed_update_time2 = now;
    
    if (associd[feedid2] != undefined) { 
        val2 = (associd[feedid2]["value"] * 1).toFixed(3);
        curve_val2 = curve_value(feedid2,dialrate).toFixed(3);
        feed_update_time2 = 1*associd[feedid2]["time"];
    }

    // Timeout error    
    var errorTimeout = $(this).attr("timeout");
    if (errorTimeout === "" || errorTimeout === undefined) errorTimeout = 0;

    var errorCode = "0";
    if (errorTimeout !== 0) {
        if ((now-offsetofTime-feed_update_time) > errorTimeout) errorCode = "1";
        // if ((now-offsetofTime-feed_update_time2) > errorTimeout) errorCode = "1";
    }
    var id = "can-"+$(this).attr("id");
    if (last_errorCode[id]==undefined) last_errorCode[id] = errorCode;
    
    // ONLY UPDATE ON CHANGE
    if (val!=curve_val || val2!=curve_val2 || redraw == 1 || errorCode != last_errorCode[id])
    {
      // console.log("update jguage2");
      var errorMessage = $(this).attr("errormessagedisplayed");
      if (errorMessage === "" || errorMessage === undefined) errorMessage = "TO Error"; 
      
      var scale = 1*$(this).attr("scale") || 1;
      draw_jgauge2(widgetcanvas[id],0,0,$(this).width(),$(this).height(),curve_val*scale,curve_val2*scale,$(this).attr("max"),$(this).attr("min"),$(this).attr("units"),errorCode,errorMessage);
    }

    last_errorCode[id] = errorCode;
  });
}

function jgauge2_slowupdate()
{

}

function jgauge2_fastupdate()
{
  jgauge2_draw();
}

function draw_jgauge2(ctx,x,y,width,height,value,value2,max,min,units,errorCode,errorMessage)
{
  if (!max) max = 1000;
  if (!min) min = 0;
  min = Number(min);
  max = Number(max);
  if (!value) value = 0;
  if (!value2) value2 = 0;
  if (!units) units = " ";
  var offset = 45;
  var position = (((value-min)*270)/(max - min));
  if (position > 270) {
    position = 270;
  }
  if (position < 0) {
    position = 0;
  }

  var position2 = (((value2-min)*270)/(max - min));
  if (position2 > 270) {
    position2 = 270;
  }
  if (position2 < 0) {
    position2 = 0;
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
  if (max > min) {
    if ((max - min) <= 1.2)  decimalPlaces = 2;
    else if ((max - min) <= 12)  decimalPlaces = 1;
  } else {
    if ((min - max) <= 1.2)  decimalPlaces = 2;
    else if ((min - max) <= 12)  decimalPlaces = 1;
  }
  
  ctx.clearRect(0,0,width,height);

  // Draw the jgauge2 onto the canvas
  ctx.drawImage(jgauge2_jgauge2, 0, 0, size, size);

  //ticks labels
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


  // Save the current drawing state
  ctx.save();
  // move to the middle of the image
  ctx.translate((size/2), (size/2));
  // Rotate around this point
  ctx.rotate((position + offset) * (Math.PI / 180));
  // Draw the image back and up
  if (errorCode!= "1"){
    ctx.drawImage(jgauge2_needle2, -(size/2), -(size/2), size, size);
  }
  // Restore the previous drawing state
  ctx.restore(); 

  // Save the current drawing state
  ctx.save();
  // move to the middle of the image
  ctx.translate((size/2), (size/2));
  // Rotate around this point
  ctx.rotate((position2 + offset) * (Math.PI / 180));
  // Draw the image back and up
  ctx.drawImage(jgauge2_needle, -(size/2), -(size/2), size, size);
  // Restore the previous drawing state
  ctx.restore();
}
