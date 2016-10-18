<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

global $session,$path;

if (!$dashboard['height']) $dashboard['height'] = 400;
?>
    <script type="text/javascript"><?php require "Modules/dashboard/dashboard_langjs.php"; ?></script>
    <link href="<?php echo $path; ?>Modules/dashboard/Views/js/widget.css" rel="stylesheet">

    <script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/dashboard.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgetlist.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/render.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>

    <?php require_once "Modules/dashboard/Views/loadwidgets.php"; ?>

<div id="dashboardpage">
    <div id="widget_options" class="modal hide keyboard" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="myModalLabel"><?php echo _('Configure element'); ?></h3>
        </div>
        <div id="widget_options_body" class="modal-body"></div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
            <button id="options-save" class="btn btn-primary"><?php echo _('Save changes'); ?></button>
        </div>
    </div>
</div>

<div id="toolbox" class="toolbox" style="background-color:#ddd; padding:10px; position:fixed;z-index:1; border-radius: 15px 15px 15px 15px; border-style:groove; width: 130px; height: auto; top:110px; right: 50px;">
	<span id="dashboard-config-buttons">
	<button id="dashboard-config-button" style="float:left"  class='btn' style="float:right" href='#dashConfigModal' role='button' data-toggle='modal'><span class='icon-wrench' title= <?php echo ("Configure dashboard"); ?>></span></button>
	<a class='btn' style="float:right" href=' <?php echo ($path.'dashboard/view?id='); ?><?php echo $dashboard['id']; ?>' ><i class='icon-eye-open' title=<?php echo ("View Mode"); ?>></i></a>
	</span>
	<span id="widget-buttons"></span>
	<span id="undo-buttons">
		<button id="undo-button" class="btn" style="float:left; width:65px"><span class="icon-arrow-left"></span><?php echo _('Undo'); ?></button>
		<button id="redo-button" class="btn" style="float:left; width:65px"><i class="icon-arrow-right"></i> <?php echo _('Redo'); ?></button>
	</span>
	<span id="when-selected">
		<button id="options-button" class="btn" style="float:left; width:130px" data-toggle="modal" data-target="#widget_options"><i class="icon-wrench"></i> <?php echo _('Configure'); ?></button>
		<button id="move-forward-button" class="btn" style="float:left; width:65px"><i class="icon-arrow-up"></i> <?php echo _('Forw.'); ?></button>
		<button id="move-backward-button" class="btn" style="float:left; width:65px"><i class="icon-arrow-down"></i> <?php echo _('Backw.'); ?></button>
		<button id="delete-button" class="btn btn-danger" style="float:left; width:130px"><i class="icon-trash"></i> <?php echo _('Delete'); ?></button>
	</span>
	<span><button id="save-dashboard" class="btn btn-success" style="float:left; width:130px; bottom: 5px"><?php echo _('Not modified'); ?></button></span>
</div>


<div id="page-container" style="height:<?php echo $dashboard['height']; ?>px; background-color:#<?php echo $dashboard['backgroundcolor']; ?>; position:relative;">
    <div id="page"><?php echo $dashboard['content']; ?></div>
    <canvas id="can" width="940px" height="<?php echo $dashboard['height']; ?>px" style="position:absolute; top:0px; left:0px; margin:0; padding:0;"></canvas>
</div>

<script type="application/javascript">
window.onload = addListeners();
var x_pos = 0,
	y_pos = 0;

function addListeners() {
  document.getElementById('toolbox').addEventListener('mousedown', mouseDown, false);
  window.addEventListener('mouseup', mouseUp, false);
}

function mouseUp() {
  window.removeEventListener('mousemove', divMove, true);
}

function mouseDown(e) {
  var div = document.getElementById('toolbox');
  x_pos = e.clientX - div.offsetLeft;
  y_pos = e.clientY - div.offsetTop;
  e=e || window.event;
  pauseEvent(e);
  window.addEventListener('mousemove', divMove, true);
}

function divMove(e) {
  var div = document.getElementById('toolbox');
  div.style.position = 'absolute';
  div.style.top = (e.clientY - y_pos) + 'px';
  div.style.left = (e.clientX - x_pos) + 'px';
}

function pauseEvent(e){
    if(e.stopPropagation) e.stopPropagation();
    if(e.preventDefault) e.preventDefault();
    e.cancelBubble=true;
    e.returnValue=false;
    return false;
}
</script>

<script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/designer.js"></script>
<script type="application/javascript">
    var dashid = <?php echo $dashboard['id']; ?>;
    var path = "<?php echo $path; ?>";
    var apikey = "";
    var feedlist = feed.list();
    var userid = <?php echo $session['userid']; ?>;
    var widget = <?php echo json_encode($widgets); ?>;
    var redraw = 0;
    var reloadiframe = -1; // force iframes url to recalculate for all vis widgets 

    $('#can').width($('#dashboardpage').width());

    render_widgets_init(widget); // populate widgets variable 

    designer.canvas = "#can";
    designer.grid_size = <?php echo $dashboard['gridsize']; ?>;
    designer.widgets = widgets;
    designer.init();

    render_widgets_start(); // start widgets refresh

    var lastsavecontent = $("#page").html();

    $("#save-dashboard").click(function (){
        var currentcontent = $("#page").html();

        var success = false;
        if (currentcontent === lastsavecontent) {
            // If it's not changed, just bypass actual saving and assume success
            success = true;
        } else {
            //recalculate the height so the page_height is shrunk to the minimum but still wrapping all components
            //otherwise a user can drag a component far down then up again and a too high value will be stored to db.
            designer.page_height = 0;
            designer.scan();
            designer.draw();
            console.log("Dashboard HTML content: " + currentcontent);
            var result=dashboard.setcontent(dashid,currentcontent,designer.page_height)
            success = result.success;
        }

        if (success) {
            $("#save-dashboard").attr('class','btn btn-success').text('<?php echo _("Saved") ?>');
            lastsavecontent = currentcontent;
        } else {
            alert('ERROR: Could not save Dashboard. '+result.message);
        }
    });

    $(window).resize(function(){
        designer.draw();
    });
</script>
