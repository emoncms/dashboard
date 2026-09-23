/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
    Author: Trystan Lea: trystan.lea@googlemail.com
    If you have any questions please get in touch, try the forums here:
    http://openenergymonitor.org/emon/forum
 */

function cylinder_widgetlist()
{
    var widgets = {
        "cylinder":
        {
            "offsetx":-80,"offsety":-165,"width":160,"height":330,
            "menu":"Widgets",
            "options":[],
            "optionstype":[],
            "optionsname":[],
            "optionshint":[],
            "optionsdata":[]
        }
    };
    // Has a third choice, so it is not the helper list
    var unitDropBoxOptions = [
        [0, _Tr("Back")],
        [1, _Tr("Front")],
        [2, _Tr("No display")]
    ];
    var tempDropBoxOptions = [        // Options for the type combobox. Each item is [typeID, "description"]
        [0,    "ºC"],
        [1,    "ºF"]
    ];
    addOption(widgets["cylinder"], "topfeedid",    "feedid",  _Tr("Feed Top"),      _Tr("Top feed value"),                []);
    addOption(widgets["cylinder"], "botfeedid",    "feedid",  _Tr("Feed Bottom"),   _Tr("Bottom feed value"),             []);
    addOption(widgets["cylinder"], "temptype",     "dropbox", _Tr("Temp unit"),     _Tr("Units of the choosen temp feed"),tempDropBoxOptions);
    addOption(widgets["cylinder"], "decimals",     "dropbox", _Tr("Decimals"),      _Tr("Decimals to show"),               widget_decimals_options());
    addOption(widgets["cylinder"], "unitend",      "dropbox", _Tr("Unit position"), _Tr("Where should the unit be shown"), unitDropBoxOptions);
    return widgets;
}

function get_color(temperature,temptype)
{
    if (temptype === "1") {
        temperature = (temperature - 32) * (5 / 9); // Fahrenheit to Celsius
    }
    var red = (32+(temperature*3.95)).toFixed(0);
    var green = 40;
    var blue = (191-(temperature*3.65)).toFixed(0);
    return "rgb("+red+","+green+","+blue+")";
}

function drawCylinder(ctx,cylBot,cylTop,width,height,temptype,unitend,decimals)
{
    if (!ctx) return;

    var midx = width / 2;
    var cylWidth = width - 8;
    var cylLeft = midx - (cylWidth/2);
    var topPos = midx;
    var botPos = height - 4 - (cylWidth/2);

    ctx.clearRect(0,0,width,height);
    cylTop = cylTop || 0;
    cylBot = cylBot || 0;
    ctx.strokeStyle = "#fff";
    ctx.lineWidth = 8;

    ctx.fillStyle = get_color(cylTop,temptype);
    ctx.beginPath();
    ctx.arc(midx,topPos,cylWidth/2,Math.PI,0,false);
    ctx.closePath();
    ctx.fill();

    var gradient = ctx.createLinearGradient(0, topPos, 0, botPos);
    gradient.addColorStop(0, get_color(cylTop,temptype));
    gradient.addColorStop(1, get_color(cylBot,temptype));
    ctx.fillStyle = gradient;
    ctx.fillRect(cylLeft, botPos, cylWidth, topPos-botPos);

    ctx.fillStyle = get_color(cylBot,temptype);
    ctx.beginPath();

    ctx.arc(midx,botPos,cylWidth/2,0,Math.PI,false);
    ctx.closePath();
    ctx.fill();

    ctx.beginPath();
    ctx.arc(midx,topPos,cylWidth/2,Math.PI,0,false);
    ctx.arc(midx,botPos,cylWidth/2,0,Math.PI,false);

    ctx.closePath();
    ctx.stroke();

    ctx.fillStyle = "#fff";
    ctx.textAlign    = "center";
    ctx.font = "bold "+((width/168)*30)+"px arial";

    cylTop = widget_decimals(cylTop, decimals);
    cylBot = widget_decimals(cylBot, decimals);

    var unit;
    if (temptype === "0") {
        unit = "ºC";
    } else {
        unit = "ºF";
    }
    if (unitend ==="0"){
        ctx.fillText(cylTop+unit,midx,topPos);
        ctx.fillText(cylBot+unit,midx,botPos+15);}
    if (unitend ==="1"){
        ctx.fillText(unit+cylTop,midx,topPos);
        ctx.fillText(unit+cylBot,midx,botPos+15);}
    if (unitend ==="2"){
        ctx.fillText(cylTop,midx,topPos);
        ctx.fillText(cylBot,midx,botPos+15);}
}

var cylinder_widget = {
    mount: function(el, config, ctx){
        var canvas = ctx.canvas(el);
        var feeds = ctx.feeds;

        var draw = function(){
            var topfeed = feeds.get(config.topfeedid);
            var botfeed = feeds.get(config.botfeedid);

            var cylTop = 60;
            var cylBot = 20;

            if (topfeed) cylTop = topfeed.value*1;
            if (botfeed) cylBot = botfeed.value*1;

            var unitend = config.unitend || "0";
            var temptype= config.temptype || "0";

            drawCylinder(canvas.context,cylBot,cylTop,el.clientWidth,el.clientHeight,temptype,unitend,config.decimals || "-1");
        };

        return {
            update: function(live){ feeds = live; draw(); },
            resize: function(){ canvas.fit(); draw(); },
            destroy: function(){}
        };
    }
};
