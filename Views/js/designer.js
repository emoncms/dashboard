/*
 designer.js -  Licence: GNU GPL Affero, Author: Trystan Lea / Chaveiro 2024

 The dashboard designer works around the concept of html elements with fixed positions
 and specified widths and heights. Its a box model where each box can hold a widget.
 A box specifies which widget it is by its class. The properties of a widget such as
 the feedid to use is also specified in the html element:

 <div class="dial" feedid="1" style="position:absolute; top:50px; left:50px; width:200px; height:100px;" ></div>

 render.js and associated widget render scripts then inserts the dial javascript into the element specified in the designer.
 The dashboard designer creates a canvas layer above the dashboard html elements layer and uses jquery to get the mouse positions and actions that specify the box position and dimentions.
 The functions: draw_options(box_options, options_type) and widget_buttons() draw the menu and widget options interface.
*/

var selected_edges = {none : 0, left : 1, right : 2, top : 3, bottom : 4, center : 5};

var designer = {

    'feedmode':"feedid",
    'grid_size':20,
    'page_width':500,
    'page_height':500,

    'cnvs':null,
    'canvas':null,
    'designer.ctx':null,
    'widgets':null,

    'boxlist': {},
    'resize': {},

    'selected_boxes': [],
    'selected_edge': selected_edges.none,
    'edit_mode': true,
    'create': null,

    'boxi': 0,

    'box_select_mode': false,
    'boxStartX': null,
    'boxStartY': null,
    'boxWidth': null,
    'boxHeight': null,

    'mousedown': false,
    'shiftdown': false,

    'undostack': [],
    'redostack': [],
    'nextundostate': null,
    'lastundoidentifier': null,

    "init": function(){
        designer.cnvs = document.getElementById("can");
        designer.ctx = designer.cnvs.getContext("2d");

        $("#when-selected").hide();
        designer.scan();
        designer.draw();
        designer.widget_buttons();
        designer.add_events();
        designer.check_undo_state();
    },


    "snap": function(pos) {
        if (designer.grid_size > 0) {
            return Math.round(pos/designer.grid_size)*designer.grid_size;
        } else {
            return Math.round(pos);
        }
    },

    "modified": function(){
        $("#save-dashboard").attr('class','btn btn-warning').text(_Tr("Changed, press to save"));
    },

    "start_save_undo_state": function(){
        if (designer.nextundostate !== null) {
            //console.log("Imbalanced undo state save start/end!");
        }
        var newstate = $("#page").html();
        designer.nextundostate = newstate;
    },

    "end_save_undo_state": function(identifier){
        if (designer.nextundostate === null) {
            //console.log("No undo state to save!");
            return;
        }
        var currentstate = $("#page").html();
        if (currentstate === designer.nextundostate) {
            designer.cancel_save_undo_state();
        } else if (identifier && designer.lastundoidentifier === identifier) {
            // If it's the same kind of state change, then ignore this one
            designer.cancel_save_undo_state();
        } else {
            designer.undostack.push(designer.nextundostate);
            designer.redostack = [];
            designer.nextundostate = null;
            designer.lastundoidentifier = identifier;
            designer.check_undo_state();
        }
    },

    "cancel_save_undo_state": function(){
        designer.nextundostate = null;
    },

    "undo": function(){
        if (designer.undostack.length == 0) return;

        var currentstate = $("#page").html();
        var laststate = designer.undostack.pop();
        designer.lastundoidentifier = null;

        designer.redostack.push(currentstate);

        $("#page").html(laststate);
        designer.selected_boxes = [];
        designer.scan();
        designer.draw();
        designer.modified();
        designer.check_undo_state();
    },

    "redo": function(){
        if (designer.redostack.length == 0) return;

        var currentstate = $("#page").html();
        var laststate = designer.redostack.pop();
        designer.lastundoidentifier = null;

        designer.undostack.push(currentstate);

        $("#page").html(laststate);
        designer.selected_boxes = [];
        designer.scan();
        designer.draw();
        designer.modified();
        designer.check_undo_state();
    },

    "check_undo_state": function(){
        if (designer.undostack.length > 0) {
            $("#undo-button").prop("disabled", false);
        } else {
            $("#undo-button").prop("disabled", true);
        }

        if (designer.redostack.length > 0) {
            $("#redo-button").prop("disabled", false);
        } else {
            $("#redo-button").prop("disabled", true);
        }
    },

    "onbox": function(x,y){
        var box = null;

        for (z in designer.boxlist) {
        if (z){
            var width = designer.boxlist[z]["width"];
            var height = designer.boxlist[z]["height"];
            var squareSize = 8;
            if (width>75 && height>75){squareSize = 16;}
            if (width>125 && height>125){squareSize = 25;}
                if (x>designer.boxlist[z]["left"]-(squareSize/2) && x<(designer.boxlist[z]["left"]+designer.boxlist[z]["width"]+(squareSize/2)) &&
                    y>designer.boxlist[z]["top"]-(squareSize/2) && y<(designer.boxlist[z]["top"]+designer.boxlist[z]["height"]+(squareSize/2)))
                {
                    if (box === null) {
                        box = z;
                    } else {
                        var z_element = $("#"+z);
                        var box_element = $("#"+box);
                        // Only set new box if this box is higher than the existing found element
                        if (z_element.index() > box_element.index()) {
                            box = z;
                        }
                   }
            }
        }
        }
        return box;
    },

    "boxesInsideDrawBox": function(x, y, width, height) {
        var boxesInside = [];
        for (var z in designer.boxlist) {
            if (z) {
                var box = designer.boxlist[z];
                if (
                    box["left"] >= x &&
                    box["top"] >= y &&
                    box["left"] + box["width"] <= x + width &&
                    box["top"] + box["height"] <= y + height
                ) {
                    boxesInside.push(z);
                }
            }
        }
        return boxesInside;
    },

    "selectbox": function(selected_box, multiple_add = false){
        if (selected_box === null) {
            designer.selected_boxes = [];
        } else {
            var index = $.inArray(selected_box, designer.selected_boxes);
            if (index > -1) {
                if (designer.shiftdown && !multiple_add) {
                    designer.selected_boxes.splice(index, 1);
                }
            } else {
                if (designer.shiftdown || multiple_add) {
                    designer.selected_boxes.push(selected_box);
                } else {
                    designer.selected_boxes = [selected_box];
                }
            }
        }

        // save offset of all boxes related to selected_box for multiple move operations
        designer.selected_boxes.forEach(function(box) {
            if (box == selected_box) {
                designer.boxlist[selected_box]["selected_offset_mid_x"] = 0;
                designer.boxlist[selected_box]["selected_offset_mid_y"] = 0;
            } else {
                var midx = designer.boxlist[selected_box]["left"] + (designer.boxlist[selected_box]["width"]/2);
                var midy = designer.boxlist[selected_box]["top"] + (designer.boxlist[selected_box]["height"]/2);
                var midtargetx = designer.boxlist[box]["left"] + (designer.boxlist[box]["width"] /2);
                var midtargety = designer.boxlist[box]["top"] + (designer.boxlist[box]["height"] /2);
                designer.boxlist[box]["selected_offset_mid_x"] = midtargetx - midx;
                designer.boxlist[box]["selected_offset_mid_y"] = midtargety - midy;
            }
        })

        // Show/hide the buttons as appropriate
        var selected_boxes_count = designer.selected_boxes.length;
        if (selected_boxes_count > 0){
            $("#when-selected").show();
            if (selected_boxes_count == 1) {
                $("#options-button").prop("disabled", false);
            } else {
                $("#options-button").prop("disabled", true);
            }
        } else {
            $("#when-selected").hide();
        }
    },

    "scan": function(){
        var seenboxes = [];
        for (z in widgets){
            $("."+z).each(function(){
                var id = 1*($(this).attr("id"));
                if (id>designer.boxi) {designer.boxi = id;}
                seenboxes.push(id);
                designer.boxlist[id] = {
                    "top":parseInt($(this).css("top")),
                    "left":parseInt($(this).css("left")),
                    "width":parseInt($(this).css("width")),
                    "height":parseInt($(this).css("height")),
                    "styleUnitWidth": (designer.getStyle($(this),"width").indexOf("%") > -1  ? 1 : 0 ),
                    "styleUnitHeight": (designer.getStyle($(this),"height").indexOf("%") > -1  ? 1 : 0 )
                };

                if (designer.boxlist[id]["width"] < designer.grid_size) {designer.boxlist[id]["width"] = designer.grid_size;}    // Zero cant be selected se we default to minimal grid size
                if (designer.boxlist[id]["height"] < designer.grid_size) {designer.boxlist[id]["height"] = designer.grid_size;}
                
                if ((designer.boxlist[id]["top"] + designer.boxlist[id]["height"])>designer.page_height) {designer.page_height = (designer.boxlist[id]["top"] + designer.boxlist[id]["height"]);}
            });
        }

        var allboxes = Object.keys(designer.boxlist);
        $.each(allboxes, function(i, box){
            box = 1 * box;
            if ($.inArray(box, seenboxes) < 0) {
                delete designer.boxlist[box];
            }
        });
    },
    
    // given an element and a style name, returns the exact style value
    "getStyle": function(element,style){
           var stylestemp = $(element).attr("style").split(';');
           var c = '';
           for (var x = 0, l = stylestemp.length; x < l; x++) {
             c = stylestemp[x].split(':');
             if ($.trim(c[0]) == style) return $.trim(c[1]);
           }
    },
    
    "draw": function(){
        $("#page-container").css("height",designer.page_height);
        $("#can").attr("height",designer.page_height);

        designer.page_width = parseInt($('#dashboardpage').width());
        $('#can').width($('#dashboardpage').width());
        designer.cnvs.setAttribute('width', designer.page_width);
        designer.ctx = designer.cnvs.getContext("2d");

        designer.ctx.clearRect(0,0,designer.page_width,designer.page_height);
        designer.ctx.strokeRect(0,0,designer.page_width,designer.page_height);

        designer.ctx.translate(0.5, 0.5); // Move the canvas by 0.5px to fix blurring

        // Draw grid
        designer.ctx.fillStyle    = "rgba(0, 0, 0, 0.2)";

        for (var x=1; x<parseInt(designer.page_width/designer.grid_size); x++){
            for (var y=1; y<parseInt(designer.page_height/designer.grid_size); y++){
                designer.ctx.fillRect((x*designer.grid_size)-1,(y*designer.grid_size)-1,1,1);
            }
        }

        // Draw selected box points
        if (designer.selected_boxes.length > 0){
            designer.selected_boxes.forEach(function(selected_box) {
                var strokeColor = "rgba(140, 179, 255, 0.9)";
                var selectedColor = "rgba(255, 0, 0, 0.9)";

                var top = designer.boxlist[selected_box]["top"];
                var left = designer.boxlist[selected_box]["left"];
                var width = designer.boxlist[selected_box]["width"];
                var height = designer.boxlist[selected_box]["height"];
                var squareSize = 8;
                
                if (width>75 && height>75){squareSize = 16;}
                if (width>125 && height>125){squareSize = 25;}

                designer.ctx.strokeStyle = (designer.selected_edge == selected_edges.left ? selectedColor : strokeColor );
                designer.ctx.strokeRect(left-(squareSize/2),top+(height/2)-(squareSize/2),squareSize,squareSize);

                designer.ctx.strokeStyle = (designer.selected_edge == selected_edges.right ? selectedColor : strokeColor );
                designer.ctx.strokeRect(left+width-(squareSize/2),top+(height/2)-(squareSize/2),squareSize,squareSize);

                designer.ctx.strokeStyle = (designer.selected_edge == selected_edges.top ? selectedColor : strokeColor );
                designer.ctx.strokeRect(left+(width/2)-(squareSize/2),top-(squareSize/2),squareSize,squareSize);

                designer.ctx.strokeStyle = (designer.selected_edge == selected_edges.bottom ? selectedColor : strokeColor );
                designer.ctx.strokeRect(left+(width/2)-(squareSize/2),top+height-(squareSize/2),squareSize,squareSize);

                designer.ctx.strokeStyle = (designer.selected_edge == selected_edges.center ? selectedColor : strokeColor );
                designer.ctx.strokeRect(left+(width/2)-(squareSize/2),top+(height/2)-(squareSize/2),squareSize,squareSize);

                designer.ctx.strokeStyle  = strokeColor;
                designer.ctx.setLineDash([3]);
                designer.ctx.strokeRect(left,top,width,height);
            });
        }

        // Update position and dimentions of elements
        for (z in designer.boxlist) {
            if (z){
                var element = "#"+z
                $(element).css("top", designer.boxlist[z]["top"]+"px");
                $(element).css("left", designer.boxlist[z]["left"]+"px");
                if (designer.boxlist[z]["styleUnitWidth"] == 1) {
                    $(element).css("width", Math.round(designer.boxlist[z]["width"] / designer.page_width * 100) + "%");
                } else {
                    $(element).css("width", designer.boxlist[z]["width"]+"px");
                }
                if (designer.boxlist[z]["styleUnitHeight"] == 1) {
                    $(element).css("height", Math.round(designer.boxlist[z]["height"] / designer.page_height * 100) + "%");
                } else {
                    $(element).css("height", designer.boxlist[z]["height"]+"px");
                }
            }
        }
        redraw = 1;
    },

    "draw_options": function(widget){
        var box_options = widgets[widget]["options"];
        var options_type = widgets[widget]["optionstype"];
        var options_name = widgets[widget]["optionsname"];
        var optionshint = widgets[widget]["optionshint"];
        var optionsdata = widgets[widget]["optionsdata"];

        // You can only configure if there's one selected box, so just select the first
        var selected_box = designer.selected_boxes[0];

        // Build options table html
        var options_html = '<div id="box-options">';
        for (z in box_options){
            // look into the designer DOM to extract the div parameters from the selected widget.
            var val = $("#"+selected_box).attr(box_options[z]);

            if (val == undefined) val="";

            options_html += '<div class="control-group"><div class="controls">';
            options_html += '<div class="input-prepend" style="margin-bottom: 0px;">';
            options_html += '<span class="add-on" style="width:100px; text-align: right; font-size:12px;">'+options_name[z]+'</span>';

            // all feeds

            if (options_type && options_type[z] == "feedid"){
                options_html += designer.select_feed(box_options[z],feedlist,val);
            }

            else if (options_type && options_type[z] == "html"){
                val = $("#"+selected_box).html();
                options_html += "<textarea class='options' id='"+box_options[z]+"' >"+val+"</textarea>"
            }

            // Combobox for selecting options
            else if (options_type && options_type[z] == "dropbox" && optionsdata[z]){  // Check we have optionsdata before deciding to draw a combobox
                options_html += "<select id='"+box_options[z]+"' class='options' >";
                for (i in optionsdata[z])
                {
                    var selected = "";
                    if (val + "" === optionsdata[z][i][0] + "")
                        selected = "selected";
                    options_html += "<option "+selected+" value=\""+optionsdata[z][i][0]+"\">"+optionsdata[z][i][1]+"</option>";
                }
                options_html += "</select>";
            }
            // Combobox for selecting options with "other" option
            else if (options_type && options_type[z] == "dropbox_other" && optionsdata[z]){  // Check we have optionsdata before deciding to draw a combobox
                options_html += '<select id="' + box_options[z] + '_dropdown" class="options select-with-other">';
                options_html += "<option value=''></option>";
                values = [];
                for (i in optionsdata[z])
                {
                    values.push(optionsdata[z][i][0]);
                    var selected = "";
                    if (val + "" === optionsdata[z][i][0] + "") {
                        selected = "selected";
                    }
                    options_html += "<option "+selected+" value=\""+optionsdata[z][i][0]+"\">"+optionsdata[z][i][1]+"</option>";
                }
                // if saved value not in list set the 'Other' option
                other_selected = '';
                if (values.indexOf(val) === -1 && val !== '') {
                    other_selected = 'selected';
                }

                other_hidden = other_selected !== 'selected' ? 'hidden' : '';
                options_html += "<option " + other_selected + " value='__other'>"+_Tr('Other')+"</option>";
                options_html += "</select>";
                options_html += '</div>';
                options_html += '<div class="input-prepend ' + other_hidden + ' other"><span class="add-on" style="width:100px; text-align: right; font-size:12px;background: none;border: none;margin-right: 1px;">' + _Tr('Other') + '</span>'
                options_html += '<input id="' + box_options[z] + '" type="text" value="' + val + '" data-last-value="' + val + '" class="options input-is-other" style="border-radius:0 0 4px 4px;border-top:none">';
            }

            else if (options_type && options_type[z] == "colour_picker"){
                 if (optionsdata[z]!=undefined && val=="") {
                     val = optionsdata[z];
                 }
                 options_html += "<input  type='color' class='options' id='"+box_options[z]+"'  value='#"+val+"'/ >"
            }

            else if (options_type && options_type[z] == "boolean"){
                options_html += "<select class='options' id='"+box_options[z]+"'>";
                options_html += "<option value='0'" + (val == 0 ? " selected" : "") + ">"+_Tr("Off")+"</option>";
                options_html += "<option value='1'" + (val == 1 ? " selected" : "") + ">"+_Tr("On")+"</option>";
                options_html += "</select>";
            }

/*
            // Radio-buttons for selecting options
            // It was a bit confusing to use, so it's disabled until I get a change to revisit and style it better (Fake-name)
            else if (options_type && options_type[z] == "toggle" && optionsdata[z]){  // Check we have optionsdata before deciding to draw a combobox
             options_html += "<td>";
             for (i in optionsdata[z])
             {
                 var selected = "";
                 if (val == optionsdata[z][i][0])
                     selected = "checked";
                 options_html += "<input type='radio' class='options' id='"+box_options[z]+"' value='0' style='vertical-align: baseline; padding: 5px; margin: 5px;' "+selected+">"+optionsdata[z][i][1]+"<br>";
             }
            }
*/

            else{
                options_html += "<input class='options' id='"+box_options[z]+"' type='text' value='"+val+"'/ >"
            }

            options_html += '</div>';
            options_html += '<span class="help-inline"><small class="muted">'+optionshint[z]+'</small></span>';
            options_html +='</div></div>';

        }

        // Generic sizing options for all widgets (an hack so we dont add new options to all widgets)
        var selPixel = (designer.boxlist[selected_box]["styleUnitWidth"] == 0 ? "selected" : "");
        var selPercent = (designer.boxlist[selected_box]["styleUnitWidth"] == 1 ? "selected" : "");
        options_html += '<div class="control-group"><div class="controls"><div style="margin-bottom: 0px;" class="input-prepend"><span style="width:100px; text-align: right; font-size:12px;" class="add-on">'+_Tr("Width")+'</span>';
        options_html += '<select class="options" id="styleUnitWidth"><option value="0" '+selPixel+'>'+_Tr("Pixels")+'</option><option value="1" '+selPercent+'>'+_Tr("Percentage")+'</option></select>';
        options_html += '</div><span class="help-inline"><small class="muted">'+_Tr("Choose width unit")+'</small></span></div></div>';

        var selPixel = (designer.boxlist[selected_box]["styleUnitHeight"] == 0 ? "selected" : "");
        var selPercent = (designer.boxlist[selected_box]["styleUnitHeight"] == 1 ? "selected" : "");
        options_html += '<div class="control-group"><div class="controls"><div style="margin-bottom: 0px;" class="input-prepend"><span style="width:100px; text-align: right; font-size:12px;" class="add-on">'+_Tr("Height")+'</span>';
        options_html += '<select class="options" id="styleUnitHeight"><option value="0" '+selPixel+'>'+_Tr("Pixels")+'</option><option value="1" '+selPercent+'>'+_Tr("Percentage")+'</option></select>';
        options_html += '</div><span class="help-inline"><small class="muted">'+_Tr("Choose height unit")+'</small></span></div></div>';

        options_html += "</div>";

        // Fill the modal configuration window with options
        $("#widget_options_body").html(options_html);

        // Change the size of the text for items with class options - size initially set by bootstrap
        // also add height of 30 px for color inputs for Firefox
        $('input, select, textarea').css('font-size','12px');
        if (navigator.userAgent.search("Firefox") >= 0) {$("input[type='color']").css({'height':'30px', 'width':'220px'});};

        // pre-select units dropdown
        $('.select-with-other').each(function(index, elem) {
            var select = $(elem);
            var other = select.parent().next();
            var input = other.find('input');
        })
    },
    
    "select_feed": function (id, feedlist, currentval){
        var feedgroups = [];
        for (f in feedlist){
            var group = (feedlist[f].tag === null ? "NoGroup" : feedlist[f].tag);
            if (group!="Deleted") {
                if (!feedgroups[group]) feedgroups[group] = []
                feedgroups[group].push(feedlist[f]);
            }
        }
        var out = "<select id='"+id+"' class='options'>";
        for (f in feedgroups){
            out += "<optgroup label='"+f+"'>";
            for (p in feedgroups[f]) {
                var feedref = feedgroups[f][p]['id']
                if (designer.feedmode=="tagname") feedref = feedgroups[f][p]['tag']+":"+feedgroups[f][p]['name']
                var selected = "";
                if (currentval == feedref)
                    selected = "selected";
                out += "<option value='"+feedref+"' "+selected+">"+feedgroups[f][p].name+"</option>";
            }
            out += "</optgroup>";
        }
        out += "</select>";
        return out;
    },

    "widget_buttons": function(){
        var widget_html = "";
        var select = [];
        for (z in widgets){
            var menu = widgets[z]["menu"];
            if (typeof select[menu] === "undefined")
                select[menu] = "<li><a id='"+z+"' class='widget-button'>"+z+"</a></li>";
            else
                select[menu] += "<li><a id='"+z+"' class='widget-button'>"+z+"</a></li>";
        } 

        for (z in select){
            widget_html += "<div class='widgetbuttons' style='display: inline-block; '><button class='btn dropdown-toggle widgetmenu' data-toggle='dropdown' style='width:62px; padding:4px;' title='"+_Tr("Add a")+" "+z+" "+_Tr("element to the dashboard")+"'><img style='' src='../Modules/dashboard/Views/icons/"+z+".png'><span class='caret'></span></button>";
            widget_html += "<ul class='dropdown-menu scrollable-menu' style='min-width: auto; padding: 0px; text-align:left; top:initial' name='d'>"+select[z]+"</ul></div>";
        }
        $("#widget-buttons").html(widget_html);

        $(".widget-button").click(function(event) {
            designer.create = $(this).attr("id");
            designer.edit_mode = false;
        });
    },

    "add_widget": function(mx,my,type){
        designer.start_save_undo_state();
        designer.boxi++;
        var html = widgets[type]["html"];
        if (html == undefined) html = "";
        $("#page").append('<div id="'+designer.boxi+'" class="'+type+'" style="position:absolute; margin: 0; top:'+designer.snap(my+widgets[type]["offsety"])+'px; left:'+designer.snap(mx+widgets[type]["offsetx"])+'px; width:'+widgets[type]["width"]+'px; height:'+widgets[type]["height"]+'px;" >'+html+'</div>');

        designer.end_save_undo_state();
        designer.selected_boxes = [designer.boxi];
        designer.scan();
        designer.draw();
        designer.modified();
        designer.edit_mode = true;
    },
    
    "delete_selected_boxes": function(){
        if (designer.selected_boxes.length > 0) {
            designer.start_save_undo_state();
            designer.selected_boxes.forEach(function(selected_box) {
                delete designer.boxlist[selected_box];
                $("#"+selected_box).remove();
            });
            designer.selected_boxes = [];
            designer.draw();
            designer.modified();
            $("#when-selected").hide();
        }
    },

    get_SI: function() {
        // return array of common units in format [value, label]
        var arr = [], json = [];
        $.ajax({
            url: "../Lib/units.php",
            async: false,
            success: function(units){
                json = units;
            }
        });
        for(j in json) {
            var unit = json[j];
            arr.push([unit.short, unit.long +' ('+ unit.short + ')'])
        }
        return arr;
    },

    "get_unified_event": function(e){
        var coors;
        if (e.originalEvent.touches){  // touch
            coors = e.originalEvent.touches[0];
        } else {                        // mouse
            coors = e;
        }
        return coors;
    },

    "handle_arrow_key_event": function(e){
        if (designer.selected_boxes.length == 0) return false;

        var targetTagName = e.target.tagName.toLowerCase();
        if (targetTagName === 'input' || targetTagName === 'textarea') return false;

        designer.start_save_undo_state();

        var left_shift = 0;
        var top_shift = 0;
        var snap_amount = Math.max(designer.grid_size, 1);
        switch(e.keyCode) {
            case 37: // Left
              left_shift = -snap_amount;
              break;
            case 38: // Up
              top_shift = -snap_amount;
              break;
            case 39: // Right
              left_shift = snap_amount;
              break;
            case 40: // Down
              top_shift = snap_amount;
              break;
            default:
              // Unhandled
              break;
        }

        // First pass - see if anything is going to go off the edge if we do this move
        designer.selected_boxes.forEach(function(selected_box) {
            var newCenterX = designer.boxlist[selected_box]["left"] + (designer.boxlist[selected_box]["width"] / 2) + left_shift;
            if (newCenterX < 0 || newCenterX > designer.page_width) {
                left_shift = 0;
            }

            var newCenterY = designer.boxlist[selected_box]["top"] + (designer.boxlist[selected_box]["height"] / 2) + top_shift;
            if (newCenterY < 0) {
                top_shift = 0;
            }
        });

        // Second pass - apply the changes, assuming we should actually move anything
        if (left_shift != 0 || top_shift != 0) {
            designer.selected_boxes.forEach(function(selected_box) {
                designer.boxlist[selected_box]["left"] = designer.boxlist[selected_box]["left"] + left_shift;
                designer.boxlist[selected_box]["top"] = designer.boxlist[selected_box]["top"] + top_shift;
    
                // Increase the page height if we need to
                var bottom = designer.boxlist[selected_box]["top"] + designer.boxlist[selected_box]["height"];
                if (bottom > designer.page_height - designer.grid_size) {
                    designer.page_height = bottom + designer.grid_size;
                }
            });

            designer.draw();
            designer.modified();
            designer.end_save_undo_state("key"+e.keyCode);
        
            return true;
        } else {
            designer.cancel_save_undo_state();
            return false;
        }
    },

    "handle_delete_key_event": function(e){
        var targetTagName = e.target.tagName.toLowerCase();
        if (targetTagName === "input" || targetTagName === "textarea") return false;

        if (designer.selected_boxes.length > 0) {
            designer.delete_selected_boxes();
            return true;
        }
        return false;
    },

    "add_events": function(){

        // Double click to display widget options
        $(this.canvas).bind('dblclick', function(e){
            if (designer.selected_boxes.length == 1) {
                $('#options-button').trigger('click');
            }
        });

        $(this.canvas).bind('touchstart mousedown', function(e){
            designer.mousedown = true;

            var mx = 0, my = 0;
            var event = designer.get_unified_event(e);
            if(event.offsetX==undefined){ // this works for Firefox
                mx = (event.pageX - $(event.target).offset().left);
                my = (event.pageY - $(event.target).offset().top);
            } else {
                mx = event.offsetX;
                my = event.offsetY;
            }

            if (designer.edit_mode){
                // If its not yet selected check if a box is selected now
                var selected_box = designer.onbox(mx,my);
                if (selected_box) {
                    designer.selectbox(selected_box);

                    designer.start_save_undo_state();

                    resize = designer.boxlist[selected_box];

                    var squareSize = 8;
                    var width = designer.boxlist[selected_box]["width"];
                    var height = designer.boxlist[selected_box]["height"];

                    if (width>75 && height>75){squareSize = 16;}
                    if (width>125 && height>125){squareSize = 25;}

                    var rightedge = resize["left"]+resize["width"];
                    var bottedge = resize["top"]+resize["height"];
                    var midx = resize["left"]+(resize["width"]/2);
                    var midy = resize["top"]+(resize["height"]/2);

                    if (Math.abs(mx - rightedge)<(squareSize/2) && Math.abs(my - midy)<(squareSize/2))
                        designer.selected_edge = selected_edges.right;
                    else if (Math.abs(mx - resize["left"])<(squareSize/2)&& Math.abs(my - midy)<(squareSize/2))
                        designer.selected_edge = selected_edges.left;
                    else if (Math.abs(my - bottedge)<(squareSize/2)&& Math.abs(mx - midx)<(squareSize/2))
                        designer.selected_edge = selected_edges.bottom;
                    else if (Math.abs(my - resize["top"])<(squareSize/2)&& Math.abs(mx - midx)<(squareSize/2))
                        designer.selected_edge = selected_edges.top;
                    else if (Math.abs(my - midy)<(squareSize/2) && Math.abs(mx - midx)<(squareSize/2))
                        designer.selected_edge = selected_edges.center;
                    else
                        designer.selected_edge = selected_edges.none;

                    designer.draw();

                } else {
                    if (!designer.shiftdown) {
                        // This handles when the click is outside any box to deselect all boxes.
                        designer.selectbox(null);
                        designer.draw()
                    }
                    // Box drawing mode
                    designer.box_select_mode = true;
                    designer.boxStartX = mx;
                    designer.boxStartY = my;
                    designer.boxWidth = 0;
                    designer.boxHeight = 0;
                }

            } else {
                if (designer.create){
                    designer.add_widget(mx,my,designer.create);
                    designer.create = null;
                    $("#when-selected").show();
                }
            }
        });

        $(this.canvas).bind('touchend touchcancel mouseup', function(e){
            designer.end_save_undo_state();
            designer.mousedown = false;
            designer.selected_edge = selected_edges.none;

            if (designer.box_select_mode){
                // Normalize width and height to always be positive
                if (designer.boxWidth < 0) {
                    designer.boxStartX += designer.boxWidth;
                    designer.boxWidth = Math.abs(designer.boxWidth);
                }
                if (designer.boxHeight < 0) {
                    designer.boxStartY += designer.boxHeight;
                    designer.boxHeight = Math.abs(designer.boxHeight);
                }

                designer.ctx.strokeRect(designer.boxStartX, designer.boxStartY, designer.boxWidth, designer.boxHeight);

                // Select boxes inside the drawn box
                var selectedBoxes = designer.boxesInsideDrawBox(designer.boxStartX, designer.boxStartY, designer.boxWidth, designer.boxHeight);
                selectedBoxes.forEach(function(box) {
                    designer.selectbox(box, true);
                });
                designer.box_select_mode = false;
                designer.draw();
                return false;
            }
        });

        $(this.canvas).bind('touchmove mousemove', function(e){
            var mx = 0, my = 0;
            var event = designer.get_unified_event(e);
            if(event.offsetX==undefined){ // this works for Firefox
                mx = (event.pageX - $(event.target).offset().left);
                my = (event.pageY - $(event.target).offset().top);
            } else {
                mx = event.offsetX;
                my = event.offsetY;
            }
            // Force limits to designer area
            if (mx < 0) mx = 0; else if (mx >  designer.page_width) mx = designer.page_width;
            if (my < 0) my = 0;

            if (designer.mousedown && designer.box_select_mode) {
                // Draw the box being dragged
                designer.boxWidth = mx - designer.boxStartX;
                designer.boxHeight = my - designer.boxStartY;

                designer.draw(); // Draw existing boxes

                var selectedColor = "rgba(0, 0, 0, 0.9)";
                designer.ctx.strokeStyle = selectedColor;
                designer.ctx.setLineDash([6]);
                designer.ctx.strokeRect(designer.boxStartX, designer.boxStartY, designer.boxWidth, designer.boxHeight);
                return false;

            } else if (designer.mousedown && designer.selected_boxes.length > 0 && designer.selected_edge){

                designer.selected_boxes.forEach(function(selected_box) {
                    resizelocal = designer.boxlist[selected_box];

                    var rightedge = resizelocal["left"]+resizelocal["width"];
                    var bottedge = resizelocal["top"]+resizelocal["height"];

                    switch(designer.selected_edge){
                        case selected_edges.right:
                            resizelocal["width"] = (designer.snap(mx)-resizelocal["left"]);
                            break;
                        case selected_edges.left:
                            resizelocal["left"] = (designer.snap(mx));
                            resizelocal["width"] = rightedge - designer.snap(mx);
                            break;
                        case selected_edges.bottom:
                            resizelocal["height"] = (designer.snap(my)-resizelocal["top"]);
                            break;
                        case selected_edges.top:
                            resizelocal["top"] = (designer.snap(my));
                            resizelocal["height"] = bottedge - designer.snap(my);
                            break;
                        case selected_edges.center:
                            resizelocal["left"] = (designer.snap(mx + resizelocal["selected_offset_mid_x"] - resizelocal["width"]/2));
                            resizelocal["top"] = (designer.snap(my + resizelocal["selected_offset_mid_y"] - resizelocal["height"]/2));
                            break;
                    }
                    // Zero cant be selected se we default to minimal grid size
                    if (resizelocal["width"] < designer.grid_size) resizelocal["width"] = designer.grid_size;
                    if (resizelocal["height"] < designer.grid_size) resizelocal["height"] = designer.grid_size;
                    
                    if (bottedge>designer.page_height-designer.grid_size){
                        designer.page_height = bottedge+designer.grid_size;
                    }
                })
                designer.draw();
                designer.modified();

                return false;
            }
        });

        // Key events
        $(window).keydown(function(e) {
            var keyCode = e.keyCode;
            switch (keyCode) {
                case 37:
                case 38:
                case 39:
                case 40: // Arrow keys
                    if (designer.handle_arrow_key_event(e)) {
                        e.preventDefault();
                    }
                    break;
                case 16: // Shift
                case 17: // Ctrl
                    designer.shiftdown = true;
                    break;
                case 8:
                case 46: // Backspace & delete
                    if (designer.handle_delete_key_event(e)) {
                        e.preventDefault();
                    }
                    break;
                default:
                    // Key not handled
                    break;
            }
        });

        $(window).keyup(function(e) {
            var keyCode = e.keyCode;

            if (keyCode == 16 || keyCode == 17) {
                designer.shiftdown = false;
            }
        });

        // On save click
        $("#options-save").click(function(){
            designer.start_save_undo_state();
            var selected_box = designer.selected_boxes[0];
            $(".options").each(function() {
                if ($(this).attr("id")=="html"){
                    $("#"+selected_box).html($(this).val());
                }
                else if ($(this).attr("id").substring(0,6)=="colour"){
                    // Since colour values are generally prefixed with "#", and "#" isn't valid in URLs, we strip out the "#".
                    // It will be replaced by the value-checking in the actual plot function, so this won't cause issues.
                    var colour = $(this).val();
                    colour = colour.replace("#","");
                    $("#"+selected_box).attr($(this).attr("id"), colour);
                }
                else if ($(this).attr("id").indexOf("styleUnit") == 0){
                    //Get styleUnit* options and set it to boxlist array
                    designer.boxlist[selected_box][$(this).attr("id")]=parseInt($(this).val());
                }
                else {
                    $("#"+selected_box).attr($(this).attr("id"), $(this).val());
                }
            });
            $('#widget_options').modal('hide')
            designer.end_save_undo_state();
            designer.draw();
            designer.modified();
            reloadiframe = selected_box;
        });

        $("#undo-button").click(function(event){
            designer.undo();
        });

        $("#redo-button").click(function(event){
            designer.redo();
        });

         $("#delete-button").click(function(event){
             designer.delete_selected_boxes();
        });

        $("#options-button").click(function(event){
            if (designer.selected_boxes.length == 1){
                designer.draw_options($("#"+designer.selected_boxes[0]).attr("class"));
            }
        });

        $("#move-forward-button").click(function(event){
            if (designer.selected_boxes.length > 0){
                designer.start_save_undo_state();
                var need_redraw = false;
                designer.selected_boxes.forEach(function(selected_box) {
                    var selected_box_element = $("#"+selected_box);
                    var next_element = selected_box_element.next();
                    if (next_element.length > 0) {
                        selected_box_element.insertAfter(next_element);
                        need_redraw = true;
                    }
                });
                if (need_redraw) {
                    designer.draw();
                    designer.modified();
                    designer.end_save_undo_state('moveforward');
                } else {
                    designer.cancel_save_undo_state();
                }
            }
        });

        $("#move-backward-button").click(function(event){
            if (designer.selected_boxes.length > 0){
                designer.start_save_undo_state();
                var need_redraw = false;
                designer.selected_boxes.forEach(function(selected_box) {
                    var selected_box_element = $("#"+selected_box);
                    var prev_element = selected_box_element.prev();
                    if (prev_element.length > 0) {
                        selected_box_element.insertBefore(prev_element);
                        need_redraw = true;
                    }
                });
                if (need_redraw) {
                    designer.draw();
                    designer.modified();
                    designer.end_save_undo_state('movebackward');
                } else {
                    designer.cancel_save_undo_state();
                }
            }
        });
        
        $(document).on('keyup', '.input-is-other', function(event) {
            var input = $(event.target);
            var select = input.parent().prev().find('select');
            if (select.val() === '__other') {
                input.data('last-value', input.val());
            }
        });
        $(document).on('change', '.select-with-other', function(event) {
            var select = $(event.target);
            var other = select.parent().next('.other');
            var input = other.find('input');
            var last_val = input.data('last-value');
            // if user selected "Other" option, show text box
            if (select.val() === '__other') {
                other.removeClass('hidden');
                if (last_val) input.val(last_val);
                input.focus();
            } else {
                input.val(select.val());
                other.addClass('hidden');
            }
        })
    }
}
