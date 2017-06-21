<?php
/*
   All emon_widgets code is released under the GNU General Public License v3.
   See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
    Widget initially created by Aymeric Thibaut
 */
 ?>
<script type="application/javascript">
  var requestTime = Date.now() /1000;
  var offsetofTime = 0;
  offsetofTime = Math.round(requestTime - <?php echo time(); ?>); // Offset in s from local to server time
</script>
