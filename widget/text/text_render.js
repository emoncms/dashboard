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

// Created by Andreas Messerli (firefox7518@gmail.com) - Swiss-solar-log.ch
 
 function addOption(widget, optionKey, optionType, optionName, optionHint, optionData)
{
  widget["options"    ].push(optionKey);
  widget["optionstype"].push(optionType);
  widget["optionsname"].push(optionName);
  widget["optionshint"].push(optionHint);
  widget["optionsdata"].push(optionData);
}

function text_widgetlist()
{
  var widgets =
  {
    "text":
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

	

  addOption(widgets["text"], "textvalue",     "value",  _Tr("Text"),     _Tr("Text Value"),      []);
  addOption(widgets["text"], "colour",     "colour_picker",  _Tr("Colour"),     _Tr("Colour used for display"),      []);
  addOption(widgets["text"], "font",     "dropbox",  _Tr("Font"),     _Tr("Font used for Display"),      fontoptions);
  addOption(widgets["text"], "size",   	"dropbox", _Tr("Size"), _Tr("Text size in px to use"),    sizeoptions);

  return widgets;
}

function text_init()
{
	setup_widget_canvas('text');
}

function text_draw()
{
  $('.text').each(function(index)
  {
    
	var text = $(this).attr("text");
	var font = $(this).attr("font");
	var textvalue = $(this).attr("textvalue");
		    
	var size = $(this).attr("size");
	if (size==undefined) size = 24;
	//var size = 24;
		
	{
	var id = "can-"+$(this).attr("id");

	draw_text(widgetcanvas[id],
						 0,
						 0,
						 $(this).width(),
						 $(this).height(),
						 $(this).attr("textvalue"),
						 $(this).attr("colour"),
						 $(this).attr("size"),
						 $(this).attr("font")
						 );
	}
  });
}



function text_slowupdate()
{
	text_draw();
}

function text_fastupdate()
{
	text_draw();
}



function draw_text(context,
				x_pos,				// these x and y coords seem unused?
				y_pos,
				width,
				height,
				textvalue,
				colour,
				size,
				font)
{
	if (!context)
		return;

	context.clearRect(0,0,width+10,height+10); // Clear old drawing
	textvalue = textvalue || "Add a text";
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
	
   
//	if (colour.indexOf("#") == -1)			// Fix missing "#" on colour if needed
	colour = "#" + colour;    
	
	var half_width = width/2;
	var half_height = height/2;
	
	context.fillStyle = colour;
	context.textAlign    = "center";
	context.font = (size+"px "+ fontname);
	context.fillText(textvalue, half_width , half_height);
}
  




