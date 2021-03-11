<?php
    /*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
    */
    global $path;
?>

<div id="dashConfigModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="dashConfigModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="dashConfigModalLabel"><?php echo dgettext('dashboard_messages','Dashboard Configuration'); ?></h3>
    </div>
    <div class="modal-body">
        <label><?php echo dgettext('dashboard_messages','Dashboard name: '); ?></label>
        <input type="text" name="name" value="<?php echo $dashboard['name']; ?>" />
        <label><?php echo dgettext('dashboard_messages','Alias name: '); ?></label>
        <input type="text" name="alias" value="<?php echo $dashboard['alias']; ?>" />
        <label><?php echo dgettext('dashboard_messages','Background color: '); ?></label>
        <input type="color" name="backgroundcolor" value="#<?php echo $dashboard['backgroundcolor']; ?>" />
        <label><?php echo dgettext('dashboard_messages','Description: '); ?></label>
        <textarea name="description"><?php echo $dashboard['description']; ?></textarea>
        <label><?php echo dgettext('dashboard_messages','Grid size: '); ?></label>
        <input type="text" name="gridsize" value="<?php echo $dashboard['gridsize']; ?>" />


        <label><?php echo dgettext('dashboard_messages','Feed selection mode: '); ?></label>
        <i style="font-size:12px"><?php echo dgettext('dashboard_messages','Note: Reset feeds in all widgets in dashboard if changing'); ?><br><?php echo dgettext('dashboard_messages','this part way through a dashboard build'); ?></i><br>
        <select name="feedmode">
            <option value="tagname" <?php if ($dashboard['feedmode'] == "tagname") echo 'selected'; ?>><?php echo dgettext('dashboard_messages','By tag:name'); ?></option>
            <option value="feedid" <?php if ($dashboard['feedmode'] == "feedid") echo 'selected'; ?>><?php echo dgettext('dashboard_messages','By feedid'); ?></option>
        </select>

        <label class="checkbox">
            <input type="checkbox" name="main" id="chk_main" value="1" <?php if ($dashboard['main'] == true) echo 'checked'; ?> />
            <abbr title="<?php echo dgettext('dashboard_messages','Make this dashboard the first shown'); ?>"><?php echo dgettext('dashboard_messages','Main'); ?></abbr>
        </label>

        <label class="checkbox">
            <input type="checkbox" name="published" id="chk_published" value="1" <?php if ($dashboard['published'] == true) echo 'checked'; ?> />
            <abbr title="<?php echo dgettext('dashboard_messages','Activate this dashboard'); ?>"><?php echo dgettext('dashboard_messages','Published'); ?></abbr>
        </label>

        <label class="checkbox">
            <input type="checkbox" name="public" id="chk_public" value="1" <?php if ($dashboard['public'] == true) echo 'checked'; ?> />
            <abbr title="<?php echo dgettext('dashboard_messages','Anyone with the URL can see this dashboard'); ?>"><?php echo dgettext('dashboard_messages','Public'); ?></abbr>
        </label>

        <label class="checkbox">
            <input type="checkbox" name="showdescription" id="chk_showdescription" value="1" <?php if ($dashboard['showdescription'] == true) echo 'checked'; ?> />
            <abbr title="<?php echo dgettext('dashboard_messages','Shows dashboard description on mouse over dashboard name in menu project'); ?>"><?php echo dgettext('dashboard_messages','Show description'); ?></abbr>
        </label>

        <label class="checkbox">
            <input type="checkbox" name="fullscreen" id="chk_fullscreen" value="1" <?php if ($dashboard['fullscreen'] == true) echo 'checked'; ?> />
            <abbr title="<?php echo dgettext('dashboard_messages','Hide menus on dashboard. Make full screen.'); ?>"><?php echo dgettext('dashboard_messages','Hide Menus'); ?></abbr>
        </label>

        <label><?php echo dgettext('dashboard_messages','Content: '); ?></label>
        <i style="font-size:12px"><?php echo dgettext('dashboard_messages','To view content changes reload editor after saving');?></i>
        <textarea name="content" style="width:100%; height:200px;"><?php echo $dashboard['content']; ?></textarea>

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo dgettext('dashboard_messages','Close'); ?></button>
        <button id="configure-save" class="btn btn-primary"><?php echo dgettext('dashboard_messages','Save changes'); ?></button>
    </div>
</div>

<script type="application/javascript">
    var dashid = <?php echo $dashboard['id']; ?>;
    var height = <?php echo $dashboard['height']; ?>;

    $("#dashboard-config-button").click(function (){

         $("textarea[name=content]").val($("#page").html());
         $("textarea[name=content]").data('original', $("#page").html());// used to test for changes
    });

    $("#configure-save").click(function (){
        var fields = {};

        fields['name'] =$("input[name=name]").val();
        fields['alias']  = $("input[name=alias]").val();
        
        fields['description']  = $("textarea[name=description]").val();
        fields['backgroundcolor']  = $("input[name=backgroundcolor]").val().replace('#','');
        fields['feedmode']  = $("select[name=feedmode]").val();

        var gridsize = parseInt($("input[name=gridsize]").val());
        gridsize = Math.max(gridsize, 0);
        fields['gridsize'] = gridsize;

        if ($("#chk_main").is(":checked")) fields['main'] = true; else fields['main'] = false;
            if ($("#chk_public").is(":checked")) fields['public'] = true; else fields['public'] = false;
        if ($("#chk_published").is(":checked")) fields['published'] = true; else fields['published'] = false;
            if ($("#chk_showdescription").is(":checked")) fields['showdescription'] = true; else fields['showdescription'] = false;
        fields['fullscreen'] = $("#chk_fullscreen").is(":checked");

        $.ajax({
            type: "POST",
            url :  path+"dashboard/set.json",
            data : "&id="+dashid+"&fields="+encodeURIComponent(JSON.stringify(fields)),
            dataType : 'json',
            async: true,
            success : function(result) {
                if (result.success!=undefined) {
                    if (result.alias===fields.alias) {
                        $('#dashConfigModal').modal('hide');
                    } else {
                        $("input[name=alias]").val(result.alias);
                        alert("Dashboard alias altered. Too long or not URL friendly." +
                        "\nYou sent: \n  "+
                        fields.alias +
                        "\nAltered to : \n  " +
                        result.alias);
                    }
                }
            }
        });
        var contentChanged = $("textarea[name=content]").val() != $("textarea[name=content]").data('original');
        if (contentChanged) {
            $.ajax({
                type: "POST",
                url :  path+"dashboard/setcontent.json",
                data : "&id="+dashid+'&content='+encodeURIComponent($("textarea[name=content]").val())+'&height='+height,
                dataType: 'json',
                async: true,
                success : function(result) {
                    if (!result.success) {
                        alert(result.message);
                    } else {
                        $("#page").html($("textarea[name=content]").val());
                        redraw = 1;
                        reloadiframe = 0; // dont re-calculate vis iframe urls
                        $('#dashConfigModal').modal('hide');
                    }
                }
            });
        }
        

        $('#page-container').css("background-color","#"+fields['backgroundcolor']);

        designer.feedmode = fields['feedmode'];
        designer.grid_size = gridsize;
        designer.draw();
    });
</script>
