/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    Author: Trystan Lea: trystan.lea@googlemail.com
	Enhancements done by: Andreas Messerli firefox7518@gmail.com
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

function feedtime_widgetlist()
{
  var widgets =
  {
    "feedtime":
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
        [-1,   _Tr("Automatic")],
        [0,    "0"],
        [1,    "1"],
        [2,    "2"],
        [3,    "3"],
        [4,    "4"],
        [5,    "5"],
        [6,    "6"]
    ];

	var fontoptions = [
					[9, "Arial Black"],
					[8, "Arial Narrow"],
					[7, "sans-serif"],
					[6, "Helvetica Neue"],
					[5, "Helvetica"],
					[4, "Comic Sans MS"],
					[3, "Courier New"],
					[2, "Arial"],
					[1, "Georgia"],
					[0, "Impact"]
				];

	var fstyleoptions = [
					[2, _Tr("Normal")],
					[1, _Tr("Italic")],
					[0, _Tr("Oblique")]
				];

	var fweightoptions = [
					[1, _Tr("Bold")],
					[0, _Tr("Normal")]
				];

	var sizeoptions = [
					[14, "18"], // set size 18 to the top position to be the default value for creating new feedtime widgets otherwise size 40 would be always the default
					[13, "40"],
					[12, "36"],
					[11, "32"],
					[10, "28"],
					[9, "24"],
					[8, "22"],
					[7, "20"],
					[6, "18"],
					[5, "16"],
					[4, "14"],
					[3, "12"],
					[2, "10"],
					[1, "8"],
					[0, "6"]
				];

	var unitEndOptions = [
					[0, _Tr("Back")],
					[1, _Tr("Front")]
				];

	addOption(widgets["feedtime"], "feedid",     "feedid",  _Tr("Feed"),     _Tr("Feed value"),      []);
	addOption(widgets["feedtime"], "colour",     "colour_picker",  _Tr("Colour"),     _Tr("Colour used for display"),      []);
	addOption(widgets["feedtime"], "font",     "dropbox",  _Tr("Font"),     _Tr("Font used for display"),      fontoptions);
	addOption(widgets["feedtime"], "fstyle",   "dropbox", _Tr("Font style"), _Tr("Font style used for display"),    fstyleoptions);
	addOption(widgets["feedtime"], "fweight",   "dropbox", _Tr("Font weight"), _Tr("Font weight used for display"),    fweightoptions);
	addOption(widgets["feedtime"], "units",      "value",   _Tr("Units"),    _Tr("Units to show"),   []);
	addOption(widgets["feedtime"], "size",   	"dropbox", _Tr("Size"), _Tr("Text size in px to use"),    sizeoptions);
	addOption(widgets["feedtime"], "unitend",  "dropbox", _Tr("Unit position"), _Tr("Where should the unit be shown"), unitEndOptions);

	return widgets;
}

function draw_feedtime(context,
		x_pos,				// these x and y coords seem unused?
		y_pos,
		font,
		fstyle,
		fweight,
		width,
		height,
		val,
		units,
		colour,
		size,
		unitend)
		{
			if (!context){
			return;
			}

			context.save();
			context.clearRect(0,0,width,height); // Clear old drawing
			context.restore();
			colour = colour || "4444CC";
			unitend = unitend || "0";
			size = size || "8";
			font = font || "5";
			fstyle = fstyle || "2";
			fweight = fweight || "1";

			var fontsize;

			if (size === "0"){fontsize = 6;}
			if (size === "1"){fontsize = 8;}
			if (size === "2"){fontsize = 10;}
			if (size === "3"){fontsize = 12;}
			if (size === "4"){fontsize = 14;}
			if (size === "5"){fontsize = 16;}
			if (size === "6"){fontsize = 18;}
			if (size === "7"){fontsize = 20;}
			if (size === "8"){fontsize = 22;}
			if (size === "9"){fontsize = 24;}
			if (size === "10"){fontsize = 28;}
			if (size === "11"){fontsize = 32;}
			if (size === "12"){fontsize = 36;}
			if (size === "13"){fontsize = 40;}
			if (size === "14"){fontsize = 18;}  //default value so that not size 40 is always the default

			var fontname;

			if (font === "0"){fontname = "Impact";}
			if (font === "1"){fontname = "Georgia";}
			if (font === "2"){fontname = "Arial";}
			if (font === "3"){fontname = "Courier New";}
			if (font === "4"){fontname = "Comic Sans MS";}
			if (font === "5"){fontname = "Helvetica";}
			if (font === "6"){fontname = "Helvetica Neue";}
			if (font === "7"){fontname = "sans-serif";}
			if (font === "8"){fontname = "Arial Narrow";}
			if (font === "9"){fontname = "Arial Black";}

			var fontstyle;

			if (fstyle === "0"){fontstyle = "oblique";}
			if (fstyle === "1"){fontstyle = "italic";}
			if (fstyle === "2"){fontstyle = "normal";}

			var fontweight;

			if (fweight === "0"){fontweight = "normal";}
			if (fweight === "1"){fontweight = "bold";}

			val = val.toFixed(0);

			if (colour.indexOf("#") === -1){			// Fix missing "#" on colour if needed
				colour = "#" + colour;	

				context.fillStyle = colour;
				context.textAlign    = "center";
				context.textBaseline = "middle";
				context.font = (fontstyle+ " "+ fontweight+ " "+ fontsize+"px "+ fontname);
				}

			if (unitend ==="0")
			{
			context.fillText(val+units, width/2 , height/2);
			}

			if (unitend ==="1")
			{
			context.fillText(units+val, width/2 , height/2);
			}




}

function feedtime_draw()
{
	$(".feedtime").each(function(index)
		{

			var font = $(this).attr("font");
			var feedid = $(this).attr("feedid");
			if (associd[feedid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }

			var val = ((new Date()).getTime() / 1000  - offsetofTime - (associd[feedid]["time"] * 1));

			if (val===undefined) {val = 0;}
			if (isNaN(val))  {val = 0;}

			var size = $(this).attr("size");
			var units = $(this).attr("units");
			var decimals = $(this).attr("decimals");

			if (decimals===undefined) {decimals = -1};

			var unitend = $(this).attr("unitend");

			{
				var id = "can-"+$(this).attr("id");

				draw_feedtime(widgetcanvas[id],
					0,
					0,
					$(this).attr("font"),
					$(this).attr("fstyle"),
					$(this).attr("fweight"),
					$(this).width(),
					$(this).height(),
					val,
					$(this).attr("units"),
					$(this).attr("colour"),
					$(this).attr("size"),
					$(this).attr("unitend")
					);
			}
		});
}

function feedtime_init()
{
	setup_widget_canvas("feedtime");
}
function feedtime_slowupdate()
	{
		feedtime_draw();
	}

function feedtime_fastupdate()
	{
		feedtime_draw();
	}
