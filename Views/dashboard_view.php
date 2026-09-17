<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/
defined('EMONCMS_EXEC') or die('Restricted access');

global $session,$path,$dashboard_editor_icon,$embed;

// Everything printed below is escaped for the place it is printed into. The
// columns are filtered on the way into the database as well, see Dashboard::set,
// so this is the second of the two.
$dashid = (int) $dashboard['id'];
$dashheight = (int) $dashboard['height'];
$backgroundcolor = preg_replace('/[^0-9a-fA-F]/', '', (string) $dashboard['backgroundcolor']);
$owner = !empty($owner);

load_language_files("Modules/vis/locale", "vis_messages");
load_language_files("Modules/dashboard/locale", "dashboard_messages");

if ($session['write']) $dashboard_editor_icon ='<a href="'.$path.'dashboard/edit?id='. $dashid.'"> <img src="'.$path.'Modules/dashboard/Views/icons/gear-icon-outlined.png" style="width:80%" ></a>';

if (isset($dashboard['fullscreen']) && $dashboard['fullscreen']) { $embed=1; ?>
<script>
    // shift edit icon up
    $(function(){
        $('#editicon').css({top: '.5rem'});
    })
</script>
<?php } ?>

  <style>
  #editicon{
    text-align: center;
    position: fixed;
    z-index: 1;
    width: 2rem;
    top: 3.5rem;
    right: .25rem;
  }
  </style>
  <?php load_css("Modules/dashboard/Views/js/widget.css"); ?>
  <script type="text/javascript"><?php require "Modules/dashboard/dashboard_langjs.php"; ?></script>
  <script type="text/javascript"><?php require "Modules/vis/vis_langjs.php"; ?></script>
  <?php
  load_js("Lib/flot/jquery.flot.min.js");
  load_js("Modules/dashboard/Views/js/widgetlist.js");
  load_js("Modules/dashboard/Views/js/render.js");
  load_js("Modules/feed/feed.js");
  ?>
  <?php require_once "Modules/dashboard/Views/loadwidgets.php"; ?>
<h2 class="d-none"><?php echo htmlspecialchars($dashboard['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
 <div id="editicon" class="hidden-phone">
	<div id="innerbutton" style="cursor: default">
		<?php echo $dashboard_editor_icon; ?>
	</div>
</div>

  <div id="page-container" style="height:<?php echo $dashheight; ?>px; position:relative;">
    <div id="page"><?php echo $page_html; ?></div>

<script type="application/javascript">
  var dashid = <?php echo $dashid; ?>;
  var widget = <?php echo json_encode($widgets); ?>;
  var apikey = <?php echo json_encode((string) $apikey); ?>;
  // Whether the person looking at this page owns it. The curl widget asks
  // before it sends a request from somebody else's browser, see curl_render.js.
  var dashboard_owner = <?php echo $owner ? 'true' : 'false'; ?>;
  feed.apikey = apikey;
  var redraw = 1;
  var reloadiframe = 0; // dont re-calculate vis iframe urls
  var _SI = []; // get a list of International System of Units (SI)

  public_userid = <?php echo json_encode((string) $public_userid); ?>;

  $('body').css("background-color", <?php echo json_encode('#' . $backgroundcolor); ?>);

  render_widgets_init(widget); // populate widgets variable
  render_widgets_start(); // start widgets refresh

  $(window).resize(function(){
    redraw = 1;
  });
  
  if (typeof(menu) !== 'undefined' && menu.l2_visible && $(window).width() > 576 && $(window).width() < 1150) menu.min_l2();

</script>
