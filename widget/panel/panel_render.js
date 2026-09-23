/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    A panel is a styled box that other widgets sit on. It replaces the four
    fixed Container-* widgets, which stay for the dashboards that hold one.
    See notes/TEXT-IMAGE-PANEL.md.

    Not called container: Bootstrap owns the .container class.
 */

var panelShadowOptions = [
    ["none",        _Tr("None")],
    ["drop-small",  _Tr("Small drop shadow")],
    ["drop",        _Tr("Drop shadow")],
    ["drop-large",  _Tr("Large drop shadow")],
    ["glow",        _Tr("Glow")],
    ["glow-soft",   _Tr("Soft glow")],
    ["glow-strong", _Tr("Strong glow")],
    ["glow-border", _Tr("Glow in the border colour")]
];

// What each shadow option draws. drop is the shadow of Container-White, Grey
// and Black, glow the shadow of Container-BlueLine, see widget.css.
// glow-border is built at draw time from the border colour.
var panelShadows = {
    "none":        "none",
    "drop-small":  "0 1px 3px rgba(0, 0, 0, 0.25)",
    "drop":        "0 4px 10px -1px rgba(200, 200, 200, 0.7)",
    "drop-large":  "0 8px 24px rgba(0, 0, 0, 0.3)",
    "glow":        "0 0 2px 2px rgba(200, 200, 200, 0.7)",
    "glow-soft":   "0 0 12px 4px rgba(200, 200, 200, 0.5)",
    "glow-strong": "0 0 16px 6px rgba(120, 120, 120, 0.6)"
};

// Written on a new panel by the designer, and used for an option that is not
// set. The same look as Container-White.
var panelDefaults = {
    "colour": "ffffff",
    "opacity": "100",
    "bordercolour": "e5e5e5",
    "borderwidth": "1",
    "radius": "0",
    "shadow": "drop"
};

function panel_widgetlist()
{
    var widgets = {
        "panel":
        {
            "offsetx":0,"offsety":0,"width":200,"height":200,
            "menu":"Containers",
            "defaults": panelDefaults,
            "options":    [],
            "optionstype":[],
            "optionsname":[],
            "optionshint":[],
            "optionsdata":[]
        }
    };
    addOption(widgets["panel"], "colour",       "colour_picker", _Tr("Background"),    _Tr("Background colour"),                          []);
    addOption(widgets["panel"], "opacity",      "number",        _Tr("Opacity"),       _Tr("Background opacity in per cent, 0 is clear"), {"min":0,"max":100});
    addOption(widgets["panel"], "bordercolour", "colour_picker", _Tr("Border colour"), _Tr("Border colour"),                              []);
    addOption(widgets["panel"], "borderwidth",  "number",        _Tr("Border width"),  _Tr("Border width in px, 0 for no border"),        {"min":0,"max":20});
    addOption(widgets["panel"], "radius",       "number",        _Tr("Corner radius"), _Tr("Corner radius in px"),                        {"min":0,"max":100});
    addOption(widgets["panel"], "shadow",       "dropbox",       _Tr("Shadow"),        _Tr("Shadow around the box"),                      panelShadowOptions);
    return widgets;
}

function panel_option(config, name)
{
    var value = config[name];
    if (value === undefined || value === "") return panelDefaults[name];
    return String(value);
}

// A colour option as rgba, so the background can be see through without
// fading what sits on the panel.
function panel_rgba(hex, opacity)
{
    hex = hex.replace("#", "");
    if (hex.length === 3) hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    if (!/^[0-9a-fA-F]{6}$/.test(hex)) hex = panelDefaults["colour"];
    var r = parseInt(hex.substr(0, 2), 16);
    var g = parseInt(hex.substr(2, 2), 16);
    var b = parseInt(hex.substr(4, 2), 16);
    return "rgba(" + r + ", " + g + ", " + b + ", " + opacity + ")";
}

var panel_widget = {
    mount: function(el, config, ctx) {
        var opacity = parseInt(panel_option(config, "opacity"), 10);
        if (isNaN(opacity)) opacity = 100;
        opacity = Math.min(100, Math.max(0, opacity)) / 100;

        var width = parseInt(panel_option(config, "borderwidth"), 10);
        if (isNaN(width) || width < 0) width = 0;

        var radius = parseInt(panel_option(config, "radius"), 10);
        if (isNaN(radius) || radius < 0) radius = 0;

        var shadowName = panel_option(config, "shadow");
        var shadow = panelShadows[shadowName];
        if (shadowName === "glow-border") {
            shadow = "0 0 12px 4px " + panel_rgba(panel_option(config, "bordercolour"), 0.6);
        }
        if (shadow === undefined) shadow = panelShadows["drop"];

        // The border sits outside the geometry, the same as the Container-*
        // classes, so a converted panel is the same size as the old box.
        el.style.backgroundColor = panel_rgba(panel_option(config, "colour"), opacity);
        el.style.border = width + "px solid " + panel_rgba(panel_option(config, "bordercolour"), 1);
        el.style.borderRadius = radius + "px";
        el.style.boxShadow = shadow;
        el.style.margin = "0";
        el.style.padding = "0";

        // No feed and nothing that depends on the size.
        return {
            update: function() {},
            resize: function() {},
            destroy: function() {}
        };
    }
};
