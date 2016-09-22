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

// feedvalue widget customized to allow to choose a colour, font, size and position of Units (thanks to Matt Galloway for this addition)
// rest enhanced by Andreas Messerli (firefox7518@gmail.com) - Swiss-solar-log.ch
 
 function addOption(widget, optionKey, optionType, optionName, optionHint, optionData)
{
  widget["options"    ].push(optionKey);
  widget["optionstype"].push(optionType);
  widget["optionsname"].push(optionName);
  widget["optionshint"].push(optionHint);
  widget["optionsdata"].push(optionData);
}

function feedvalue_custom_widgetlist()
{
  var widgets =
  {
    "feedvalue_custom":
    {
      "offsetx":-40,"offsety":-30,"width":120,"height":60,
      "menu":"Widgets",
      "options":    [],
      "optionstype":[],
      "optionsname":[],
      "optionshint":[],
      "optionsdata":[]
    }
  };

  var decimalsDropBoxOptions = [        // Options for the type combobox. Each item is [typeID, "description"]
        [-1,   "Automatic"],
        [0,    "0"],
        [1,    "1"],
        [2,    "2"],
        [3,    "3"],
        [4,    "4"],
        [5,    "5"],
        [6,    "6"]
    ];
	

	var fontoptions = [
					[5, "Arial Black"],
					[4, "Comic Sans MS"],
					[3, "Courier New"],
					[2, "arial"],
					[1, "Georgia"],
					[0, "Impact"]
				];
				
				
	var sizeoptions = [
					[5, "24"],
					[4, "16"],
					[3, "12"],
					[2, "10"],
					[1, "8"],
					[0, "32"]
				];

	var unitEndOptions = [
					[0, "Back"],
					[1, "Front"]
				];				

  addOption(widgets["feedvalue_custom"], "feedid",     "feedid",  _Tr("Feed"),     _Tr("Feed value"),      []);
  addOption(widgets["feedvalue_custom"], "colour",     "colour_picker",  _Tr("Colour"),     _Tr("Colour used for display"),      []);
  addOption(widgets["feedvalue_custom"], "font",     "dropbox",  _Tr("Font"),     _Tr("Font used for Display"),      fontoptions);
  addOption(widgets["feedvalue_custom"], "units",      "value",   _Tr("Units"),    _Tr("Units to show"),   []);
  addOption(widgets["feedvalue_custom"], "decimals",   "dropbox", _Tr("Decimals"), _Tr("Decimals to show"),    decimalsDropBoxOptions);
  addOption(widgets["feedvalue_custom"], "size",   	"dropbox", _Tr("Size"), _Tr("Text size in px to use"),    sizeoptions);
  addOption(widgets["feedvalue_custom"], "unitend",  "dropbox", _Tr("Unit position"), _Tr("Where should the unit be shown"), unitEndOptions);

  return widgets;
}

function feedvalue_custom_init()
{
	setup_widget_canvas('feedvalue_custom');
}

function feedvalue_custom_draw()
{
  $('.feedvalue_custom').each(function(index)
  {
    
	var font = $(this).attr("font");
	var feedid = $(this).attr("feedid");
    if (associd[feedid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    var val = associd[feedid]['value'] * 1;
    if (val==undefined) val = 0;
    if (isNaN(val))  val = 0;
    
	var size = $(this).attr("size");
	if (size==undefined) size = 24;
	//var size = 24;
	
    var units = $(this).attr("units");
    if (units==undefined) units = '';
    
    var decimals = $(this).attr("decimals");
    if (decimals==undefined) decimals = -1;
	
	var unitend = $(this).attr("unitend");
    if (unitend==undefined) unitend = 0;
	
	{
	var id = "can-"+$(this).attr("id");

	draw_feedvalue_custom(widgetcanvas[id],
						 0,
						 0,
						 $(this).attr("font"),
						 $(this).width(),
						 $(this).height(),
						 val,
						 $(this).attr("units"),
						 $(this).attr("colour"),
						 $(this).attr("decimals"),
						 $(this).attr("size"),
						 $(this).attr("unitend")
						 );
	}
  });
}



function feedvalue_custom_slowupdate()
{
	feedvalue_custom_draw();
}

function feedvalue_custom_fastupdate()
{
	feedvalue_custom_draw();
}



function draw_feedvalue_custom(context,
				x_pos,				// these x and y coords seem unused?
				y_pos,
				font,
				width,
				height,
				val,
				units,
				colour,
				decimals,
				size,
				unitend)
{
	if (!context)
		return;

	context.clearRect(0,0,width+10,height+10); // Clear old drawing

	font = font || "arial black";    

	if (size == 0)
	{
		size = 32
		}
	
	if (size == 1)
	{
		size = 8
		}

	if (size == 2)
	{
		size = 10
		}
	
	if (size == 3)
	{
		size = 12
		}

	if (size == 4)
	{
		size = 16
		}
	
	if (size == 5)
	{
		size = 24
		}

		
	if (font == 0)
	{
		fontname = "Impact"
		}
	
	
	if (font == 1)
	{
		fontname = "Georgia"
		}
	
	if (font == 2)
	{
		fontname = "arial"
		}
	
	if (font == 3)
	{
		fontname = "Courier New"
		}
		
	if (font == 4)
	{
		fontname = "Comic Sans MS"
		}
		
	if (font == 5)
	{
		fontname = "Arial Black"
		}
	
   
    if (decimals<0)
    {

      if (val>=100)
          val = val.toFixed(0);
      else if (val>=10)
          val = val.toFixed(1);
      else if (val<=-100)
          val = val.toFixed(0);
      else if (val<=-10)
          val = val.toFixed(1);
      else
          val = val.toFixed(2);
    }
    else 
    {
      val = val.toFixed(decimals);
    }

	if (colour.indexOf("#") == -1)			// Fix missing "#" on colour if needed
		colour = "#" + colour;	
	
    //$(this).html(val+units);

	
	var half_width = width/2;
	var half_height = height/2;
	
	context.fillStyle = colour;
	context.textAlign    = "center";
	context.font = (size+"px "+ fontname);
	
	if (unitend ==0){
	context.fillText(val+units, half_width , half_height);
	}
	
	if (unitend ==1){
	context.fillText(units+val, half_width , half_height);
	}
  
}



