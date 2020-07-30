/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
 */
/**
 http://www.meteolafleche.com/temperature.html
 Compute humidex for given relative humidity RH[%] and temperature T[Deg.C].
 returns : Humidex
*/
function humidex(RH,T) {
   var t=7.5*T/(237.7+T);
   var et=Math.pow(10,t);
   var e=6.112*et*(RH/100);
   var hum=T+(5/9)*(e-10);
   if (hum < T)
   {
       hum=T;
   }
  return hum;
}

function humidex_widgetlist()
{
    var widgets =
    {
        "humidex":
        {
            "offsetx":-40,"offsety":-10,"width":80,"height":20,
            "menu":"Widgets",
            "options":    [],
            "optionstype":[],
            "optionsname":[],
            "optionshint":[],
            "optionsdata":[]
        }
    };
    
    var tempDropBoxOptions = [        // Options for the type combobox. Each item is [typeID, "description"]
        [0,    "ºC"],
        [1,    "ºF"]
    ];
    
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
					[14, "18"], // set size 18 to the top position to be the default value for creating new humidex widgets otherwise size 40 would be always the default
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

    addOption(widgets["humidex"], "feedhumid", "feedid",  _Tr("Humidity"),    _Tr("Relative humidity in %"),          []);
    addOption(widgets["humidex"], "feedtemp",  "feedid",  _Tr("Temperature"), _Tr("Temperature feed"),                []);
    addOption(widgets["humidex"], "temptype",  "dropbox", _Tr("Temp unit"),   _Tr("Units of the choosen temp feed"),  tempDropBoxOptions);
	addOption(widgets["humidex"], "decimals",   "dropbox", _Tr("Decimals"), _Tr("Decimals to show"),    decimalsDropBoxOptions);
	addOption(widgets["humidex"], "colour",     "colour_picker",  _Tr("Colour"),     _Tr("Colour used for display"),      []);
	addOption(widgets["humidex"], "font",     "dropbox",  _Tr("Font"),     _Tr("Font used for display"),      fontoptions);
	addOption(widgets["humidex"], "fstyle",   "dropbox", _Tr("Font style"), _Tr("Font style used for display"),    fstyleoptions);
	addOption(widgets["humidex"], "fweight",   "dropbox", _Tr("Font weight"), _Tr("Font weight used for display"),    fweightoptions);
	addOption(widgets["humidex"], "size",   	"dropbox", _Tr("Size"), _Tr("Text size in px to use"),    sizeoptions);
	addOption(widgets["humidex"], "align",    "dropbox", _Tr("Alignment"), _Tr("Alignment"), alignmentOptions);
    return widgets;
}
function draw_humidex(feedvalue,
		x_pos,				// these x and y coords seem unused?
		y_pos,
		font,
		fstyle,
		fweight,
		width,
		height,
		val,
		colour,
		decimals,
		size,
		align)
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

			if (colour.indexOf("#") === -1){			// Fix missing "#" on colour if needed
				colour = "#" + colour;	
				}

			feedvalue.css({
				"color":colour, 
				"font":fontstyle+" "+ fontweight+" "+ fontsize+"px "+fontname,"text-align":align,
				"line-height":height+"px"
			});
			feedvalue.html(val);

			
}

function humidex_draw()
{
  $(".humidex").each(function(index)
  {
    var feedvalue = $(this);
    var font = $(this).attr("font");
    var fstyle = $(this).attr("fstyle");
    var fweight = $(this).attr("fweight");
    var feedtemp = $(this).attr("feedtemp");
    if (assocfeed[feedtemp]!=undefined) feedtemp = assocfeed[feedtemp]; // convert tag:name to feedid
    if (associd[feedtemp] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    var temp = associd[feedtemp]["value"] * 1;
    if (temp===undefined) {temp = 0;}
    if (isNaN(temp))  {temp = 0;}
    
    var temptype = $(this).attr("temptype");
    if (temptype===undefined) {temptype = "0";}

    var feedhumid = $(this).attr("feedhumid");
    if (assocfeed[feedhumid]!=undefined) feedhumid = assocfeed[feedhumid]; // convert tag:name to feedid
    if (associd[feedhumid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    var humid = associd[feedhumid]["value"] * 1;
    if (humid===undefined) {humid = 0;}
    if (isNaN(humid))  {humid = 0;}

    var size = $(this).attr("size");
    var decimals = $(this).attr("decimals");
    if (decimals===undefined) {decimals = -1;}

    if (temptype ==="1") { 
    temp = (temp - 32) * (5 / 9); // Fahrenheit to celsius
    }
    var val = humidex(humid,temp);
    {
		var id = "can-"+$(this).attr("id");

		draw_humidex(feedvalue,
			0,
			0,
			$(this).attr("font"),
			$(this).attr("fstyle"),
			$(this).attr("fweight"),
			$(this).width(),
			$(this).height(),
			val,
			$(this).attr("colour"),
			$(this).attr("decimals"),
			$(this).attr("size"),
			$(this).attr("align")
			);
		}
	});
} 


function humidex_init(){
	$(".feedvalue").html("");
}

function humidex_slowupdate() { humidex_draw();}

function humidex_fastupdate() { humidex_draw();}
