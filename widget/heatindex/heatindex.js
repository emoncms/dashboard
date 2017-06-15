 /*
   All emon_widgets code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.

   Part of the OpenEnergyMonitor project: http://openenergymonitor.org

 */


/**
http://www.wpc.ncep.noaa.gov/html/heatindex_equation.shtml
 Compute heat index for given relative humidity RH[%] and temperature T[Deg.F].
 returns : heat index  [Deg.F]
*/
function heatindex(RH,T) {
  var hitemp = 61.0+((T-68.0)*1.2)+(RH*0.094);
  var fptemp = parseFloat(T);
  var hifinal = 0.5*(fptemp+hitemp);
  if(hifinal >= 80.0){
   var hi = -42.379 + 2.04901523 * T+ 10.14333127 * RH - 0.22475541 * T * RH - 6.83783 * (Math.pow(10, -3)) * (Math.pow(T, 2)) - 5.481717 * (Math.pow(10, -2)) * (Math.pow(RH, 2)) + 1.22874 * (Math.pow(10, -3)) * (Math.pow(T, 2)) * RH+ 8.5282 * (Math.pow(10, -4)) * T * (Math.pow(RH, 2)) - 1.99 * (Math.pow(10, -6)) * (Math.pow(T, 2)) * (Math.pow(RH,2));
   var adjust = 0;
    if (RH < 13 && T >= 80 && T< 112) {adjust = (-1)*((13-RH)/4)*Math.sqrt([17-Math.abs(T-95.0)]/17);}
    else if (RH > 85 && T >= 80 && T< 87) {adjust = ((RH-85)/10) * ((87-T)/5);}
   hi = hi + adjust;
  }
  else{
   var hi = hifinal;
   }
  return hi;
}

function addOption(widget, optionKey, optionType, optionName, optionHint, optionData)
{
    widget["options"    ].push(optionKey);
    widget["optionstype"].push(optionType);
    widget["optionsname"].push(optionName);
    widget["optionshint"].push(optionHint);
    widget["optionsdata"].push(optionData);
}

function heatindex_widgetlist()
{
    var widgets =
    {
        "heatindex":
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
					[2, "Normal"],
					[1, "Italic"],
					[0, "Oblique"]
				];
				
	var fweightoptions = [
					[1, "Bold"],
					[0, "Normal"]
				];
				
	var sizeoptions = [
					[14, "18"], // set size 18 to the top position to be the default value for creating new frostpoint widgets otherwise size 40 would be always the default
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
					[0, "Back"],
					[1, "Front"]
				];				
    
    addOption(widgets["heatindex"], "feedhumid", "feedid",  _Tr("Humidity"),    _Tr("Relative humidity in %"),          []);
    addOption(widgets["heatindex"], "feedtemp",  "feedid",  _Tr("Temperature"), _Tr("Temperature feed"),                []);
    addOption(widgets["heatindex"], "temptype",  "dropbox", _Tr("Temp unit"),   _Tr("Units of the choosen temp feed"),  tempDropBoxOptions);
    addOption(widgets["heatindex"], "decimals",  "dropbox", _Tr("Decimals"),    _Tr("Decimals to show"),                decimalsDropBoxOptions);
    addOption(widgets["heatindex"], "colour",     "colour_picker",  _Tr("Colour"),     _Tr("Colour used for display"),      []);
    addOption(widgets["heatindex"], "font",     "dropbox",  _Tr("Font"),     _Tr("Font used for display"),      fontoptions);
    addOption(widgets["heatindex"], "fstyle",   "dropbox", _Tr("Font style"), _Tr("Font style used for display"),    fstyleoptions);
    addOption(widgets["heatindex"], "fweight",   "dropbox", _Tr("Font weight"), _Tr("Font weight used for display"),    fweightoptions);
    addOption(widgets["heatindex"], "size",   	"dropbox", _Tr("Size"), _Tr("Text size in px to use"),    sizeoptions);
    addOption(widgets["heatindex"], "unitend",  "dropbox", _Tr("Unit position"), _Tr("Where should the unit be shown"), unitEndOptions);
    return widgets;
}

function draw_heatindex(context,
		x_pos,				// these x and y coords seem unused?
		y_pos,
		font,
		fstyle,
		fweight,
		width,
		height,
		val,
		unit,
		temp,
		colour,
		decimals,
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
				}
			else 
				{
					val = val.toFixed(decimals);
				}
			
		
			if (colour.indexOf("#") === -1){			// Fix missing "#" on colour if needed
				colour = "#" + colour;	

				context.fillStyle = colour;
				context.textAlign    = "center";
				context.textBaseline = "middle";
				context.font = (fontstyle+ " "+ fontweight+ " "+ fontsize+"px "+ fontname);
				}

			if (unitend ==="0")
				{
				context.fillText(val+unit, width/2 , height/2);
				}
	
			if (unitend ==="1")
				{
				context.fillText(unit+val, width/2 , height/2);
				}
			
}

function heatindex_draw()
{
  $(".heatindex").each(function(index)
  {
    var font = $(this).attr("font");
    var fstyle = $(this).attr("fstyle");
    var fweight = $(this).attr("fweight");
    var feedtemp = $(this).attr("feedtemp");
    if (associd[feedtemp] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    var temp = associd[feedtemp]["value"] * 1;
    if (temp===undefined) {temp = 0;}
    if (isNaN(temp))  {temp = 0;}
    
    var temptype = $(this).attr("temptype");
    if (temptype===undefined) {temptype = 0;}

    var feedhumid = $(this).attr("feedhumid");
    if (associd[feedhumid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    var humid = associd[feedhumid]["value"] * 1;
    if (humid===undefined) {humid = 0;}
    if (isNaN(humid))  {humid = 0;}
	
    var size = $(this).attr("size");
    var decimals = $(this).attr("decimals");
    if (decimals===undefined) {decimals = -1;}

    if (temptype ==="0") {
    temp = (temp * 9/5 + 32) ; // Celsius to Fahrenheit
	}
    var val = heatindex(humid,temp);
    if (temptype === "0") { 
    val = (val - 32) * (5 / 9); // Fahrenheit to Celsius
    }

    if (temptype === "0") {
    unit = "ºC";
    } else {
    unit = "ºF";
    }

	
    var unitend = $(this).attr("unitend");
    {
		var id = "can-"+$(this).attr("id");

		draw_heatindex(widgetcanvas[id],
			0,
			0,
			$(this).attr("font"),
			$(this).attr("fstyle"),
			$(this).attr("fweight"),
			$(this).width(),
			$(this).height(),
			val,
			unit,
			temp,
			$(this).attr("colour"),
			$(this).attr("decimals"),
			$(this).attr("size"),
			$(this).attr("unitend")
			);
		}

  });
}


function heatindex_init(){
	setup_widget_canvas("heatindex");
}

function heatindex_slowupdate() { heatindex_draw();}

function heatindex_fastupdate() { heatindex_draw(); }
