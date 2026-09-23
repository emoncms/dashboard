/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    A text widget with options instead of html. The body is plain text, with
    b, i, u, br, a and sub allowed inside it, and everything else about how it
    looks is an option. See notes/TEXT-IMAGE-PANEL.md.

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
function text_wrapper(el)
{
    var wrapper = el.querySelector(":scope > .text-content");
    if (!wrapper) {
        wrapper = document.createElement("div");
        wrapper.className = "text-content";
        while (el.firstChild) wrapper.appendChild(el.firstChild);
        el.appendChild(wrapper);
    }
    return wrapper;
}

var text_widget = {
    mount: function(el, config, ctx) {
        var wrapper = text_wrapper(el);

        var size = config.size;
        var colour = config.colour;
        var weight = config.weight;
        var font = config.font;
        var align = config.align;
        var valign = config.valign;
        var rotate = config.rotate;

        if (size !== undefined && size !== "") el.style.fontSize = parseInt(size, 10) + "px";
        if (colour !== undefined && colour !== "") el.style.color = widget_colour(colour);
        if (weight !== undefined && weight !== "") el.style.fontWeight = weight;
        if (font !== undefined && font !== "") el.style.fontFamily = font;
        if (align !== undefined && align !== "") el.style.textAlign = align;

        var degrees = parseInt(rotate, 10);
        if (isNaN(degrees)) degrees = 0;
        if (degrees < -180) degrees = -180;
        if (degrees > 180) degrees = 180;
        wrapper.style.transform = degrees === 0 ? "" : "rotate(" + degrees + "deg)";

        var place = {"top": "start", "bottom": "end"};
        wrapper.style.alignContent = place[valign] || "center";

        // No feed and nothing that depends on the size.
        return {
            update: function() {},
            resize: function() {},
            destroy: function() {}
        };
    }
};
