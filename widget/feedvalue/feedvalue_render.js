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

function feedvalue_widgetlist()
{
  var widgets =
  {
    "feedvalue":
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
					[14, "18"], // set size 18 to the top position to be the default value for creating new feedvalue widgets otherwise size 40 would be always the default
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

	addOption(widgets["feedvalue"], "feedid",     "feedid",  _Tr("Feed"),     _Tr("Feed value"),      []);
	addOption(widgets["feedvalue"], "colour",     "colour_picker",  _Tr("Colour"),     _Tr("Colour used for display"),      []);
	addOption(widgets["feedvalue"], "font",     "dropbox",  _Tr("Font"),     _Tr("Font used for display"),      fontoptions);
	addOption(widgets["feedvalue"], "fstyle",   "dropbox", _Tr("Font style"), _Tr("Font style used for display"),    fstyleoptions);
	addOption(widgets["feedvalue"], "fweight",   "dropbox", _Tr("Font weight"), _Tr("Font weight used for display"),    fweightoptions);
	addOption(widgets["feedvalue"], "units",      "value",   _Tr("Units"),    _Tr("Units to show"),   []);
	addOption(widgets["feedvalue"], "decimals",   "dropbox", _Tr("Decimals"), _Tr("Decimals to show"),    decimalsDropBoxOptions);
	addOption(widgets["feedvalue"], "size",   	"dropbox", _Tr("Size"), _Tr("Text size in px to use"),    sizeoptions);
	addOption(widgets["feedvalue"], "unitend",  "dropbox", _Tr("Unit position"), _Tr("Where should the unit be shown"), unitEndOptions);
	addOption(widgets["feedvalue"], "timeout",      "value",   _Tr("Timeout"),    _Tr("Timeout without feed update in seconds (empty is never)"),   []);

	return widgets;
}

function draw_feedvalue(feedvalue,font,fstyle,fweight,width,height,val,units,colour,decimals,size,unitend,errorCode)
{
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

    if (decimals<0)
    {
        if (val>=100){
            val = val.toFixed(0);
        }
        else if (val>=10){
            val = val.toFixed(1);
        }
        else if (val<=-100){
            val = val.toFixed(0);
        }
        else if (val<=-10){
            val = val.toFixed(1);
        }
        else {
            val = val.toFixed(2);
        }
        val = parseFloat(val);
    }
    else 
    {
        val = val.toFixed(decimals);
    }

    if (colour.indexOf("#") === -1) {			// Fix missing "#" on colour if needed
        colour = "#" + colour;
        // italic bold 12px/30px Georgia, serif
        feedvalue.css({"color":colour, "font":fontstyle+" "+ fontweight+" "+ fontsize+"px "+fontname});
        //context.fillStyle = colour;
        //context.textAlign    = "center";
        //context.textBaseline = "middle";
    }

    if (errorCode === "1")
    {
        feedvalue.html("TO Error");
    }
    else
    {
        if (unitend ==="0")
        {
            feedvalue.html(val+units);
        }

        if (unitend ==="1")
        {
            feedvalue.html(units+val);
        }
    }
}

function feedvalue_draw()
{
    $(".feedvalue").each(function(index)
    {
        var feedvalue = $(this);

        var errorTimeout = feedvalue.attr("timeout");
        if (errorTimeout === "" || errorTimeout === undefined){           //Timeout parameter is empty
            errorTimeout = 0;
        }

        var font = feedvalue.attr("font");
        var feedid = feedvalue.attr("feedid");
        if (assocfeed[feedid]!=undefined) feedid = assocfeed[feedid]; // convert tag:name to feedid
        if (associd[feedid] === undefined) { console.log("Review config for feed id of " + feedvalue.attr("class")); return; }
        var val = associd[feedid]["value"] * 1;

        if (val===undefined) {val = 0;}
        if (isNaN(val))  {val = 0;}

        var errorCode = "0";
        if (errorTimeout !== 0)
        {
            if (((new Date()).getTime() / 1000 - offsetofTime - (associd[feedid]["time"] * 1)) > errorTimeout) 
            {
                errorCode = "1";
            }
        }

        var size = feedvalue.attr("size");
        var units = feedvalue.attr("units");
        var decimals = feedvalue.attr("decimals");

        if (decimals===undefined) {decimals = -1};

        var unitend = feedvalue.attr("unitend");

        draw_feedvalue(
            feedvalue,
            feedvalue.attr("font"),
            feedvalue.attr("fstyle"),
            feedvalue.attr("fweight"),
            feedvalue.width(),
            feedvalue.height(),
            val,
            feedvalue.attr("units"),
            feedvalue.attr("colour"),
            feedvalue.attr("decimals"),
            feedvalue.attr("size"),
            feedvalue.attr("unitend"),
            errorCode
        );
    });
}

function feedvalue_init()
{
    $(".feedvalue").html("");
}
function feedvalue_slowupdate()
{
	  feedvalue_draw();
}

function feedvalue_fastupdate()
{
	  feedvalue_draw();
}
