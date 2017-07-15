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

// Convenience function for shoving things into the widget object
// I'm not sure about calling optionKey "optionKey", but I don't want to just use "options" (because that's what this whole function returns), and it's confusing enough as it is.
function addOption(widget, optionKey, optionType, optionName, optionHint, optionData)
{

    widget["options"    ].push(optionKey);
    widget["optionstype"].push(optionType);
    widget["optionsname"].push(optionName);
    widget["optionshint"].push(optionHint);
    widget["optionsdata"].push(optionData);


}
function bar_widgetlist()
{
    var widgets =
    {
        "bar":
        {
            "offsetx":-80,"offsety":-80,"width":160,"height":200,
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
                    [0, "Normal"],
                    [1, "Bold"]
                ];
    
    var unitEndOptions = [
                    [0, "Back"],
                    [1, "Front"]
                ];
    
    var graduationDropBoxOptions = [
                    [0, "Off"],
                    [1, "On"]
                    ];

    var displayminmaxDropBoxOptions = [
                    [0, "No"],
                    [1, "Yes"]
                    ];

    addOption(widgets["bar"], "title",          "value",            _Tr("Title"),           _Tr("Title of bar"),                                                                []);
    addOption(widgets["bar"], "colour_label",   "colour_picker",    _Tr("Label Colour"),    _Tr("Colour of title and values"),                                                  []);
    addOption(widgets["bar"], "font",           "dropbox",          _Tr("Font used"),       _Tr("Font used"),                                                                   fontoptions);
    addOption(widgets["bar"], "fstyle",         "dropbox",          _Tr("Font style"),      _Tr("Font style used for display"),                                                 fstyleoptions);
    addOption(widgets["bar"], "fweight",        "dropbox",          _Tr("Font weight"),     _Tr("Font weight used for display"),                                                fweightoptions);
    addOption(widgets["bar"], "feedid",         "feedid",           _Tr("Feed"),            _Tr("Feed value"),                                                                  []);
    addOption(widgets["bar"], "max",            "value",            _Tr("Max value"),       _Tr("Max value to show"),                                                           []);
    addOption(widgets["bar"], "scale",          "value",            _Tr("Scale"),           _Tr("Value is multiplied by scale before display. Defaults to 1"),                  []);
    addOption(widgets["bar"], "units",          "value",            _Tr("Units"),           _Tr("Units to show"),                                                               []);
    addOption(widgets["bar"], "unitend",        "dropbox",          _Tr("Unit position"),   _Tr("Where should the unit be shown"),                                              unitEndOptions);
    addOption(widgets["bar"], "decimals",       "dropbox",          _Tr("Decimals"),        _Tr("Decimals to show"),                                                            decimalsDropBoxOptions);
    addOption(widgets["bar"], "offset",         "value",            _Tr("Offset"),          _Tr("Static offset. Subtracted from value before computing"),                       []);
    addOption(widgets["bar"], "colour",         "colour_picker",    _Tr("Colour"),          _Tr("Colour to draw bar in"),                                                       []);
    addOption(widgets["bar"], "graduations",    "dropbox",          _Tr("Graduations"),     _Tr("Should the graduations be shown"),                                             graduationDropBoxOptions);
    addOption(widgets["bar"], "gradNumber",     "value",            _Tr("Grad. Num."),      _Tr("How many graduation lines to draw (only relevant if graduations are on)"),     []);
    addOption(widgets["bar"], "displayminmax",  "dropbox",          _Tr("Min / Max ?"),     _Tr("Display Min. and Max. ?"),                                                     displayminmaxDropBoxOptions);
    addOption(widgets["bar"], "minvaluefeed",   "feedid",           _Tr("Min. feed"),       _Tr("The feed for the minimum value"),                                              []);
    addOption(widgets["bar"], "maxvaluefeed",   "feedid",           _Tr("Max. feed"),       _Tr("The feed for the maximum value"),                                              []);
    addOption(widgets["bar"], "colour_minmax",   "colour_picker",    _Tr("Colour"),          _Tr("Colour for min. and max. bars"),                                               []);


    return widgets;
}


function draw_bar(context,
                x_pos,              // these x and y coords seem unused?
                y_pos,
                title,
                font,
                fstyle,
                fweight,
                width,
                height,
                raw_value,
                max_value,
                units_string,
                decimals,
                unitend,
                display_colour,
                colour_label,
                static_offset,
                graduationBool,
                graduationQuant,
                displayminmax,
                minvaluefeed,
                maxvaluefeed,
                colour_minmax
                )
{
    if (!context)
        return;

    context.clearRect(0,0,width+10,height+10); // Clear old drawing

    // if (1 * max_value) == false: 3000. Else 1 * max_value
    max_value = 1 * max_value || 3000;
    // if units_string == false: "". Else units_string
    units_string = units_string || "";
    title = title || "";
    fstyle = fstyle || "2";
    fweight = fweight || "0";
    unitend = unitend || "0";
    colour_label = colour_label || "000";
    colour_minmax = colour_minmax || "555";
    static_offset = 1*static_offset || 0;
    var display_value = raw_value;
    display_value = display_value-static_offset;

    var scaled_value = (display_value/max_value);    // Produce a scaled 0-1 value corresponding to min-max
    if (scaled_value < 0){scaled_value = 0;}

    var min_display_value = minvaluefeed
    min_display_value = min_display_value-static_offset

    var min_scaled_value = (min_display_value/max_value);    // Produce a scaled 0-1 value corresponding to min-max
    if (min_scaled_value < 0){min_scaled_value = 0;}

    var max_display_value = maxvaluefeed
    max_display_value = max_display_value-static_offset

    var max_scaled_value = (max_display_value/max_value);    // Produce a scaled 0-1 value corresponding to min-max
    if (max_scaled_value < 0){max_scaled_value = 0;}

    var size = 0;
    if (width<height){size = width/2;}
    else {size = height/2;}
    size = size - (size*0.058/2);

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
    else if (typeof(font) === "undefined") {fontname = "Arial Black";}

    var fontstyle;

    if (fstyle === "0"){fontstyle = "oblique";}
    if (fstyle === "1"){fontstyle = "italic";}
    if (fstyle === "2"){fontstyle = "normal";}

    var fontweight;

    if (fweight === "0"){fontweight = "normal";}
    if (fweight === "1"){fontweight = "bold";}

    if (graduationBool === "1")
    {
        height = height - (size/2);
        width = width - (size);
    }

    var half_width = width/2;
    var half_height = height/2;


    if (!display_value) display_value = 0;  // Clamp value so we don't draw negative values.

    context.lineWidth = 1;
    context.strokeStyle = "#000";
    var border_space = 5;
    context.strokeRect(border_space,
                       border_space,
                       width-(border_space*2),
                       height-(border_space*2));
    context.lineWidth = 0;
    
    if (display_colour.indexOf("#") === -1) {display_colour = "#" + display_colour;}  // Fix missing "#" on colour if needed

    context.fillStyle = display_colour;

    var bar_border_space = 10;
    var bar_top = ((height-bar_border_space) - (scaled_value * (height - (bar_border_space*2))));

    if (bar_top < bar_border_space)     // Clamp value so we don't overshoot the top of the bargraph.
       {bar_top = bar_border_space;}

    context.fillRect(bar_border_space,
                    bar_top,
                    width-(bar_border_space*2),
                    (height-bar_border_space) - bar_top );
    if(displayminmax==="1"){

    if (colour_minmax.indexOf("#") === -1) {colour_minmax = "#" + colour_minmax;}  // Fix missing "#" on colour if needed
    context.fillStyle = colour_minmax;
    var bar_min = ((height-bar_border_space) - (min_scaled_value * (height - (bar_border_space*2))));

    if (bar_min < bar_border_space)     // Clamp value so we don't overshoot the top of the bargraph.
       {bar_min = bar_border_space;}

    context.fillRect(bar_border_space,
                    bar_min,
                    width-(bar_border_space*2),
                    2 );

    context.fillStyle = colour_minmax;
    var bar_max = ((height-bar_border_space) - (max_scaled_value * (height - (bar_border_space*2))));

    if (bar_max < bar_border_space)     // Clamp value so we don't overshoot the top of the bargraph.
       {bar_max = bar_border_space;}

    context.fillRect(bar_border_space,
                    bar_max,
                    width-(bar_border_space*2),
                    2 );
    }

    if (colour_label.indexOf("#") === -1) {colour_label = "#" + colour_label;} // Fix missing "#" on colour if needed
    context.fillStyle = colour_label;
    
    if (graduationBool == 1)
    {
        if (graduationQuant > 0)
        {
            context.textAlign    = "start";
            context.font = (fontstyle+ " "+ fontweight+ " "+(size*0.15)+"px "+ fontname);

            var step = (height-border_space*2)/(Number(graduationQuant)+1);
            var curY;
            
            if (unitend ==="0"){context.fillText((static_offset+max_value)+units_string, width+(size*0.1), (size*0.15)+2);}
            if (unitend ==="1"){context.fillText(units_string+(static_offset+max_value), width+(size*0.1), (size*0.15)+2);}
            var divisions = Number(graduationQuant)+1;
            
            for (var y = 0; y < graduationQuant; y++)
            {
                curY = Number(((y+1)*step).toFixed(0))+0.5;  // Bin down so we're drawing in the middle of the pixel, so the line is exactly 1 px wide
                context.moveTo(border_space, curY);
                context.lineTo(width-border_space, curY);

                var unitOffset = Number(static_offset+((graduationQuant-y)*(max_value/divisions)));
                if (unitOffset < 1000)
                    {unitOffset = unitOffset.toFixed(1);}
                else
                    {unitOffset = unitOffset.toFixed(0);}
                if (unitend ==="0"){context.fillText(parseFloat(unitOffset)+units_string, width+(size*0.1), curY+(size*0.05));}
                if (unitend ==="1"){context.fillText(units_string+parseFloat(unitOffset), width+(size*0.1), curY+(size*0.05));}
            }
            if (unitend ==="0"){context.fillText(static_offset+units_string, width+(size*0.1), height-2);}
            if (unitend ==="1"){context.fillText(units_string+static_offset, width+(size*0.1), height-2);}

            context.strokeStyle = "#888";
            context.stroke();
        }
    }

        if (decimals<0){
            if (raw_value>=100) {
            raw_value = raw_value.toFixed(0);
            } else if (raw_value>=10) {
            raw_value = raw_value.toFixed(1);
            } else  {
            raw_value = raw_value.toFixed(2);
            }
        raw_value = parseFloat(raw_value);
        }
        else {
             raw_value = raw_value.toFixed(decimals);
        }


    context.fillStyle = colour_label;
    
    var unitsandval = raw_value+units_string;
    var valsize;
    if (unitsandval.length >4){ valsize = (size / (unitsandval.length+2)) * 5.5;}
	else {valsize = (size / 6) * 5.5;}
    var titlesize ;
	if (title.length >10) {titlesize = (size / (title.length+2)) * 9;}
	else {titlesize = (size / 12) * 9.5;}
    
    if (graduationBool == 1) {
    context.textAlign    = "start";
        if (title) {
            context.font = (fontstyle+ " "+ fontweight+ " "+(valsize*0.3)+"px "+ fontname);
            if (unitend ==="0"){context.fillText(raw_value+units_string, bar_border_space, height + (size*0.42))}
            if (unitend ==="1"){context.fillText(units_string+raw_value, bar_border_space, height + (size*0.42))}
            context.font = (fontstyle+ " "+ fontweight+ " "+(titlesize*0.35)+"px "+ fontname);
            context.fillText(title, bar_border_space, height + (size * 0.2));
        } else {
            context.font = (fontstyle+ " "+ fontweight+ " "+(valsize*0.45)+"px "+ fontname);
            if (unitend ==="0"){context.fillText(raw_value+units_string, bar_border_space, height + (size*0.3));}
            if (unitend ==="1"){context.fillText(units_string+raw_value, bar_border_space, height + (size*0.3));}
        }
    }
    else
    {
    context.textAlign    = "center";
        context.font = (fontstyle+ " "+ fontweight+ " "+(valsize*0.5)+"px "+ fontname);
        if (unitend ==="0"){context.fillText(raw_value+units_string, half_width, height/2 + (size*0.2));}
        if (unitend ==="1"){context.fillText(units_string+raw_value, half_width, height/2 + (size*0.2));}
        context.font = (fontstyle+ " "+ fontweight+ " "+(titlesize*0.4)+"px "+ fontname);
        context.fillText(title, half_width, height/7 + (size *0.1));
    }

    context.fillStyle = "#000";
    var spreadAngle = 32;

}
function bar_draw()
{
    $(".bar").each(function(index)
    {
        var feedid = $(this).attr("feedid");
        var minvaluefeed = $(this).attr("minvaluefeed");
        var maxvaluefeed = $(this).attr("maxvaluefeed");
        if (associd[feedid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
        var val = curve_value(feedid,dialrate).toFixed(3);
        var minval = curve_value(minvaluefeed,dialrate).toFixed(3);
        var maxval = curve_value(maxvaluefeed,dialrate).toFixed(3);
        // ONLY UPDATE ON CHANGE
        if (val != (associd[feedid]["value"] * 1).toFixed(3) || redraw == 1)
        {
            var id = "can-"+$(this).attr("id");
            var scale = 1*$(this).attr("scale") || 1;
            draw_bar(widgetcanvas[id],
                                0,
                                0,
                                $(this).attr("title"),
                                $(this).attr("font"),
                                $(this).attr("fstyle"),
                                $(this).attr("fweight"),
                                $(this).width(),
                                $(this).height(),
                                val*scale,
                                $(this).attr("max"),
                                $(this).attr("units"),
                                $(this).attr("decimals"),
                                $(this).attr("unitend"),
                                $(this).attr("colour"),
                                $(this).attr("colour_label"),
                                $(this).attr("offset"),
                                $(this).attr("graduations"),
                                $(this).attr("gradNumber"),
                                $(this).attr("displayminmax"),
                                minval*scale,
                                maxval*scale,
                                $(this).attr("colour_minmax")
                                );
        }
    });
}


function bar_init()
{
    setup_widget_canvas("bar");
}
function bar_slowupdate()
{

}

function bar_fastupdate()
{
    bar_draw();
}
