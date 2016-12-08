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

    var fontoptions = [
                    [9, "Arial Black"],
                    [8, "arial"],
                    [7, "Arial Narrow"],
                    [6, "sans-serif"],
                    [5, "Helvetica"],
                    [4, "Comic Sans MS"],
                    [3, "Courier New"],
                    [2, "arial"],
                    [1, "Georgia"],
                    [0, "Impact"]
                ];
    
    
    var graduationDropBoxOptions = [
                    [0, "Off"],
                    [1, "On"]
                    ];

    addOption(widgets["bar"], "title",          "value",            _Tr("Title"),           _Tr("Title of bar"),                                                                []);
    addOption(widgets["bar"], "colour_label",   "colour_picker",    _Tr("Label Colour"),    _Tr("Colour of title and values"),                                                  []);
    addOption(widgets["bar"], "font",           "dropbox",          _Tr("Font used"),       _Tr("Font used"),                                                                   fontoptions);
    addOption(widgets["bar"], "feedid",         "feedid",           _Tr("Feed"),            _Tr("Feed value"),                                                                  []);
    addOption(widgets["bar"], "max",            "value",            _Tr("Max value"),       _Tr("Max value to show"),                                                           []);
    addOption(widgets["bar"], "scale",          "value",            _Tr("Scale"),           _Tr("Value is multiplied by scale before display. Defaults to 1"),                  []);
    addOption(widgets["bar"], "units",          "value",            _Tr("Units"),           _Tr("Unit type to show after value. Ex: <br>\"{Reading}{unit-string}\""),           []);
    addOption(widgets["bar"], "offset",         "value",            _Tr("Offset"),          _Tr("Static offset. Subtracted from value before computing position (default 0)"),  []);
    addOption(widgets["bar"], "colour",         "colour_picker",    _Tr("Colour"),          _Tr("Colour to draw bar in"),                                                       []);
    addOption(widgets["bar"], "graduations",    "dropbox",          _Tr("Graduations"),     _Tr("Should the graduations be shown"),                                             graduationDropBoxOptions);
    addOption(widgets["bar"], "gradNumber",     "value",            _Tr("Grad. Num."),      _Tr("How many graduation lines to draw (only relevant if graduations are on)"),     []);


    return widgets;
}

function bar_init()
{
    setup_widget_canvas('bar');
}

function bar_draw()
{
    $('.bar').each(function(index)
    {
        var feedid = $(this).attr("feedid");
        if (associd[feedid] === undefined) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
        var val = curve_value(feedid,dialrate).toFixed(3);
        // ONLY UPDATE ON CHANGE
        if (val != (associd[feedid]['value'] * 1).toFixed(3) || redraw == 1)
        {
            var id = "can-"+$(this).attr("id");
            var scale = 1*$(this).attr("scale") || 1;
            draw_bar(widgetcanvas[id],
                                0,
                                0,
                                $(this).attr("title"),
                                $(this).attr("font"),
                                $(this).width(),
                                $(this).height(),val*scale,
                                $(this).attr("max"),
                                $(this).attr("units"),
                                $(this).attr("colour"),
                                $(this).attr("colour_label"),
                                $(this).attr("offset"),
                                $(this).attr("graduations"),
                                $(this).attr("gradNumber")
                                );
        }
    });
}

function bar_slowupdate()
{

}

function bar_fastupdate()
{
    bar_draw();
}


