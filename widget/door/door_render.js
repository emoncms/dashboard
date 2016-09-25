/*
   All emon_widgets code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    Author: Trystan Lea: trystan.lea@googlemail.com
    If you have any questions please get in touch, try the forums here:
    http://openenergymonitor.org/emon/forum
 */


function addOption(widget, optionKey, optionType, optionName, optionHint, optionData)
{
  widget["options"    ].push(optionKey);
  widget["optionstype"].push(optionType);
  widget["optionsname"].push(optionName);
  widget["optionshint"].push(optionHint);
  widget["optionsdata"].push(optionData);
}


function door_widgetlist()
{
  var widgets = {
    "door":
    {
      "offsetx":-40,"offsety":-40,"width":80,"height":80,
      "menu":"Widgets",
      "options":    [],
      "optionstype":[],
      "optionsname":[],
      "optionshint":[],
      "optionsdata":[]
    }
  };


  var directionDropBoxOptions = [        // Options for the type combobox. Each item is [typeID, "description"]
        [0,    "left"],
        [1,    "right"]
    ];

  addOption(widgets["door"], "feedid",     "feedid",           _Tr("Feed"),            _Tr("Feed value"),               []);
  addOption(widgets["door"], "direction", "dropbox",        _Tr("Direction"),      _Tr("Opening direction"),     directionDropBoxOptions);
  addOption(widgets["door"], "colour",    "colour_picker", _Tr("Open colour"), _Tr("Opened door colour"), []);
  addOption(widgets["door"], "colour2",    "colour_picker", _Tr("Closed colour"), _Tr("Closed door colour"), []);

  return widgets;
}

function door_init()
{
  setup_widget_canvas('door');
  door_draw();
}

function door_draw() {
  $('.door').each(function(index)
  {
    var feedid = $(this).attr("feedid");
    if (associd[feedid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    var val = associd[feedid]['value'] * 1;
    var id = "can-"+$(this).attr("id");
    var dir = $(this).attr("direction");
    var colour = $(this).attr("colour");
	var colour2 = $(this).attr("colour2");

    if (browserVersion >= 9)
      draw_door(widgetcanvas[id], val, dir, colour, colour2);
  });
}

function door_slowupdate() {
  door_draw();
}

function door_fastupdate() {}

function draw_door(ctx, status, direction, colour_open, colour_closed){
  if (!ctx) 
    return;

  // Fix missing "#" on colour if needed
//  if (colour.indexOf("#") == -1)
   
  colour_closed = colour_closed || "#000000";
  colour_open = "#" + colour_open || "#000000";
  
	ctx.width = ctx.width;
  ctx.clearRect(0, 0, 80, 80, colour_closed, colour_open, ctx);

  if (status==0) {
    angle = 0.6;
  }
	  else {
		angle = 0;
	  }
  doorwidth = 6;
  doorlength = 50;

  ctx.translate(40, 60);

  // draw door frame
  ctx.fillStyle = "white";
  ctx.fillRect(-doorwidth/2, -doorlength-doorwidth/2, doorwidth, doorlength);
  ctx.fillStyle = "grey";
  ctx.fillRect(-doorwidth/2, -doorlength-doorwidth/2, doorwidth, doorwidth/2);

  if (direction==0)
    ctx.rotate(angle);
	  else
		ctx.rotate(-angle);

//  // door shadow
//  ctx.shadowBlur=10;
//  ctx.shadowColor='black';
//  ctx.shadowOffsetX=5;
//  ctx.shadowOffsetY=2;

  // draw knob
  ctx.beginPath();
  if (direction==0)
    ctx.arc(doorwidth/2, -doorlength*0.8, doorwidth/2, 0, 2*Math.PI);
	  else
		ctx.arc(-doorwidth/2, -doorlength*0.8, doorwidth/2, 0, 2*Math.PI);
  ctx.fillStyle = "grey";
  ctx.fill();
  ctx.closePath();
  
  // draw door
  ctx.beginPath();
  ctx.lineWidth = doorwidth;
  ctx.lineCap = "butt";
  ctx.moveTo(0,0);
  ctx.lineTo(0, -doorlength);  

  if (status==0)
  ctx.strokeStyle = (colour_open);
  ctx.stroke();
  
	
  if (status==1)
  ctx.strokeStyle = (colour_closed);
  ctx.stroke();
	  
  // draw hinge
  ctx.beginPath();
  ctx.arc(0, 0, doorwidth*0.7, 0, 2*Math.PI);
  ctx.fillStyle = "grey";
  ctx.fill();

  ctx.shadowBlur=0;
  ctx.shadowOffsetX=0;
  ctx.shadowOffsetY=0;
  if (direction==0)
    ctx.rotate(-angle);
	
  else
    ctx.rotate(angle);
  ctx.translate(-40, -60);
  ctx.closePath();
  
	console.log("Value for colour_open " + colour_open + " and colour_closed " + colour_closed); return;

}
