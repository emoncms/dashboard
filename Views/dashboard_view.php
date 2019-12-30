<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

global $session,$path,$dashboard_editor_icon;

load_language_files("Modules/vis/locale", "vis_messages");
load_language_files("Modules/dashboard/locale", "dashboard_messages");

if ($session['write']) $dashboard_editor_icon ='<a href="'.$path.'dashboard/edit?id='. $dashboard['id'].'"> <img src="'.$path.'Modules/dashboard/Views/icons/gear-icon-outlined.png" style="width:80%" ></a>';

if($dashboard['fullscreen']): ?>

<script>
    /**
     * if the dashboard's property "fullscreen" is true
     * hide the menus and shift the content up and left
     */
    $(function(){
        // hide menus
        $('#emoncms-navbar, #sidebar').hide();
        // shift content
        $('.content-container').css({margin:0});
        // fit footer to botttom of screen
        $('#footer').css({
            bottom: 0,
            position: 'absolute',
            width: '100vw'
        });
        // shift edit icon up
        $('#editicon').css({top: '.5rem'});
    })
</script>

<?php endif; ?>
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
  <link href="<?php echo $path; ?>Modules/dashboard/Views/js/widget.css?ver=<?php echo $js_css_version; ?>" rel="stylesheet">
  <script type="text/javascript"><?php require "Modules/dashboard/dashboard_langjs.php"; ?></script>
  <script type="text/javascript"><?php require "Modules/vis/vis_langjs.php"; ?></script>
  <script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/widgetlist.js?ver=<?php echo $js_css_version; ?>"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/Views/js/render.js?ver=<?php echo $js_css_version; ?>"></script>
  <script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js?ver=<?php echo $js_css_version; ?>"></script>
  <?php require_once "Modules/dashboard/Views/loadwidgets.php"; ?>
<h2 class="d-none"><?php echo $dashboard['name'] ?></h2>
 <div id="editicon" class="hidden-phone">
	<div id="innerbutton" style="cursor: default">
		<?php echo $dashboard_editor_icon; ?>
	</div>
</div>

  <div id="page-container" style="height:<?php echo $dashboard['height']; ?>px; position:relative;">
    <div id="page"><?php echo $dashboard['content']; ?></div>

<script type="application/javascript">
  var dashid = <?php echo $dashboard['id']; ?>;
  var widget = <?php echo json_encode($widgets); ?>;
  var apikey = "<?php echo get('apikey'); ?>";
  var userid = <?php echo $session['userid']; ?>;
  var redraw = 1;
  var reloadiframe = 0; // dont re-calculate vis iframe urls
  var _SI = []; // get a list of International System of Units (SI)

  $('body').css("background-color","#<?php echo $dashboard['backgroundcolor']; ?>");

  render_widgets_init(widget); // populate widgets variable
  render_widgets_start(); // start widgets refresh

  $(window).resize(function(){
    redraw = 1;
  });
</script>