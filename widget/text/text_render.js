/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    A text widget with options instead of html. The body is plain text, with
    b, i, u, br, a and sub allowed inside it, and everything else about how it
    looks is an option. See notes/TEXT-AND-IMAGE-WIDGETS.md.

    paragraph, heading and heading-center keep their html field and stay
    editable, but are no longer offered by the toolbox.
 */

// Families the census found in use, see the font tag values in census.json.
var textFontOptions = [
    ["", _Tr("Default")],
    ["Arial", "Arial"],
    ["Arial Black", "Arial Black"],
    ["Helvetica", "Helvetica"],
    ["Verdana", "Verdana"],
    ["Georgia", "Georgia"],
    ["Courier New", "Courier New"],
    ["Comic Sans MS", "Comic Sans MS"],
    ["sans-serif", "sans-serif"]
];

var textWeightOptions = [
    ["normal", _Tr("Normal")],
    ["bold", _Tr("Bold")]
];

var textAlignOptions = [
    ["left", _Tr("Left")],
    ["center", _Tr("Center")],
    ["right", _Tr("Right")]
];

// Middle is first so it is the default when the option is not set.
var textValignOptions = [
    ["middle", _Tr("Middle")],
    ["top", _Tr("Top")],
    ["bottom", _Tr("Bottom")]
];

function text_widgetlist()
{
    var widgets = {
        "text":
        {
            "offsetx":-60,"offsety":-20,"width":120,"height":40,
            "menu":"Text",
            "options":    [],
            "optionstype":[],
            "optionsname":[],
            "optionshint":[],
            "optionsdata":[],
            "text": _Tr("Some text")
        }
    };
    addOption(widgets["text"], "text",     "text",          _Tr("Text"),   _Tr("The text to show. b, i, u, sub, a and br may be used in it."), []);
    addOption(widgets["text"], "size",     "number",        _Tr("Size"),   _Tr("Text size in px"),            {"min":6,"max":200});
    addOption(widgets["text"], "colour",   "colour_picker", _Tr("Colour"), _Tr("Text colour"),                []);
    addOption(widgets["text"], "weight",   "dropbox",       _Tr("Weight"), _Tr("Normal or bold"),             textWeightOptions);
    addOption(widgets["text"], "font",     "dropbox",       _Tr("Font"),   _Tr("Font family"),                textFontOptions);
    addOption(widgets["text"], "align",    "dropbox",       _Tr("Align"),  _Tr("Horizontal alignment"),       textAlignOptions);
    addOption(widgets["text"], "valign",   "dropbox",       _Tr("Vertical"), _Tr("Vertical alignment"),       textValignOptions);
    addOption(widgets["text"], "rotate",   "number",        _Tr("Rotate"), _Tr("Degrees, -180 to 180"),       {"min":-180,"max":180});
    return widgets;
}

// The text sits in a wrapper so a rotation turns the text and not the box.
// The wrapper is drawn here and not stored, the same as the canvas of other
// widgets, and the converter reads through it. See dashboard_convert_text_body
// in dashboard_convert.php.
function text_wrapper(box)
{
    var wrapper = box.children(".text-content");
    if (!wrapper.length) {
        box.wrapInner('<div class="text-content"></div>');
        wrapper = box.children(".text-content");
    }
    return wrapper;
}

function text_init()
{
    $(".text").each(function(){
        var box = $(this);
        var wrapper = text_wrapper(box);

        var size = box.attr("size");
        var colour = box.attr("colour");
        var weight = box.attr("weight");
        var font = box.attr("font");
        var align = box.attr("align");
        var valign = box.attr("valign");
        var rotate = box.attr("rotate");

        var css = {};
        if (size !== undefined && size !== "") css["font-size"] = parseInt(size, 10) + "px";
        if (colour !== undefined && colour !== "") css["color"] = "#" + String(colour).replace("#", "");
        if (weight !== undefined && weight !== "") css["font-weight"] = weight;
        if (font !== undefined && font !== "") css["font-family"] = font;
        if (align !== undefined && align !== "") css["text-align"] = align;
        box.css(css);

        var degrees = parseInt(rotate, 10);
        if (isNaN(degrees)) degrees = 0;
        if (degrees < -180) degrees = -180;
        if (degrees > 180) degrees = 180;
        wrapper.css("transform", degrees === 0 ? "" : "rotate(" + degrees + "deg)");

        var place = {"top": "start", "bottom": "end"};
        wrapper.css("align-content", place[valign] || "center");
    });
}

function text_fastupdate()
{
}

function text_slowupdate()
{
}
