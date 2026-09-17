/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    An image widget. Most stored images are an external absolute url rather
    than an upload, so the widget takes a url. See notes/TEXT-AND-IMAGE-WIDGETS.md.
 */

var imageFitOptions = [
    ["contain", _Tr("Fit inside the box")],
    ["cover",   _Tr("Fill the box and crop")],
    ["fill",    _Tr("Stretch to the box")]
];

function image_widgetlist()
{
    var widgets = {
        "image":
        {
            "offsetx":-60,"offsety":-60,"width":120,"height":120,
            "menu":"Image",
            "options":    [],
            "optionstype":[],
            "optionsname":[],
            "optionshint":[],
            "optionsdata":[]
        }
    };
    addOption(widgets["image"], "src",  "image_url",     _Tr("Image url"), _Tr("Address of the image to show"),                    []);
    addOption(widgets["image"], "alt",  "value",   _Tr("Alt text"),  _Tr("Describes the image to a reader who cannot see it"), []);
    addOption(widgets["image"], "fit",  "dropbox", _Tr("Fit"),       _Tr("How the image fills the box"),                     imageFitOptions);
    addOption(widgets["image"], "link", "url",     _Tr("Link"),      _Tr("Optional address to open when the image is clicked"), []);
    return widgets;
}

// The browser blocks an http image on an https page, and some stored images
// are http. A warning is shown instead of an empty box.
function image_warning(src)
{
    if (String(src).toLowerCase().indexOf("http://") !== 0) return "";
    if (window.location.protocol !== "https:") return "";
    return _Tr("This http image is blocked on an https page");
}

function image_init()
{
    $(".image").each(function(){
        var box = $(this);
        var src = box.attr("src");
        var alt = box.attr("alt");
        var fit = box.attr("fit");
        var link = box.attr("link");

        if (src === undefined || src === "") {
            box.html('<div class="image-empty">' + _Tr("No image url") + '</div>');
            return;
        }

        var img = $('<img>').attr("src", src).attr("alt", alt === undefined ? "" : alt);
        img.css("object-fit", fit === undefined || fit === "" ? "contain" : fit);

        var content = img;
        if (link !== undefined && link !== "") {
            content = $('<a>').attr("href", link).attr("target", "_blank")
                              .attr("rel", "noopener noreferrer").append(img);
        }

        box.empty().append(content);

        var warning = image_warning(src);
        if (warning !== "") box.append($('<div class="image-warning"></div>').text(warning));
    });
}

function image_fastupdate()
{
}

function image_slowupdate()
{
}
