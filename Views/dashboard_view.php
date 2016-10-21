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

<div id="editicon" class="hidden-phone" style="text-align:center; position:fixed;z-index:1; width: 35px; height: 35px; top:53px; right: 0px;">
	<div id="innerbutton" style="cursor: default";>
		<a href='<?php echo ($path.'dashboard/edit?id='); ?> <?php echo $dashboard['id']; ?>'><img src="/Modules/dashboard/Views/gear-icon.png" style="width:65%" ></a>
	</div>
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
