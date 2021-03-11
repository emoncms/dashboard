<?php
    global $path;
    load_language_files("Modules/dashboard/locale", "dashboard_messages");
?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/dashboard/dashboard.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>
<style>
#table input[type="text"] {
     width: 88%;
}
</style>

<div class="container">
    <div id="localheading"><h2><?php echo dgettext('dashboard_messages','Dashboards'); ?></h2></div>

    <div id="nodashboards" class="alert alert-block hide">
        <h4 class="alert-heading"><?php echo dgettext('dashboard_messages','No dashboards created'); ?></h4>
        <p><?php echo dgettext('dashboard_messages','Maybe you would like to add your first dashboard using the button bellow.') ?></p>
    </div>

    <div id="table"><div>loading...</div></div>

    <div id="bottomtoolbar" class="hide"><hr>
        <button id="addnewdashboard" class="btn btn-small"><i class="icon-plus-sign" ></i>&nbsp;<?php echo _("New"); ?></button>
    </div>
</div>

<div id="myModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel"><?php echo dgettext('dashboard_messages','Delete dashboard') ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo dgettext('dashboard_messages','Deleting a dashboard is permanent.'); ?>
           <br><br>
           <?php echo dgettext('dashboard_messages','Are you sure you want to delete?'); ?>
        </p>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo dgettext('dashboard_messages','Cancel'); ?></button>
        <button id="confirmdelete" class="btn btn-primary"><?php echo dgettext('dashboard_messages','Delete permanently'); ?></button>
    </div>
</div>

<script>
  // Extend table library field types
  for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];

  table.element = "#table";

  table.fields = {
    'id':{'title':"<?php echo dgettext('dashboard_messages','Id'); ?>", 'type':"fixed"},
    'name':{'title':"<?php echo dgettext('dashboard_messages','Name'); ?>", 'type':"text"},
    'alias':{'title':"<?php echo dgettext('dashboard_messages','Alias'); ?>", 'type':"text"},
     // 'description':{'title':"<?php echo dgettext('dashboard_messages','Description'); ?>", 'type':"text"},
    'main':{'title':"<?php echo dgettext('dashboard_messages','Default'); ?>", 'type':"icon", 'trueicon':"icon-star", 'falseicon':"icon-star-empty"},
    'public':{'title':"<?php echo dgettext('dashboard_messages','Public'); ?>", 'type':"icon", 'trueicon':"icon-globe", 'falseicon':"icon-lock"},
    // 'published':{'title':"<?php echo dgettext('dashboard_messages','Bookmarked'); ?>", 'type':"icon", 'trueicon':"icon-ok", 'falseicon':"icon-remove"},

    // Actions
    'clone-action':{'title':'', 'type':"iconbasic", 'icon':'icon-random'},
    'edit-action':{'title':'', 'type':"edit"},
    'delete-action':{'title':'', 'type':"delete"},
    'draw-action':{'title':'', 'type':"iconlink", 'icon':"icon-edit", 'link':path+"dashboard/edit?id="},
    'view-action':{'title':'', 'type':"iconlink", 'link':path+"dashboard/view?id="}
  }

  table.deletedata = false;

  update();

  function update() {
    table.data = dashboard.list();
    table.draw();
    if (table.data.length != 0) {
      $("#nodashboards").hide();
      //$("#localheading").show();
      $("#bottomtoolbar").show();
    } else {
      $("#nodashboards").show();
      //$("#localheading").hide();
      $("#bottomtoolbar").show();
    };
  }

  $("#table").bind("onEdit", function(e){});

  $("#table").bind("onSave", function(e,id,fields_to_update){
    var result = dashboard.set(id,fields_to_update);
    if (result.success!=undefined) {
        if (!result.success) alert(result.message);
    }
    if (fields_to_update.main) update();
  });

  $("#table").bind("onDelete", function(e,id,row){
    $('#myModal').modal('show');
    $('#myModal').attr('feedid',id);
    $('#myModal').attr('feedrow',row);
  });

  $("#confirmdelete").click(function(){
    var id = $('#myModal').attr('feedid');
    var row = $('#myModal').attr('feedrow');
    dashboard.remove(id);
    table.remove(row);
    update();

    $('#myModal').modal('hide');
  });
  
  $("#addnewdashboard").click(function(){
    dashboard.add(); 
    update();
  });

  // UI js
  $("#table").on('click', '.icon-random', function() {
    var i = table.data[$(this).attr('row')];
    var result = dashboard.clone(i['id']);
    update();
  });
</script>
