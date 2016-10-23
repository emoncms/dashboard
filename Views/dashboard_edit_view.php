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

<div id="toolbox" style="background-color:#ddd; padding:10px; position:fixed;z-index:1; border-radius: 5px 5px 5px 5px; border-style:groove; width: 120px; height: auto; top:55px; right: 0px;">
	<span id="dashboard-config-buttons">
	<button id="dashboard-config-button" style="padding:5px; float:left; width:30px"  class='btn' style="float:right" href='#dashConfigModal' role='button' data-toggle='modal'><span><img style="" src='<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-gear.png'); ?>'></span></button>
	<button id="undo-button" class="btn" style="padding:5px; float:left; width:30px"><span><img src='<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-undo.png'); ?>'></span></button>
	<button id="redo-button" class="btn" style="padding:5px; float:left; width:30px"><span><img src='<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-redo.png'); ?>'></span></button>
	<button id="view-mode" class='btn' style="float:left; padding:5px; width:30px"><span><a href='<?php echo ($path.'dashboard/view?id='); ?><?php echo $dashboard['id']; ?>' ><img src='<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-view.png'); ?>' ></span></a></button>
	</span>
	<span id="when-selected">
		<button id="options-button" class="btn" style="float:left; padding:5px; width:30px" data-toggle="modal" data-target="#widget_options"><span><img src='<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-tool.png'); ?>'></span></button>
		<button id="move-forward-button" class="btn" style="float:left; padding:5px; width:30px" ><span><img src='<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-front.png'); ?>'></span></button>
		<button id="move-backward-button" class="btn" style="float:left; padding:5px; width:30px" ><span><img src='<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-back.png'); ?>'></span></button>
		<button id="delete-button" class="btn btn-danger" style="float:left; padding:5px; width:30px" ><span><img style="width:100%" src='<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-delete.png'); ?>'></span></button>
	</span>
	<span id="widget-buttons"></span>
	<span><button id="save-dashboard" class="btn btn-success" style="float:left; padding:5px; width:120px; bottom:5px" ><?php echo _('Not modified'); ?></button></span>
</div>

<div id="page-container" style="height:<?php echo $dashboard['height']; ?>px; background-color:#<?php echo $dashboard['backgroundcolor']; ?>; position:relative;">
    <div id="page"><?php echo $dashboard['content']; ?></div>
    <canvas id="can" width="940px" height="<?php echo $dashboard['height']; ?>px" style="position:absolute; top:0px; left:0px; margin:0; padding:0;"></canvas>
</div>

<script type="application/javascript">
window.onload = addListeners();
var startx = 0, starty = 0;

function addListeners() {
  $("#toolbox").on("touchstart mousedown", null, null, mouseDown);
  $(window).on("touchend touchcancel mouseup", null, null, mouseUp);
}

function mouseUp() {
  $(window).off("touchmove mousemove", null, toolboxMove);
}

function mouseDown(e) {
  var toolbox = $('#toolbox');
  if (toolbox[0] === e.target) {
    var position = toolbox.position();
    startx = e.clientX - position.left;
    starty = e.clientY - position.top;
    $(window).on("touchmove mousemove", null, null, toolboxMove);
	e=e || window.event;
	pauseEvent(e);
  }
}

function pauseEvent(e){
    if(e.stopPropagation) e.stopPropagation();
    if(e.preventDefault) e.preventDefault();
    e.cancelBubble=true;
    e.returnValue=false;
    return false;
}

function toolboxMove(e) {
  var left = e.clientX - startx;
  var top = e.clientY - starty;
  $('#toolbox').css({position: 'absolute', left: left+'px', top: top+'px'});
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
