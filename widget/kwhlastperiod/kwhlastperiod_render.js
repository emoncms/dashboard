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
 
var kwhlastperiod_start = []
 
 function addOption(widget, optionKey, optionType, optionName, optionHint, optionData)
{
  widget["options"    ].push(optionKey);
  widget["optionstype"].push(optionType);
  widget["optionsname"].push(optionName);
  widget["optionshint"].push(optionHint);
  widget["optionsdata"].push(optionData);
}

function kwhlastperiod_widgetlist()
{
  var widgets =
  {
    "kwhlastperiod":
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
					[14, "18"], // set size 18 to the top position to be the default value for creating new kwhlastperiod widgets otherwise size 40 would be always the default
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

  var alignmentOptions = [
    ["center", _Tr("Center")],
    ["left", _Tr("Left")],
    ["right", _Tr("Right")]
  ];
				
	addOption(widgets["kwhlastperiod"], "feedid",   "feedid",  _Tr("Feed"),     _Tr("Feed value"),      []);
	addOption(widgets["kwhlastperiod"], "offset",    "value",   _Tr("Period offset"),    _Tr("Period offset hours"),   []);
	addOption(widgets["kwhlastperiod"], "prepend",    "value",   _Tr("Prepend Text"),    _Tr("Prepend Text"),   []);
	addOption(widgets["kwhlastperiod"], "append",  "value", _Tr("Append Text"), _Tr("Append Text (Units)"), []);
	addOption(widgets["kwhlastperiod"], "decimals", "dropbox", _Tr("Decimals"), _Tr("Decimals to show"),    decimalsDropBoxOptions);
	addOption(widgets["kwhlastperiod"], "colour",   "colour_picker",  _Tr("Colour"),     _Tr("Colour used for display"),      []);
	addOption(widgets["kwhlastperiod"], "font",     "dropbox",  _Tr("Font"),     _Tr("Font used for display"),      fontoptions);
	addOption(widgets["kwhlastperiod"], "fstyle",   "dropbox", _Tr("Font style"), _Tr("Font style used for display"),    fstyleoptions);
	addOption(widgets["kwhlastperiod"], "fweight",  "dropbox", _Tr("Font weight"), _Tr("Font weight used for display"),    fweightoptions);
	addOption(widgets["kwhlastperiod"], "size",   	"dropbox", _Tr("Size"), _Tr("Text size in px to use"),    sizeoptions);
	addOption(widgets["kwhlastperiod"], "align",    "dropbox", _Tr("Alignment"), _Tr("Alignment"), alignmentOptions);
	addOption(widgets["kwhlastperiod"], "timeout",  "value",   _Tr("Timeout"),    _Tr("Timeout without feed update in seconds (empty is never)"),   []);
	addOption(widgets["kwhlastperiod"], "errormessagedisplayed",    "value",  _Tr("Error Message"),   _Tr("Error message displayed when timeout is reached"),   []);

	return widgets;
}

function draw_kwhlastperiod(kwhlastperiod,font,fstyle,fweight,width,height,prepend,val,append,colour,decimals,size,align,errorCode,errorMessage)
{
    colour = colour || "4444CC";
    size = size || "8";
    font = font || "5";
    fstyle = fstyle || "2";
    fweight = fweight || "1";
    align = align || "center";
    
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
    }
    
    kwhlastperiod.css({
        "color":colour, 
        "font":fontstyle+" "+ fontweight+" "+ fontsize+"px "+fontname,"text-align":align,
        "line-height":height+"px"
    });

    if (errorCode === "1")
    {
        kwhlastperiod.html(errorMessage);
    }
    else
    {
        kwhlastperiod.html(prepend+val+append);
    }
}

function kwhlastperiod_draw()
{
    $(".kwhlastperiod").each(function(index)
    {
        var kwhlastperiod = $(this);
        var errorMessage = $(this).attr("errormessagedisplayed");
        if (errorMessage === "" || errorMessage === undefined){            //Error Message parameter is empty
          errorMessage = "TO Error";
        }
        var errorTimeout = kwhlastperiod.attr("timeout");
        if (errorTimeout === "" || errorTimeout === undefined){           //Timeout parameter is empty
            errorTimeout = 0;
        }
        
        var font = kwhlastperiod.attr("font");
        var feedid = kwhlastperiod.attr("feedid");
        if (assocfeed[feedid]!=undefined) feedid = assocfeed[feedid]; // convert tag:name to feedid
        if (associd[feedid] === undefined) { console.log("Review config for feed id of " + kwhlastperiod.attr("class")); return; }
        
        var offset = kwhlastperiod.attr("offset")*1;
        if (offset===undefined) offset = 0;
        if (isNaN(offset)) offset = 0;

        var date = new Date();
        date.setHours(0,0,0,0);
        date.setDate(date.getDate() - 7);
        var period_time = date.getTime() + offset*3600*1000;

        if (kwhlastperiod_start[period_time]==undefined) {
            var result1 = feed.get_value(feedid, period_time);
            var result2 = feed.get_value(feedid, period_time+(3600*24*1000));
            kwhlastperiod_start[period_time] = result2[1]-result1[1];
        }

        var val = kwhlastperiod_start[period_time]
        
        if (val===undefined) {val = 0;}
        if (isNaN(val))  {val = 0;}

        var errorCode = "0";
        if (errorTimeout !== 0)
        {
            if ((time - offsetofTime - (associd[feedid]["time"] * 1)) > errorTimeout) 
            {
                errorCode = "1";
            }
        }

        var size = kwhlastperiod.attr("size");

        var decimals = kwhlastperiod.attr("decimals");

        if (decimals===undefined) {decimals = -1};


        // backwards compatibility
        var unitend = kwhlastperiod.attr("unitend");
        var units = kwhlastperiod.attr("units");
        
        // new options
        var prepend = kwhlastperiod.attr("prepend");
        var append = kwhlastperiod.attr("append");
        
        // check if new options are undefined: use old
        if (prepend==undefined && append==undefined) {
            if (unitend!=undefined && units!=undefined) {
                if (unitend ==="0") {
                    append = units;
                    prepend = ""
                } else if (unitend ==="1") {
                    prepend = units;
                    append = ""
                } 
            } else {
                prepend = ""
                append = ""
            }
        }

        draw_kwhlastperiod(
            kwhlastperiod,
            kwhlastperiod.attr("font"),
            kwhlastperiod.attr("fstyle"),
            kwhlastperiod.attr("fweight"),
            kwhlastperiod.width(),
            kwhlastperiod.height(),
            prepend,
            val,
            append,
            kwhlastperiod.attr("colour"),
            kwhlastperiod.attr("decimals"),
            kwhlastperiod.attr("size"),
            kwhlastperiod.attr("align"),
            errorCode,
            errorMessage
        );
    });
}

function kwhlastperiod_init()
{
    $(".kwhlastperiod").html("");
}
function kwhlastperiod_slowupdate()
{
	  kwhlastperiod_draw();
}
function kwhlastperiod_fastupdate()
{
	  kwhlastperiod_draw();
}