function draw_bar(context,
                x_pos,              // these x and y coords seem unused?
                y_pos,
                title,
                font,
                width,
                height,
                raw_value,
                max_value,
                units_string,
                display_colour,
                colour_label,
                static_offset,
                graduationBool,
                graduationQuant)
{
    if (!context)
        return;

    context.clearRect(0,0,width+10,height+10); // Clear old drawing

    // if (1 * max_value) == false: 3000. Else 1 * max_value
    max_value = 1 * max_value || 3000;
    // if units_string == false: "". Else units_string
    units_string = units_string || "";
    title = title || "";
    colour_label = colour_label || "000";
    static_offset = 1*static_offset || 0;
    var display_value = raw_value
    display_value = display_value-static_offset

    var scaled_value = (display_value/max_value);    // Produce a scaled 0-1 value corresponding to min-max
    if (scaled_value < 0)
        scaled_value = 0;

    var size = 0;
    if (width<height)
        size = width/2;
    else
        size = height/2;
    
    if (font == 0){fontname = "Impact"}
    if (font == 1){fontname = "Georgia"}
    if (font == 2){fontname = "arial"}
    if (font == 3){fontname = "Courier New"}
    if (font == 4){fontname = "Comic Sans MS"}
    if (font == 5){fontname = "Helvetica"}
    if (font == 6){fontname = "sans-serif"}
    if (font == 7){fontname = "Arial Narrow"}
    if (font == 8){fontname = "Arial"}
    if (font == 9){fontname = "Arial Black"}
    else if (typeof(font) == "undefined") {fontname = "Arial Black"}
    
    if (graduationBool == 1)
    {
        height = height - (size/2)
        width = width - (size)
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
    
    if (display_colour.indexOf("#") == -1) display_colour = "#" + display_colour;  // Fix missing "#" on colour if needed

    context.fillStyle = display_colour;

    var bar_border_space = 10;
    var bar_top = ((height-bar_border_space) - (scaled_value * (height - (bar_border_space*2))));

    if (bar_top < bar_border_space)     // Clamp value so we don't overshoot the top of the bargraph.
        bar_top = bar_border_space;

    context.fillRect(bar_border_space,
                    bar_top,
                    width-(bar_border_space*2),
                    (height-bar_border_space) - bar_top );

                    
    if (colour_label.indexOf("#") == -1) colour_label = "#" + colour_label; // Fix missing "#" on colour if needed
    context.fillStyle = colour_label;
    
    if (graduationBool == 1)
    {
        if (graduationQuant > 0)
        {
            context.textAlign    = "start";
            context.font = ((size*0.20)+"px "+ fontname);

            var step = (height-border_space*2)/(Number(graduationQuant)+1);
            var curY;
            
            context.fillText((static_offset+max_value)+units_string, width+(size*0.1), (size*0.15)+2);
            var divisions = Number(graduationQuant)+1;
            
            for (var y = 0; y < graduationQuant; y++)
            {
                curY = Number(((y+1)*step).toFixed(0))+0.5;  // Bin down so we're drawing in the middle of the pixel, so the line is exactly 1 px wide
                context.moveTo(border_space, curY);
                context.lineTo(width-border_space, curY);

                var unitOffset = Number(static_offset+((graduationQuant-y)*(max_value/divisions)))
                if (unitOffset < 1000)
                    unitOffset = unitOffset.toFixed(1)
                else
                    unitOffset = unitOffset.toFixed(0)
                context.fillText(parseFloat(unitOffset)+units_string, width+(size*0.1), curY+(size*0.05));
            }
            context.fillText(static_offset+units_string, width+(size*0.1), height-2);

            context.strokeStyle = "#888";
            context.stroke();
        }
    }

    if (raw_value>=100) {
        raw_value = raw_value.toFixed(0);
    } else if (raw_value>=10) {
        raw_value = raw_value.toFixed(1);
    } else  {
        raw_value = raw_value.toFixed(2);
    }
    raw_value = parseFloat(raw_value);

    context.fillStyle = colour_label;
    context.textAlign    = "center";
    if (graduationBool == 1) {
        half_width += (size*0.20)

        if (title) {
            context.font = ((size*0.25)+"px "+ fontname);
            context.fillText(raw_value+units_string, half_width+(size*0.40), height + (size*0.48));
            context.font = ((size*0.20)+"px "+ fontname);
            context.fillText(title, half_width + (size * 0.35), height + (size * 0.2));
        } else {
            context.font = ((size*0.3)+"px "+ fontname);
            context.fillText(raw_value+units_string, half_width+(size*0.2), height + (size*0.3));
        }
    }
    else
    {
        context.font = ((size*0.5)+"px "+ fontname);
        context.fillText(raw_value+units_string, half_width, height/2 + (size*0.2));
        context.font = ((size*0.2)+"px "+ fontname);
        context.fillText(title, half_width, height/7 + (size *0.1));
    }

    context.fillStyle = "#000";
    var spreadAngle = 32;

}

