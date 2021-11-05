/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    Author: Aymeric Thibaut
    If you have any questions please get in touch, try the forums here:
    http://openenergymonitor.org/emon/forum
 */

function feedtimestamp_widgetlist()
{
  var widgets =
  {
    "feedtimestamp":
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
					[14, "18"], // set size 18 to the top position to be the default value for creating new feedtimestamp widgets otherwise size 40 would be always the default
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

	var dateformatOptions = [
		[0, _Tr("Not displayed")],
		[1, "DD/MM/YYYY"],
		[2, "DD-MM-YYYY"],
		[3, "DD.MM.YYYY"],
		[4, "YYYY/MM/DD"],
		[5, "YYYY-MM-DD"],
		[6, "YYYY.MM.DD"],
		[7, "MM/DD/YYYY"],
		[8, "MM-DD-YYYY"],
		[9, "MM.DD.YYYY"]
	];
	
	var timeformatOptions = [
		[0, _Tr("Not displayed")],
		[1, "HH:MM:SS"],
		[2, "HH:MM"],
		[3, "H:MM:SS"],
		[4, "H:MM"],
		[5, "H:MM:SS A"],
		[6, "H:MM A"]
	];

	addOption(widgets["feedtimestamp"], "feedid",       "feedid",        _Tr("Feed"),          _Tr("Feed value"),                     []);
	addOption(widgets["feedtimestamp"], "colour",       "colour_picker", _Tr("Colour"),        _Tr("Colour used for display"),        []);
	addOption(widgets["feedtimestamp"], "font",         "dropbox",       _Tr("Font"),          _Tr("Font used for display"),          fontoptions);
	addOption(widgets["feedtimestamp"], "fstyle",       "dropbox",       _Tr("Font style"),    _Tr("Font style used for display"),    fstyleoptions);
	addOption(widgets["feedtimestamp"], "fweight",      "dropbox",       _Tr("Font weight"),   _Tr("Font weight used for display"),   fweightoptions);
	addOption(widgets["feedtimestamp"], "size",         "dropbox",       _Tr("Size"),          _Tr("Text size in px to use"),         sizeoptions);
	addOption(widgets["feedtimestamp"], "align",        "dropbox",       _Tr("Alignment"),     _Tr("Alignment"),                      alignmentOptions);
	addOption(widgets["feedtimestamp"], "dateformat",   "dropbox",       _Tr("Date format"),   _Tr("Date format"),                    dateformatOptions);
	addOption(widgets["feedtimestamp"], "timeformat",   "dropbox",       _Tr("Time format"),   _Tr("Time format"),                    timeformatOptions);
	return widgets;
}

function draw_feedtimestamp(feedvalue,
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
		align,
		unitend)
		{
			colour = colour || "4444CC";
			unitend = unitend || "0";
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

function feedtimestamp_draw()
{
	$(".feedtimestamp").each(function(index)
		{
			var feedvalue = $(this);
			var font = $(this).attr("font");
			var feedid = $(this).attr("feedid");
			if (assocfeed[feedid]!=undefined) feedid = assocfeed[feedid]; // convert tag:name to feedid
			if (associd[feedid] === undefined) { 
                // console.log("Review config for feed id of " + $(this).attr("class"));
                return; 
            }

			var timestamp = new Date(associd[feedid]["time"] * 1000)
			var day = "0" + timestamp.getDate();
			var month ="0" + (timestamp.getMonth() + 1);
			var year = timestamp.getFullYear();
			var hours = timestamp.getHours();
			var minutes = "0" + timestamp.getMinutes();
			var seconds = "0" + timestamp.getSeconds();

			var val;
			var hours2;
			var dateformat = $(this).attr("dateformat");
			var timeformat = $(this).attr("timeformat");

			if(hours==0){hours2=12; suffix="AM";}
			if(hours<12 && hours!=0){hours2=hours; suffix="AM";}
			if(hours==12){hours2=hours; suffix="PM";}
			if(hours>12){hours2=hours-12; suffix="PM";}

			if (dateformat === "0"){val = "";}
			if (dateformat === "1"){val=day.substr(-2) + "/" +month.substr(-2)+ "/" +year + " ";}
			if (dateformat === "2"){val=day.substr(-2) + "-" +month.substr(-2)+ "-" +year + " ";}
			if (dateformat === "3"){val=day.substr(-2) + "." +month.substr(-2)+ "." +year + " ";}
			if (dateformat === "4"){val=year + "/" +month.substr(-2) + "/" +day.substr(-2)+ " ";}
			if (dateformat === "5"){val=year + "-" +month.substr(-2) + "-" +day.substr(-2)+ " ";}
			if (dateformat === "6"){val=year + " " +month.substr(-2) + "." +day.substr(-2)+ " ";}
			if (dateformat === "7"){val=month.substr(-2) + "/" +day.substr(-2)+ "/" +year + " ";}
			if (dateformat === "8"){val=month.substr(-2) + "-" +day.substr(-2)+ "-" +year + " ";}
			if (dateformat === "9"){val=month.substr(-2) + "." +day.substr(-2)+ "." +year + " ";}
			
			if (timeformat === "1"){val=val + ("0"+hours).substr(-2) + ":" +minutes.substr(-2)+ ":" +seconds.substr(-2);}
			if (timeformat === "2"){val=val + ("0"+hours).substr(-2) + ":" +minutes.substr(-2);}
			if (timeformat === "3"){val=val + hours + ":" +minutes.substr(-2)+ ":" +seconds.substr(-2);}
			if (timeformat === "4"){val=val + hours + ":" +minutes.substr(-2);}
			if (timeformat === "5"){val=val + hours2 + ":" +minutes.substr(-2)+ ":" +seconds.substr(-2)+ " "+suffix;}
			if (timeformat === "6"){val=val + hours2 + ":" +minutes.substr(-2) + " "+suffix;}
			var size = $(this).attr("size");
			{
				var id = "can-"+$(this).attr("id");

				draw_feedtimestamp(feedvalue,
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
					$(this).attr("align"),
					$(this).attr("unitend")
					);
			}
		});
}

function feedtimestamp_init()
{
	$(".feedvalue").html("");
}
function feedtimestamp_slowupdate()
	{
		feedtimestamp_draw();
	}

function feedtimestamp_fastupdate()
	{
		feedtimestamp_draw();
	}
