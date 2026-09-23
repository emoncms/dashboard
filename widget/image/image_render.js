/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    An image widget. Most stored images are an external absolute url rather
    than an upload, so the widget takes a url. See notes/TEXT-IMAGE-PANEL.md.
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

var image_widget = {
    mount: function(el, config, ctx) {
        var src = config.src;
        var alt = config.alt;
        var fit = config.fit;
        var link = config.link;

        el.innerHTML = "";
        if (src === undefined || src === "") {
            var empty = document.createElement("div");
            empty.className = "image-empty";
            empty.textContent = _Tr("No image url");
            el.appendChild(empty);
        } else {
            var img = document.createElement("img");
            img.src = src;
            img.alt = alt === undefined ? "" : alt;
            img.style.objectFit = fit === undefined || fit === "" ? "contain" : fit;

            var content = img;
            if (link !== undefined && link !== "") {
                content = document.createElement("a");
                content.href = link;
                content.target = "_blank";
                content.rel = "noopener noreferrer";
                content.appendChild(img);
            }

            el.appendChild(content);

            var warning = image_warning(src);
            if (warning !== "") {
                var note = document.createElement("div");
                note.className = "image-warning";
                note.textContent = warning;
                el.appendChild(note);
            }
        }

        // No feed. The image scales with the box through object-fit.
        return {
            update: function() {},
            resize: function() {},
            destroy: function() {}
        };
    }
};
