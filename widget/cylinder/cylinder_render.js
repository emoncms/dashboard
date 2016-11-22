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


function cylinder_widgetlist()
{
  var widgets = {
    "cylinder":
    {
      "offsetx":-80,"offsety":-165,"width":160,"height":330,
      "menu":"Widgets",
      "options":["topfeedid","botfeedid"],
      "optionstype":["feedid","feedid"],
      "optionsname":[_Tr("Feed Top"),_Tr("Feed Bottom")],
      "optionshint":[_Tr("Top feed value"),_Tr("Bottom feed value")]
    }
  }
  return widgets;
}

function cylinder_init()
{
  setup_widget_canvas('cylinder');
}

function cylinder_draw()
{
  $('.cylinder').each(function(index)
  {
    var feedid1 = $(this).attr("topfeedid");
    var feedid2 = $(this).attr("botfeedid");
    if ((associd[feedid1] === undefined) || (associd[feedid2] === undefined)) { console.log("Review config for feed id of " + $(this).attr("class")); return; }
    var cyl_top = associd[feedid1]['value']*1;
    var cyl_bot = associd[feedid2]['value']*1;

    var id = "can-"+$(this).attr("id");
    draw_cylinder(widgetcanvas[id],cyl_bot,cyl_top,$(this).width(),$(this).height());
  });
}

function cylinder_slowupdate()
{
  cylinder_draw();
}

function cylinder_fastupdate()
{
}

  function draw_cylinder(ctx,cyl_bot,cyl_top,width,height)
  {

    // console.log("Draw cylinder");
    if (!ctx) console.log("No CTX");
    if (!ctx) return;

    //var width = 168;
    var midx = width / 2;
    var cyl_width = width - 8;
    var cyl_left = midx - (cyl_width/2);
    var top_pos = midx;
    var bot_pos = height - 4 - (cyl_width/2);

    ctx.clearRect(0,0,width,500);
    cyl_top = cyl_top || 0;
    cyl_bot = cyl_bot || 0;
    ctx.strokeStyle = "#fff";
    ctx.lineWidth = 8;

    ctx.fillStyle = get_color(cyl_top);
    ctx.beginPath();
    ctx.arc(midx,top_pos,cyl_width/2,Math.PI,0,false);
    ctx.closePath();
    ctx.fill();

    var gradient = ctx.createLinearGradient(0, top_pos, 0, bot_pos);
    gradient.addColorStop(0, get_color(cyl_top));
    gradient.addColorStop(1, get_color(cyl_bot));
    ctx.fillStyle = gradient;
    ctx.fillRect(cyl_left, bot_pos, cyl_width, top_pos-bot_pos);

    ctx.fillStyle = get_color(cyl_bot);
    ctx.beginPath();
    ctx.arc(midx,bot_pos,cyl_width/2,0,Math.PI,false);
    ctx.closePath();
    ctx.fill();

    ctx.beginPath();
    ctx.arc(midx,top_pos,cyl_width/2,Math.PI,0,false);
    ctx.arc(midx,bot_pos,cyl_width/2,0,Math.PI,false);

    ctx.closePath();
    ctx.stroke();

    ctx.fillStyle = "#fff";
    ctx.textAlign    = "center";
    ctx.font = "bold "+((width/168)*30)+"px arial";
    ctx.fillText(cyl_top.toFixed(1)+"C",midx,top_pos);
    ctx.fillText(cyl_bot.toFixed(1)+"C",midx,bot_pos+15);
  }

  function get_color(temperature)
  {
    var red = (32+(temperature*3.95)).toFixed(0);
    var green = 40;
    var blue = (191-(temperature*3.65)).toFixed(0);
    return "rgb("+red+","+green+","+blue+")";
  }



