var widgets = {

    // paragraph, heading and heading-center are kept so existing dashboards
    // still render and their options can be edited. They have no menu entry,
    // so the toolbox does not offer them. New text goes in the text widget.
    "paragraph": 
    {
        "offsetx":-50,"offsety":-30,"width":100,"height":60,
        "options":["html"],"optionstype":["html"],"optionsname":["html"],"optionshint":[_Tr("Html code to show")],"html":
        _Tr("Some text")
    },

    "heading": 
    {
        "offsetx":-50,"offsety":-30,"width":100,"height":60,
        "options":["html"],"optionstype":["html"],"optionsname":["html"],"optionshint":[_Tr("Html code to show")],"html":
        _Tr("Title")
    },

    "heading-center": 
    {
        "offsetx":-50,"offsety":-30,"width":100,"height":60,
        "options":["html"],"optionstype":["html"],"optionsname":["html"],"optionshint":[_Tr("Html code to show")],"html":
        _Tr("Title")
    },

    // The four fixed containers are kept for existing dashboards and are not
    // offered by the toolbox. New boxes are panel widgets, see
    // widget/panel/panel_render.js.
    "Container-White": 
    {
        "offsetx":0,"offsety":0,"width":200,"height":200,
        "html":""
    },

    "Container-Grey": 
    {
        "offsetx":-100,"offsety":-180,"width":200,"height":360,
        "html":""
    },

    "Container-Black": 
    {
        "offsetx":-100,"offsety":-180,"width":200,"height":360,
        "html":""
    },

    "Container-BlueLine": 
    {
        "offsetx":-100,"offsety":-180,"width":200,"height":360,
        "html":""
    }
};
