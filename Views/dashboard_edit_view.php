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
$js_css_version = 1;

if (!$dashboard['height']) $dashboard['height'] = 400;
?>
    <script type="text/javascript"><?php require "Modules/dashboard/dashboard_langjs.php"; ?></script>
    <link href="<?php echo $path; ?>Modules/dashboard/Views/js/widget.css?ver=<?php echo $js_css_version; ?>" rel="stylesheet">
	  <link href="<?php echo $path; ?>Modules/dashboard/Views/dashboardeditor.css?ver=<?php echo $js_css_version; ?>" rel="stylesheet">

    <script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/dashboard.js?ver=<?php echo $js_css_version; ?>"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgetlist.js?ver=<?php echo $js_css_version; ?>"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/render.js?ver=<?php echo $js_css_version; ?>"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js?ver=<?php echo $js_css_version; ?>"></script>

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

<div id="toolbox" style="cursor:move; text-align: center; background-color:#ddd; padding-left:5px; padding-right:5px; padding-bottom:15px; position:fixed;z-index:1; border-radius: 5px 5px 5px 5px; border-style:groove; width: 125px; height: auto; top:55px; right: 30px;">Toolbox
	<div id="separator" style="height:1.5px; background:#717171"></div>
	<div id="Buttons" style="position:relative; top:5px; cursor:pointer">
	<span id="dashboard-config-buttons">
	<button id="dashboard-config-button" style="padding:4px; float:left; width:31px"  class='btn' style="float:right" href='#dashConfigModal' role='button' data-toggle='modal'><span><img title="Configure dashboard basic data" src='<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-gear.png'); ?>'></span></button>
	<button id="undo-button" class="btn" style="padding:4px; float:left; width:31px"><span><img title="Undo last step" src='<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-undo.png'); ?>'></span></button>
	<button id="redo-button" class="btn" style="padding:4px; float:left; width:31px"><span><img title="Redo last step" src='<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-redo.png'); ?>'></span></button>
	<button id="view-mode" class='btn' style="float:left; padding:4px; width:31px"; onclick="window.location.href='view?id=<?php echo $dashboard['id']; ?>'"><img title="Return to view mode" src='<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-view.png'); ?>' ></span></a></button>
	</span>
	<span id="when-selected">
		<button id="options-button" class="btn" style="float:left; padding:4px; width:31px" data-toggle="modal" data-target="#widget_options"><span><img title="Configure selected item" src='<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-tool.png'); ?>'></span></button>
		<button id="move-forward-button" class="btn" style="float:left; padding:4px; width:31px" ><span><img title="Move selected item in front of other items" src='<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-front.png'); ?>'></span></button>
		<button id="move-backward-button" class="btn" style="float:left; padding:4px; width:31px" ><span><img title="Move selected item to back of other items" src='<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-back.png'); ?>'></span></button>
		<button id="delete-button" class="btn btn-danger" style="float:left; padding:4px; width:31px" ><span><img title="Delete selected items" style="width:100%" src='<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-delete.png'); ?>'></span></button>
	</span>
	<span id="widget-buttons" ></span>
	<span><button id="save-dashboard" class="btn btn-success" style="float:left; padding:2px; width:125px" title="Nothing to save" ><?php echo _('Not modified'); ?></button></span>
	</div>

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
  var posx = e.clientX - startx;
  var posy = e.clientY - starty;
  if (posx < 0 ) posx = 0;
  if (posy < 50 ) posy = 50;
	
  $('#toolbox').css({position: 'absolute', left: posx+'px', top: posy+'px'});
  console.log("posx:" + posx + "posy:" + posy);
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
