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
?>
  <link href="<?php echo $path; ?>Modules/dashboard/Views/js/widget.css" rel="stylesheet">
  <script type="text/javascript"><?php require "Modules/dashboard/dashboard_langjs.php"; ?></script>
  <script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgetlist.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/render.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>

  <?php require_once "Modules/dashboard/Views/loadwidgets.php"; ?>

  <div id="editicon" style="background-color:transparent; padding:5px; position:fixed;z-index:1; width: 130px; height: auto; top:63px; right: 27px;">
	<span id="dashboard-config-button">
	<a id="editbutton" style="float:right; text-align:center" class='btn-link' href='<?php echo ($path.'dashboard/edit?id='); ?><?php echo $dashboard['id']; ?>'><i class='icon-cog' ></i> <?php ?></a>
	</span>
	</div>

  <div id="page-container" style="height:<?php echo $dashboard['height']; ?>px; position:relative;">
  <div id="page"><?php echo $dashboard['content']; ?></div>
  </div>

<script type="application/javascript">
  var dashid = <?php echo $dashboard['id']; ?>;
  var path = "<?php echo $path; ?>";
  var widget = <?php echo json_encode($widgets); ?>;
  var apikey = "<?php echo get('apikey'); ?>";
  var userid = <?php echo $session['userid']; ?>;
  var redraw = 1;
  var reloadiframe = 0; // dont re-calculate vis iframe urls

  $('body').css("background-color","#<?php echo $dashboard['backgroundcolor']; ?>");

  render_widgets_init(widget); // populate widgets variable 
  render_widgets_start(); // start widgets refresh

  $(window).resize(function(){
    redraw = 1;
  });
</script>

<script type="application/javascript">
window.onload = addListeners();
var startx = 0, starty = 0;

function addListeners() {
  $("#editicon").on("mousedown", null, null, mouseDown);
  $(window).on("mouseup", null, null, mouseUp);
}

function mouseUp() {
  $(window).off("mousemove", null, editiconMove);
}

function mouseDown(e) {
  var editicon = $('#editicon');
  if (editicon[0] === e.target) {
    var position = editicon.position();
    startx = e.clientX - position.left;
    starty = e.clientY - position.top;
    $(window).on("mousemove", null, null, editiconMove);
  }
}

function editiconMove(e) {
  var left = e.clientX - startx;
  var top = e.clientY - starty;
  $('#editicon').css({position: 'absolute', left: left+'px', top: top+'px'});
}
</script>
