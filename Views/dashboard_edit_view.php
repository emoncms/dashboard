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

load_language_files("Modules/vis/locale", "vis_messages");
load_language_files("Modules/dashboard/locale", "dashboard_messages");

if (!$dashboard['height']) $dashboard['height'] = 400;
if (!isset($dashboard['feedmode'])) $dashboard['feedmode'] = "feedid";
?>
    <script type="text/javascript"><?php require "Modules/dashboard/dashboard_langjs.php"; ?></script>
    <script type="text/javascript"><?php require "Modules/vis/vis_langjs.php"; ?></script>
    <link href="<?php echo $path; ?>Modules/dashboard/Views/js/widget.css?ver=<?php echo $js_css_version; ?>" rel="stylesheet">
    <link href="<?php echo $path; ?>Modules/dashboard/Views/dashboardeditor.css?ver=<?php echo $js_css_version; ?>" rel="stylesheet">

    <script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/dashboard.js?ver=<?php echo $js_css_version; ?>"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgetlist.js?ver=<?php echo $js_css_version; ?>"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/render.js?ver=<?php echo $js_css_version; ?>"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js?ver=<?php echo $js_css_version; ?>"></script>

    <?php require_once "Modules/dashboard/Views/loadwidgets.php"; ?>
    <script>
    // @see: Lib/misc/gettext.js
    function getTranslations() {
        return Object.assign({
            "Saved": "<?php echo _("Saved") ?>",
            "Could not save Dashboard": "<?php echo _("Could not save Dashboard") ?>",
            "Items Saved": "<?php echo _("Items Saved") ?>"
        }, LANG_JS);
    }
    </script>
<div id="dashboardpage">
    <div id="widget_options" class="modal hide keyboard" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="myModalLabel"><?php echo dgettext('dashboard_messages','Configure element'); ?></h3>
        </div>
        <div id="widget_options_body" class="modal-body"></div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo dgettext('dashboard_messages','Cancel'); ?></button>
            <button id="options-save" class="btn btn-primary"><?php echo dgettext('dashboard_messages','Save changes'); ?></button>
        </div>
    </div>
</div>

<div id="toolbox" style="cursor:move; text-align: center; background-color:#ddd; padding-left:5px; padding-right:5px; padding-bottom:15px; position:fixed;z-index:1; border-radius: 5px 5px 5px 5px; border-style:groove; width: 125px; height: auto; top: 5rem; right: 1rem;"><?php echo dgettext('dashboard_messages','Toolbox'); ?>
	<div id="separator" style="height:1.5px; background:#717171"></div>
	<div id="Buttons" style="position:relative; top:5px; cursor:pointer">
	<span id="dashboard-config-buttons">
	<button id="dashboard-config-button" style="padding:4px; float:left; width:31px" class="btn" href="#dashConfigModal" role="button" data-toggle="modal" title="<?php echo dgettext('dashboard_messages','Configure dashboard basic data'); ?>"><span><img src="<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-gear.png'); ?>"></span></button>
	<button id="undo-button" class="btn" style="padding:4px; float:left; width:31px" title="<?php echo dgettext('dashboard_messages','Undo last step'); ?>"><span><img src="<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-undo.png'); ?>"></span></button>
	<button id="redo-button" class="btn" style="padding:4px; float:left; width:31px" title="<?php echo dgettext('dashboard_messages','Redo last step'); ?>"><span><img src="<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-redo.png'); ?>"></span></button>
	<button id="view-mode" class="btn" style="float:left; padding:4px; width:31px" title="<?php echo dgettext('dashboard_messages','Return to view mode'); ?>" onclick="window.location.href='view?id=<?php echo $dashboard['id']; ?>'"><span><img src="<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-view.png'); ?>" ></span></button>
	</span>
	<span id="when-selected">
		<button id="options-button" class="btn" style="float:left; padding:4px; width:31px" data-toggle="modal" data-target="#widget_options" title="<?php echo dgettext('dashboard_messages','Configure selected item'); ?>"><span><img src="<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-tool.png'); ?>"></span></button>
		<button id="move-forward-button" class="btn" style="float:left; padding:4px; width:31px" title="<?php echo dgettext('dashboard_messages','Move selected item in front of other items'); ?>"><span><img src="<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-front.png'); ?>"></span></button>
		<button id="move-backward-button" class="btn" style="float:left; padding:4px; width:31px" title="<?php echo dgettext('dashboard_messages','Move selected item to back of other items'); ?>"><span><img src="<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-back.png'); ?>"></span></button>
		<button id="delete-button" class="btn btn-danger" style="float:left; padding:4px; width:31px" title="<?php echo dgettext('dashboard_messages','Delete selected items'); ?>"><span><img src="<?php echo ($path.'Modules/dashboard/Views/icons/emon-icon-delete.png'); ?>"></span></button>
	</span>
	<span id="widget-buttons" ></span>
	<span><button id="save-dashboard" class="btn btn-success" style="float:left; padding:2px; width:125px" title="<?php echo dgettext('dashboard_messages','Nothing to save'); ?>" ><?php echo dgettext('dashboard_messages','Not modified'); ?></button></span>
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
    var apikey = "";
    var feedlist = feed.list();
    var userid = <?php echo $session['userid']; ?>;
    var widget = <?php echo json_encode($widgets); ?>;
    var redraw = 0;
    var reloadiframe = -1; // force iframes url to recalculate for all vis widgets
    var _SI = designer.get_SI(); // get a list of International System of Units (SI)
    $('#can').width($('#dashboardpage').width());

    render_widgets_init(widget); // populate widgets variable

    designer.canvas = "#can";
    designer.grid_size = <?php echo $dashboard['gridsize']; ?>;
    designer.feedmode = "<?php echo $dashboard['feedmode']; ?>";
    console.log("designer.feedmode: "+designer.feedmode);
    designer.widgets = widgets;
    designer.init();

    render_widgets_start(); // start widgets refresh

    var lastsavecontent = $("#page").html();
    
    $("#save-dashboard").click(function (){
        var currentcontent = $("#page").html();
        if (currentcontent === lastsavecontent) {
            // If it's not changed, just bypass actual saving and assume success
            showSuccess();
        } else {
            //recalculate the height so the page_height is shrunk to the minimum but still wrapping all components
            //otherwise a user can drag a component far down then up again and a too high value will be stored to db.
            designer.page_height = 0;
            designer.scan();
            designer.draw();
            
            dashboard_v2.setcontent(dashid,currentcontent,designer.page_height)
              .done(showSuccess)
              .fail(showError)
        }
    });
    function showError(xhr,status) {
        throw(new Error(_("Could not save Dashboard. ") + status));
    }
    function showSuccess() {
        $("#save-dashboard").attr("class","btn btn-success").text(_("Saved"));
        $("#save-dashboard").attr("title",_("Items Saved"));
        lastsavecontent = $("#page").html();
    }

    $(window).resize(function(){
        designer.draw();
    });
</script>