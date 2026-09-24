/*
 designer.js -  Licence: GNU GPL Affero, Author: Trystan Lea / Chaveiro 2024

 The dashboard designer holds the dashboard document, see notes/SCHEMA.md
 and notes/EDITOR.md. Each widget is a box at a fixed position with
 a width and a height, and its options, for example the feedid to use:

 { "id": 7, "type": "dial", "x": 50, "y": 50, "w": 200, "h": 100,
   "wunit": "px", "hunit": "px", "options": { "feedid": "1" } }

 Every edit writes the document, and the box on the page is drawn from it by
 box_element, the same markup the server renders:

 <div id="7" class="dial" feedid="1" style="position:absolute; margin: 0; top:50px; left:50px; width:200px; height:100px;"></div>

 render.js mounts a widget in each box. On save the document is posted.
 The designer draws a canvas layer above the boxes and uses jquery to get the
 mouse positions and actions that specify the box position and dimensions.
 draw_options and widget_buttons draw the menu and widget options interface.
*/

var selected_edges = {none : 0, left : 1, right : 2, top : 3, bottom : 4, center : 5};

// Escapes a value on its way into the html the options modal is built from.
// Option values hold what the author typed and feed names hold whatever posted
// the data, so neither is markup here.
function designer_escape(value)
{
    if (value === undefined || value === null) return "";
    return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

var designer = {

    "feedmode":"feedid",
    "grid_size":20,
    "page_width":500,
    "canvas_width":500,
    "page_height":500,
    "min_height":400,

    "cnvs":null,
    "canvas":null,
    "ctx":null,

    // The dashboard document, set by the edit view before init
    "document": null,

    "boxlist": {},

    "selected_boxes": [],
    "selected_edge": selected_edges.none,

    // Dashboard id, set by the edit view. A paste from another dashboard
    // keeps its positions, a paste on the same one is stepped along.
    "dashboard_id": 0,

    // Copied widgets, see copy_selected_boxes and paste_text
    "clipboard": null,
    "clipboard_text": null,
    "edit_mode": true,
    "create": null,
    "create_button": null,

    "box_select_mode": false,
    "boxStartX": null,
    "boxStartY": null,
    "boxWidth": null,
    "boxHeight": null,

    "mousedown": false,
    "shiftdown": false,

    "undostack": [],
    "redostack": [],
    "nextundostate": null,
    "lastundoidentifier": null,

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
        $("#save-dashboard").attr("class","btn btn-warning").text(_Tr("Changed, press to save"));
    },

    "start_save_undo_state": function(){
        if (designer.nextundostate !== null) {
            //console.log("Imbalanced undo state save start/end!");
        }
        designer.nextundostate = designer.encode();
    },

    "end_save_undo_state": function(identifier){
        if (designer.nextundostate === null) {
            //console.log("No undo state to save!");
            return;
        }
        var currentstate = designer.encode();
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

        var currentstate = designer.encode();
        var laststate = designer.undostack.pop();
        designer.lastundoidentifier = null;

        designer.redostack.push(currentstate);

        designer.document = JSON.parse(laststate);
        designer.render_page();
        designer.selected_boxes = [];
        designer.selection_buttons();
        designer.scan();
        designer.draw();
        designer.modified();
        designer.check_undo_state();
    },

    "redo": function(){
        if (designer.redostack.length == 0) return;

        var currentstate = designer.encode();
        var laststate = designer.redostack.pop();
        designer.lastundoidentifier = null;

        designer.undostack.push(currentstate);

        designer.document = JSON.parse(laststate);
        designer.render_page();
        designer.selected_boxes = [];
        designer.selection_buttons();
        designer.scan();
        designer.draw();
        designer.modified();
        designer.check_undo_state();
    },

    // Document as a string, for the undo stack and the save comparison
    "encode": function(){
        return JSON.stringify(designer.document);
    },

    "widget_by_id": function(id){
        var list = designer.document.widgets;
        for (var i = 0; i < list.length; i++) {
            if (list[i].id == id) return list[i];
        }
        return null;
    },

    "widget_index": function(id){
        var list = designer.document.widgets;
        for (var i = 0; i < list.length; i++) {
            if (list[i].id == id) return i;
        }
        return -1;
    },

    // Box of a widget, the markup dashboard_render_write writes in
    // dashboard_render.php. render.js mounts it once it is on the page.
    "box_element": function(widget){
        var el = document.createElement("div");
        el.id = widget.id;
        el.className = widget.type;

        var w = Math.max(0, widget.w), h = Math.max(0, widget.h);
        var y = Math.max(0, widget.y);
        var style = "position:absolute; margin: 0; top:" + y + "px; left:" + widget.x + "px; "
        + "width:" + w + (widget.wunit == "pc" ? "%" : "px") + "; "
        + "height:" + h + (widget.hunit == "pc" ? "%" : "px") + ";";
        if (widget.style) {
            for (var property in widget.style) style += " " + property + ": " + widget.style[property] + ";";
        }
        el.setAttribute("style", style);

        if (widget.options) {
            for (var name in widget.options) el.setAttribute(name, widget.options[name]);
        }
        if (widget.config) el.setAttribute("config", JSON.stringify(widget.config));

        if (widget.unknown && widget.html === undefined && widget.type.indexOf("Container-") !== 0) {
            // Placeholder for a widget no module declares, as the server draws
            var placeholder = document.createElement("div");
            placeholder.className = "dashboard-placeholder";
            placeholder.appendChild(document.createTextNode(widget.type));
            placeholder.appendChild(document.createElement("br"));
            var small = document.createElement("small");
            small.textContent = _Tr("widget not installed");
            placeholder.appendChild(small);
            el.appendChild(placeholder);
        } else {
            el.innerHTML = (widget.html || "") + (widget.text || "");
        }
        return el;
    },

    // Draws every box from the document, replacing what is on the page
    "render_page": function(){
        var page = document.getElementById("page");
        var boxes = [];
        for (var i = 0; i < designer.document.widgets.length; i++) {
            boxes.push(designer.box_element(designer.document.widgets[i]));
        }
        page.replaceChildren.apply(page, boxes);
    },

    // Draws one box again from its widget, after its options changed
    "render_box": function(id){
        var old = document.getElementById(id);
        var widget = designer.widget_by_id(id);
        if (!old || !widget) return;
        old.replaceWith(designer.box_element(widget));
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

        for (var z in designer.boxlist) {
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
            });

        designer.selection_buttons();
    },

    // Toolbox buttons that act on the selection follow its size
    "selection_buttons": function(){
        var count = designer.selected_boxes.length;
        $("#when-selected").toggle(count > 0);
        $("#options-button").prop("disabled", count !== 1);
        $("#copy-button, #cut-button").prop("disabled", count === 0);
    },

    // Pixel geometry of every widget, for the canvas. A per cent width or
    // height is read from the box as drawn, since it depends on the page.
    "scan": function(){
        designer.boxlist = {};
        var list = designer.document.widgets;
        for (var i = 0; i < list.length; i++) {
            var widget = list[i];
            var id = widget.id;
            var el = $("#" + id);
            var pc_width = widget.wunit == "pc";
            var pc_height = widget.hunit == "pc";
            designer.boxlist[id] = {
                "type": widget.type,
                "top": widget.y,
                "left": widget.x,
                "width": pc_width && el.length ? parseInt(el.css("width")) : widget.w,
                "height": pc_height && el.length ? parseInt(el.css("height")) : widget.h,
                "styleUnitWidth": pc_width ? 1 : 0,
                "styleUnitHeight": pc_height ? 1 : 0
            };

            if (designer.boxlist[id]["width"] < designer.grid_size) {designer.boxlist[id]["width"] = designer.grid_size;}    // Zero cant be selected se we default to minimal grid size
            if (designer.boxlist[id]["height"] < designer.grid_size) {designer.boxlist[id]["height"] = designer.grid_size;}
        }
    },

    // Page height is one grid spacing below the lowest widget, with a
    // minimum so an empty page has room to drop widgets on. Canvas width
    // is the column width, or one grid spacing past the rightmost widget
    // when a page built on a wider screen overflows the column.
    "fit_size": function(){
        var bottom = 0, right = 0;
        for (var z in designer.boxlist) {
            var box = designer.boxlist[z];
            if (box["top"] + box["height"] > bottom) bottom = box["top"] + box["height"];
            if (box["left"] + box["width"] > right) right = box["left"] + box["width"];
        }
        bottom = Math.ceil(bottom / designer.grid_size) * designer.grid_size + designer.grid_size;
        right = Math.ceil(right / designer.grid_size) * designer.grid_size + designer.grid_size;
        designer.page_height = Math.max(bottom, designer.min_height);
        designer.page_width = parseInt($("#dashboardpage").width());
        designer.canvas_width = Math.max(right, designer.page_width);
    },

    "draw": function(){
        designer.fit_size();
        $("#page-container").css("height",designer.page_height);
        $("#can").attr("height",designer.page_height);
        $("#can").width(designer.canvas_width);
        designer.cnvs.setAttribute("width", designer.canvas_width);
        designer.ctx = designer.cnvs.getContext("2d");

        designer.ctx.clearRect(0,0,designer.canvas_width,designer.page_height);

        designer.ctx.translate(0.5, 0.5); // Move the canvas by 0.5px to fix blurring

        // Dotted line marking the bottom of the page
        designer.ctx.save();
        designer.ctx.strokeStyle = "rgba(0, 0, 0, 0.4)";
        designer.ctx.lineWidth = 1;
        designer.ctx.setLineDash([1, 3]);
        designer.ctx.beginPath();
        designer.ctx.moveTo(0, designer.page_height - 1);
        designer.ctx.lineTo(designer.canvas_width, designer.page_height - 1);
        designer.ctx.stroke();
        designer.ctx.restore();

        // Draw grid
        designer.ctx.fillStyle    = "rgba(0, 0, 0, 0.2)";

        for (var x=1; x<parseInt(designer.canvas_width/designer.grid_size); x++){
            for (var y=1; y<parseInt(designer.page_height/designer.grid_size); y++){
                designer.ctx.fillRect((x*designer.grid_size)-1,(y*designer.grid_size)-1,1,1);
            }
        }

        // Faint outline of every box so a widget that renders nothing can
        // still be found, and a label on a widget that has no feed set
        designer.ctx.font = "12px sans-serif";
        designer.ctx.textAlign = "center";
        designer.ctx.textBaseline = "middle";
        designer.ctx.setLineDash([3]);
        for (var id in designer.boxlist) {
            var box = designer.boxlist[id];
            designer.ctx.strokeStyle = "rgba(0, 0, 0, 0.15)";
            designer.ctx.strokeRect(box["left"],box["top"],box["width"],box["height"]);
            var note = designer.unconfigured(id);
            if (note) {
                designer.ctx.fillStyle = "rgba(255, 190, 0, 0.12)";
                designer.ctx.fillRect(box["left"],box["top"],box["width"],box["height"]);
                designer.ctx.fillStyle = "rgba(0, 0, 0, 0.5)";
                designer.ctx.fillText(box["type"]+": "+note, box["left"]+box["width"]/2, box["top"]+box["height"]/2, box["width"]-4);
            }
        }
        designer.ctx.setLineDash([]);

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

        // Update position and dimensions of the elements and the document
        for (var z in designer.boxlist) {
            if (z){
                var box = designer.boxlist[z];
                var widget = designer.widget_by_id(z);
                var element = "#"+z;
                $(element).css("top", box["top"]+"px");
                $(element).css("left", box["left"]+"px");
                var w = box["width"], wunit = "px";
                var h = box["height"], hunit = "px";
                if (box["styleUnitWidth"] == 1) {
                    w = Math.round(box["width"] / designer.page_width * 100);
                    wunit = "pc";
                }
                if (box["styleUnitHeight"] == 1) {
                    h = Math.round(box["height"] / designer.page_height * 100);
                    hunit = "pc";
                }
                $(element).css("width", w + (wunit == "pc" ? "%" : "px"));
                $(element).css("height", h + (hunit == "pc" ? "%" : "px"));
                if (widget) {
                    widget.x = box["left"];
                    widget.y = box["top"];
                    widget.w = w;
                    widget.h = h;
                    widget.wunit = wunit;
                    widget.hunit = hunit;
                }
            }
        }
    },

    // Note for a widget that is missing what it needs to show anything:
    // a feed widget with no feed set, or a text widget with no text.
    // Returns null when the widget is complete.
    "unconfigured": function(id){
        var w = widgets[designer.boxlist[id]["type"]];
        var widget = designer.widget_by_id(id);
        if (!w || !w["optionstype"] || !widget) return null;
        var hasfeed = false, hastext = false;
        for (var i in w["optionstype"]) {
            var type = w["optionstype"][i];
            if (type == "text" || type == "html") hastext = true;
            if (type != "feedid") continue;
            hasfeed = true;
            var val = widget.options ? widget.options[w["options"][i]] : undefined;
            if (val !== undefined && val !== "") return null;
        }
        if (hasfeed) return _Tr("not configured");
        // The old paragraph and heading types hold their content in html, the
        // text widget in text, and both are drawn. An image counts as content.
        var content = $("<div>").html((widget.html || "") + (widget.text || ""));
        if (hastext && content.text().trim() === "" && !content.find("img").length) return _Tr("no text");
        return null;
    },

    "draw_options": function(widget){
        if (!widgets[widget]) return;
        var box_options = widgets[widget]["options"];
        var options_type = widgets[widget]["optionstype"];
        var options_name = widgets[widget]["optionsname"];
        var optionshint = widgets[widget]["optionshint"];
        var optionsdata = widgets[widget]["optionsdata"];

        // You can only configure if there's one selected box, so just select the first
        var selected_box = designer.selected_boxes[0];
        var selected_widget = designer.widget_by_id(selected_box);
        if (!selected_widget) return;
        if (!selected_widget.options) selected_widget.options = {};

        // Build options table html
        var options_html = '<div id="box-options">';

        // The modal is titled after the widget. A description, if any, sits
        // above the options.
        var title = widgets[widget]["title"] ? widgets[widget]["title"] : widget;
        $("#myModalLabel").text(_Tr("Configure") + " " + String(title).toLowerCase());
        if (widgets[widget]["description"]) {
            options_html += '<p class="muted" style="font-size:12px;">'+designer_escape(widgets[widget]["description"])+"</p>";
        }

        // A widget whose settings do not fit in attributes holds them in one
        // nested config, see the inline config section of notes/SCHEMA.md. The
        // hidden input carries it so the save handler writes it back like any
        // other option, and the widget fills the block beside it. It comes
        // before the options, which are the less used part of the form.
        if (widgets[widget]["config"]) {
            var config_value = selected_widget.config ? JSON.stringify(selected_widget.config) : "";
            options_html += '<div class="control-group"><div class="controls">';
            options_html += '<input type="hidden" class="options" id="config" value="'+designer_escape(config_value)+'">';
            options_html += '<div id="widget-config-editor"></div>';
            options_html += "</div></div>";
            options_html += '<hr style="margin:10px 0;">';
        }

        designer.option_rules = {};
        for (var z in box_options){
            designer.option_rules[box_options[z]] = {
                "type": options_type ? options_type[z] : "value",
                "data": optionsdata ? optionsdata[z] : null
            };
            var val = selected_widget.options[box_options[z]];

            if (val == undefined) val="";

            options_html += '<div class="control-group"><div class="controls">';
            options_html += '<div class="input-prepend" style="margin-bottom: 0px;">';
            options_html += '<span class="add-on" style="width:100px; text-align: right; font-size:12px;">'+options_name[z]+"</span>";

            // all feeds

            if (options_type && options_type[z] == "feedid"){
                options_html += designer.select_feed(box_options[z],feedlist,val);
            }

            else if (options_type && options_type[z] == "html"){
                val = selected_widget.html || "";
                options_html += "<textarea class='options' id='"+box_options[z]+"' >"+designer_escape(val)+"</textarea>";
            }

            // Body of a text widget
            else if (options_type && options_type[z] == "text"){
                val = selected_widget.text || "";
                options_html += "<textarea class='options' id='"+box_options[z]+"' >"+designer_escape(val)+"</textarea>";
            }

            else if (options_type && options_type[z] == "number"){
                var range = optionsdata && optionsdata[z] ? optionsdata[z] : {};
                options_html += "<input class='options' id='"+box_options[z]+"' type='number'";
                if (range.min !== undefined) options_html += " min='"+range.min+"'";
                if (range.max !== undefined) options_html += " max='"+range.max+"'";
                options_html += " value='"+designer_escape(val)+"'/ >";
            }

            // Combobox for selecting options
            else if (options_type && options_type[z] == "dropbox" && optionsdata && optionsdata[z]){  // Check we have optionsdata before deciding to draw a combobox
                options_html += "<select id='"+box_options[z]+"' class='options' >";
                for (var i in optionsdata[z])
                {
                    var selected = "";
                    if (val + "" === optionsdata[z][i][0] + "")
                    selected = "selected";
                    options_html += "<option "+selected+" value=\""+optionsdata[z][i][0]+"\">"+optionsdata[z][i][1]+"</option>";
                }
                options_html += "</select>";
            }
            // Combobox for selecting options with "other" option
            else if (options_type && options_type[z] == "dropbox_other" && optionsdata && optionsdata[z]){  // Check we have optionsdata before deciding to draw a combobox
                options_html += '<select id="' + box_options[z] + '_dropdown" class="options select-with-other">';
                options_html += "<option value=''></option>";
                var values = [];
                for (var i in optionsdata[z])
                {
                    values.push(optionsdata[z][i][0]);
                    var selected = "";
                    if (val + "" === optionsdata[z][i][0] + "") {
                        selected = "selected";
                    }
                    options_html += "<option "+selected+" value=\""+optionsdata[z][i][0]+"\">"+optionsdata[z][i][1]+"</option>";
                }
                // if saved value not in list set the 'Other' option
                var other_selected = "";
                if (values.indexOf(val) === -1 && val !== "") {
                    other_selected = "selected";
                }

                var other_hidden = other_selected !== "selected" ? "hidden" : "";
                options_html += "<option " + other_selected + " value='__other'>"+_Tr("Other")+"</option>";
                options_html += "</select>";
                options_html += "</div>";
                options_html += '<div class="input-prepend ' + other_hidden + ' other"><span class="add-on" style="width:100px; text-align: right; font-size:12px;background: none;border: none;margin-right: 1px;">' + _Tr("Other") + "</span>";
                options_html += '<input id="' + box_options[z] + '" type="text" value="' + designer_escape(val) + '" data-last-value="' + designer_escape(val) + '" class="options input-is-other" style="border-radius:0 0 4px 4px;border-top:none">';
            }

            else if (options_type && options_type[z] == "colour_picker"){
                // A colour input cannot hold nothing, so no colour at all is
                // a box beside it, stored as none. The picker shows the
                // default meanwhile.
                var none = (val == "none");
                if (none) val = "";
                if (optionsdata && optionsdata[z]!=undefined && val=="") {
                    val = optionsdata[z];
                }
                options_html += "<input  type='color' class='options' id='"+box_options[z]+"'  value='#"+designer_escape(val)+"'/ >";
                options_html += " <label class='checkbox inline' style='margin-left:8px'><input type='checkbox' class='colour-none' data-for='"+box_options[z]+"'"+(none ? " checked" : "")+"> "+_Tr("None")+"</label>";
            }

            else if (options_type && options_type[z] == "boolean"){
                // A widget that is on unless it is turned off gives that
                // default in optionsdata, the same as a colour picker does.
                // Without one the select opens on Off whatever the widget
                // does, and a save then writes Off to a box that was on.
                if ((val == undefined || val == "") && optionsdata && optionsdata[z] != undefined) {
                    val = optionsdata[z];
                }
                options_html += "<select class='options' id='"+box_options[z]+"'>";
                options_html += "<option value='0'" + (val == 0 ? " selected" : "") + ">"+_Tr("Off")+"</option>";
                options_html += "<option value='1'" + (val == 1 ? " selected" : "") + ">"+_Tr("On")+"</option>";
                options_html += "</select>";
            }


            else{
                options_html += "<input class='options' id='"+box_options[z]+"' type='text' value='"+designer_escape(val)+"'/ >";
            }

            options_html += "</div>";
            options_html += '<span class="help-inline"><small class="muted">'+optionshint[z]+"</small></span>";
            options_html +="</div></div>";

        }

        // Generic sizing options for all widgets (an hack so we dont add new options to all widgets)
        var selPixel = (designer.boxlist[selected_box]["styleUnitWidth"] == 0 ? "selected" : "");
        var selPercent = (designer.boxlist[selected_box]["styleUnitWidth"] == 1 ? "selected" : "");
        options_html += '<div class="control-group"><div class="controls"><div style="margin-bottom: 0px;" class="input-prepend"><span style="width:100px; text-align: right; font-size:12px;" class="add-on">'+_Tr("Width")+"</span>";
        options_html += '<select class="options" id="styleUnitWidth"><option value="0" '+selPixel+">"+_Tr("Pixels")+'</option><option value="1" '+selPercent+">"+_Tr("Percentage")+"</option></select>";
        options_html += '</div><span class="help-inline"><small class="muted">'+_Tr("Choose width unit")+"</small></span></div></div>";

        var selPixel = (designer.boxlist[selected_box]["styleUnitHeight"] == 0 ? "selected" : "");
        var selPercent = (designer.boxlist[selected_box]["styleUnitHeight"] == 1 ? "selected" : "");
        options_html += '<div class="control-group"><div class="controls"><div style="margin-bottom: 0px;" class="input-prepend"><span style="width:100px; text-align: right; font-size:12px;" class="add-on">'+_Tr("Height")+"</span>";
        options_html += '<select class="options" id="styleUnitHeight"><option value="0" '+selPixel+">"+_Tr("Pixels")+'</option><option value="1" '+selPercent+">"+_Tr("Percentage")+"</option></select>";
        options_html += '</div><span class="help-inline"><small class="muted">'+_Tr("Choose height unit")+"</small></span></div></div>";

        // A widget with a config may put settings of its own under the
        // options too, rows that belong with them rather than with its form.
        if (widgets[widget]["config"]) {
            options_html += '<div id="widget-config-options"></div>';
        }

        options_html += "</div>";

        // Fill the modal configuration window with options
        $("#widget_options_body").html(options_html);

        // A widget with a config draws a form of its own above the options,
        // which wants more room than the options table does.
        $("#widget_options").toggleClass("modal-wide", !!widgets[widget]["config"]);

        // The widget draws its own config block, now the modal holds it.
        if (widgets[widget]["config"] && typeof window[widget+"_config_editor"] === "function") {
            window[widget+"_config_editor"]($("#widget-config-editor"), $("#config"));
        }

        // Check each value as it is typed, before the dashboard is saved.
        $("#widget_options_body").find(".options").on("input change", function(){
                designer.check_option($(this));
            }).each(function(){
                designer.check_option($(this));
            });
        // Also called here so a widget with no options resets the save button.
        designer.update_options_save();

        // Change the size of the text for items with class options - size initially set by bootstrap
        // also add height of 30 px for color inputs for Firefox
        $("input, select, textarea").css("font-size","12px");
        if (navigator.userAgent.search("Firefox") >= 0) {$("input[type='color']").css({"height":"30px", "width":"220px"});};
    },

    "select_feed": function (id, feedlist, currentval){
        var feedgroups = [];
        for (var f in feedlist){
            var group = (feedlist[f].tag === null ? "NoGroup" : feedlist[f].tag);
            if (group!="Deleted") {
                if (!feedgroups[group]) feedgroups[group] = [];
                feedgroups[group].push(feedlist[f]);
            }
        }
        var out = "<select id='"+id+"' class='options'>";
        for (var f in feedgroups){
            out += "<optgroup label='"+designer_escape(f)+"'>";
            for (var p in feedgroups[f]) {
                var feedref = feedgroups[f][p]["id"];
                if (designer.feedmode=="tagname") feedref = feedgroups[f][p]["tag"]+":"+feedgroups[f][p]["name"];
                var selected = "";
                if (currentval == feedref)
                selected = "selected";
                out += "<option value='"+designer_escape(feedref)+"' "+selected+">"+designer_escape(feedgroups[f][p].name)+"</option>";
            }
            out += "</optgroup>";
        }
        out += "</select>";
        return out;
    },

    "widget_buttons": function(){
        var widget_html = "";
        var select = [];
        for (var z in widgets){
            var menu = widgets[z]["menu"];
            // A widget with no menu is kept for existing dashboards only
            if (menu === undefined) continue;
            if (typeof select[menu] === "undefined") select[menu] = [];
            select[menu].push(z);
        }

        // Menus list widgets in script load order, and the graph module loads
        // after the dashboard module, so graph would sit last in its menu.
        // It is the one most dashboards want, so it goes first.
        for (var z in select){
            var at = select[z].indexOf("graph");
            if (at > 0) select[z].unshift(select[z].splice(at, 1)[0]);
        }

        for (var z in select){
            var title = _Tr("Add a")+" "+z+" "+_Tr("element to the dashboard");
            var icon = "<img src='../Modules/dashboard/Views/icons/"+z+".png'>";
            if (select[z].length == 1) {
                // A menu with one widget is a plain button that adds it
                widget_html += "<div class='widgetbuttons' style='display: inline-block; '><button data-widget='"+select[z][0]+"' class='btn widgetmenu widget-button' style='width:62px; padding:4px;' title='"+title+"'>"+icon+"</button></div>";
                continue;
            }
            var items = "";
            for (var i in select[z]) items += "<li><a data-widget='"+select[z][i]+"' class='widget-button'>"+select[z][i]+"</a></li>";
            widget_html += "<div class='widgetbuttons' style='display: inline-block; '><button class='btn dropdown-toggle widgetmenu' data-toggle='dropdown' style='width:62px; padding:4px;' title='"+title+"'>"+icon+"<span class='caret'></span></button>";
            widget_html += "<ul class='dropdown-menu scrollable-menu' style='min-width: auto; padding: 0px; text-align:left; top:initial' name='d'>"+items+"</ul></div>";
        }
        // Blank button so the toolbox rows are even
        widget_html += "<div class='widgetbuttons' style='display: inline-block; '><button class='btn widgetmenu' disabled style='width:62px; padding:4px; visibility:hidden;'><img src='../Modules/dashboard/Views/icons/Text.png'></button></div>";
        $("#widget-buttons").html(widget_html);

        $(".widget-button").click(function(event) {
                var type = $(this).attr("data-widget");
                // A second click on the pending widget cancels it
                if (designer.create == type) {
                    designer.clear_create();
                } else {
                    designer.set_create(type, $(this).closest(".widgetbuttons").find(".widgetmenu"));
                }
            });
    },

    // Marks a widget type as waiting to be placed on the next canvas click.
    // Toolbox button is shown pressed and the canvas cursor changes so the
    // pending state is visible.
    "set_create": function(type, button){
        designer.clear_create();
        designer.create = type;
        designer.create_button = button;
        designer.edit_mode = false;
        if (button) button.addClass("active");
        $(designer.canvas).css("cursor","crosshair");
    },

    "clear_create": function(){
        if (designer.create_button) designer.create_button.removeClass("active");
        designer.create = null;
        designer.create_button = null;
        designer.edit_mode = true;
        $(designer.canvas).css("cursor","");
        designer.draw();
    },

    // Dashed outline of the pending widget at its snapped position
    "draw_ghost": function(mx,my){
        var w = widgets[designer.create];
        designer.draw();
        designer.ctx.strokeStyle = "rgba(0, 0, 0, 0.6)";
        designer.ctx.setLineDash([6]);
        designer.ctx.strokeRect(designer.snap(mx+w["offsetx"]), designer.snap(my+w["offsety"]), w["width"], w["height"]);
        designer.ctx.setLineDash([]);
    },

    "add_widget": function(mx,my,type){
        designer.start_save_undo_state();
        var list = widgets[type];
        var widget = {
            "id": designer.document.next_id++,
            "type": type,
            "x": designer.snap(mx+list["offsetx"]),
            "y": designer.snap(my+list["offsety"]),
            "w": list["width"],
            "h": list["height"],
            "wunit": "px",
            "hunit": "px",
            "options": {}
        };
        if (list["html"] !== undefined && list["html"] !== "") widget.html = list["html"];
        if (list["text"] !== undefined && list["text"] !== "") widget.text = list["text"];

        // A widget list may give starting values for its options, so the
        // options panel opens showing what is drawn rather than blank fields.
        var defaults = list["defaults"];
        if (defaults) {
            for (var name in defaults) widget.options[name] = defaults[name];
        }

        designer.document.widgets.push(widget);
        $("#page").append(designer.box_element(widget));

        designer.end_save_undo_state();
        designer.selected_boxes = [String(widget.id)];
        designer.selection_buttons();
        designer.scan();
        designer.draw();
        designer.modified();
        designer.edit_mode = true;
    },

    // Checks one option value against the rules in dashboard_convert_option_valid
    // in dashboard_convert.php. The server decides what is stored, so this only
    // reports a problem.
    "check_option": function(field){
        var rule = designer.option_rules[field.attr("id")];
        var group = field.closest(".control-group");
        var help = group.find(".help-inline").first();

        if (help.data("hint") === undefined) help.data("hint", help.html());

        // A number input reports an empty value for input that is not a number,
        // so badInput is checked to tell a cleared field from an invalid one.
        var element = field[0];
        var problem = "";
        if (element && element.validity && element.validity.badInput) {
            problem = _Tr("Must be a whole number");
        } else if (rule) {
            problem = designer.option_problem(rule, field.val());
        }
        if (problem === ""){
            group.removeClass("error");
            help.html(help.data("hint"));
        } else {
            group.addClass("error");
            help.html("<small>" + problem + "</small>");
        }

        designer.update_options_save();
    },

    // Save is disabled while any field has an error. Cancel still closes the
    // panel.
    "update_options_save": function(){
        var bad = $("#widget_options_body").find(".control-group.error").length;
        $("#options-save").prop("disabled", bad > 0);
        $("#options-problem").text(bad === 0 ? ""
            : bad + " " + (bad === 1 ? _Tr("error found, fix to save")
                : _Tr("errors found, fix to save")));
    },

    "option_problem": function(rule, value){
        if (value === undefined || value === "") return "";

        if (rule.type === "text") {
            return designer.content_problem(value, window.dashboard_text_elements);
        }
        if (rule.type === "html") {
            return designer.content_problem(value, window.dashboard_html_elements);
        }
        if (rule.type === "number") {
            if (!/^-?\d+$/.test(value)) return _Tr("Must be a whole number");
            var range = rule.data && !Array.isArray(rule.data) ? rule.data : {};
            var number = parseInt(value, 10);
            if (range.min !== undefined && number < range.min) {
                return _Tr("Must be") + " " + range.min + " " + _Tr("or more");
            }
            if (range.max !== undefined && number > range.max) {
                return _Tr("Must be") + " " + range.max + " " + _Tr("or less");
            }
            return "";
        }
        if (rule.type === "url" || rule.type === "image_url") {
            return designer.url_problem(value, rule.type === "image_url");
        }
        if (rule.type === "value" || rule.type === "dropbox_other") {
            if (value.length > 512) return _Tr("Too long, the limit is 512 characters");
            if (value.indexOf("<") !== -1 || value.indexOf(">") !== -1) {
                return _Tr("Invalid character");
            }
            return "";
        }
        return "";
    },

    // Returns the host of a url, an empty string for a url on this site, or
    // false for a scheme that is not allowed. Follows dashboard_convert_url_host
    // in dashboard_convert.php.
    "url_host": function(url){
        var absolute = /^https?:\/\/([^/?#]*)/i.exec(url);
        if (absolute) return designer.url_strip_port(absolute[1]);
        // Scheme relative, //host/path, points at another site
        if (url.substring(0, 2) === "//") {
            return designer.url_strip_port(url.substring(2).split(/[/?#]/)[0]);
        }
        if (url.split(/[/?#]/)[0].indexOf(":") !== -1) return false;
        return "";
    },

    "url_strip_port": function(host){
        var at = host.lastIndexOf("@");
        if (at !== -1) host = host.substring(at + 1);
        var colon = host.lastIndexOf(":");
        if (colon !== -1 && host.indexOf("]") === -1) host = host.substring(0, colon);
        return host.toLowerCase().replace(/\.+$/, "");
    },

    // A url on this site is fetched with the viewer's session, so it has extra
    // rules. Follows dashboard_convert_url_allowed and
    // dashboard_convert_url_own_site.
    "url_problem": function(value, is_image){
        var url = value.replace(/[\x00-\x20\x7f]/g, "");
        if (url === "") return "";

        var host = designer.url_host(url);
        if (host === false) return _Tr("Must start with http:// or https://");
        if (host !== "" && host !== designer.url_strip_port(window.location.host)) return "";

        // The url points at this site
        if (!is_image) {
            if (designer.view_link(url)) return "";
            return _Tr("Must point at another site, or at a dashboard, app or graph view");
        }

        if (url.indexOf("?") === -1 && designer.stored_image(url)) return "";
        return _Tr("Must point at another site, or at an image in")
        + " Modules/dashboard/Views/images";
    },

    // Whether a link back at this site is a page view. Follows
    // dashboard_convert_url_is_view_link in dashboard_convert.php.
    "view_link": function(url){
        var views = { dashboard: /^view$/, app: /^(?:view)?$/, graph: /^\d+$/ };
        var modules = window.dashboard_modules || [];

        url = url.replace(/\\/g, "/").replace(/^(?:https?:)?\/\/[^/?#]*/i, "");
        var path = url.split(/[?#]/)[0];
        var query = /\?([^#]*)/.exec(url);
        query = query ? query[1] : "";
        try {
            path = decodeURIComponent(path);
            query = decodeURIComponent(query);
        } catch (e) { return false; }
        if (/(?:^|[&;])\s*q\s*(?:[\[=&;]|$)/i.test(query)) return false;
        if (/[&?#=;%]/.test(path)) return false;

        var segments = [];
        if (path !== "" && path.charAt(0) !== "/") segments.push("dashboard");
        var parts = path.split("/");
        for (var p = 0; p < parts.length; p++) {
            if (parts[p] === "..") segments.pop();
            else if (parts[p] !== "" && parts[p] !== ".") segments.push(parts[p].toLowerCase());
        }

        for (var i = 0; i < segments.length; i++) {
            if (/\.(?:php\d*|phtml|phar|phps)$/.test(segments[i])) return false;
            if (modules.indexOf(segments[i]) === -1) continue;
            if (!views.hasOwnProperty(segments[i])) return false;
            var action = i + 1 < segments.length ? segments[i + 1] : "";
            return views[segments[i]].test(action) && segments.length <= i + 2;
        }
        return true;
    },

    "stored_image": function(url){
        var path = url.split(/[?#]/)[0];
        try { path = decodeURIComponent(path); } catch (e) { return false; }

        var segments = path.split("/");
        for (var i = 0; i < segments.length; i++) {
            if (segments[i] === "..") return false;
        }

        var match = /(?:^|\/)Modules\/dashboard\/Views\/images\/([^/]+)$/.exec(path);
        if (!match) return false;

        var dot = match[1].lastIndexOf(".");
        if (dot === -1) return false;
        var extension = match[1].substring(dot + 1).toLowerCase();
        return ["png", "jpg", "jpeg", "gif", "webp", "svg", "bmp", "ico", "avif"]
        .indexOf(extension) !== -1;
    },

    // Joins names as a list: b, i and sub.
    "name_list": function(names){
        if (names.length < 2) return names.join("");
        return names.slice(0, -1).join(", ") + " " + _Tr("and") + " " + names[names.length - 1];
    },

    // Reports tags in the markup that are not in the allowed list. See
    // dashboard_convert_clean in dashboard_convert.php.
    "content_problem": function(value, allowed){
        if (!allowed || value.indexOf("<") === -1) return "";

        var parsed = new DOMParser().parseFromString("<div>" + value + "</div>", "text/html");
        var wrapper = parsed.body.firstElementChild;
        var elements = wrapper ? wrapper.querySelectorAll("*") : [];
        var refused = [];
        var styled = false;
        var url_problems = [];

        for (var i = 0; i < elements.length; i++) {
            var tag = elements[i].tagName.toLowerCase();
            if (allowed.indexOf(tag) === -1) {
                if (refused.indexOf(tag) === -1) refused.push(tag);
                continue;
            }
            if (elements[i].hasAttribute("style")) styled = true;
            // Links and images are held to the rules the server applies on
            // save, see dashboard_convert_url_allowed.
            ["href", "src"].forEach(function(attribute){
                if (!elements[i].hasAttribute(attribute)) return;
                var problem = designer.url_problem(elements[i].getAttribute(attribute), attribute === "src");
                if (problem && url_problems.indexOf(problem) === -1) url_problems.push(problem);
            });
        }

        var says = [];
        if (refused.length) {
            // A short list is printed in full. The html list has around thirty
            // tags, so the refused tags are named instead.
            if (allowed.length <= 8) {
                says.push(_Tr("Must only use") + " " + designer.name_list(allowed));
            } else {
                says.push(_Tr("Must not use") + " " + designer.name_list(refused));
            }
        }
        // Styling in a text widget is set with its options.
        if (styled && allowed === window.dashboard_text_elements) {
            says.push(_Tr("Must not use style, set it with the options"));
        }
        url_problems.forEach(function(problem){ says.push(problem); });
        return says.join(". ");
    },

    "delete_selected_boxes": function(){
        if (designer.selected_boxes.length > 0) {
            designer.start_save_undo_state();
            designer.selected_boxes.forEach(function(selected_box) {
                    var at = designer.widget_index(selected_box);
                    if (at !== -1) designer.document.widgets.splice(at, 1);
                    delete designer.boxlist[selected_box];
                    $("#"+selected_box).remove();
                });
            designer.selected_boxes = [];
            designer.selection_buttons();
            designer.draw();
            designer.modified();
        }
    },

    // Copies the selected widgets in document order, so a paste keeps their
    // layering. The same text goes to the system clipboard where the browser
    // allows it, so the group can be pasted into another dashboard.
    "copy_selected_boxes": function(){
        if (designer.selected_boxes.length === 0) return false;
        var copies = [];
        designer.document.widgets.forEach(function(widget) {
                if (designer.selected_boxes.indexOf(String(widget.id)) > -1) {
                    copies.push(JSON.parse(JSON.stringify(widget)));
                }
            });
        designer.clipboard = {"emoncms_dashboard": designer.dashboard_id, "widgets": copies};
        designer.clipboard_text = JSON.stringify(designer.clipboard);
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(designer.clipboard_text).catch(function() {});
        }
        return true;
    },

    "cut_selected_boxes": function(){
        if (!designer.copy_selected_boxes()) return false;
        designer.delete_selected_boxes();
        return true;
    },

    // Widgets held in clipboard text, or null when it is not a copy
    "clipboard_widgets": function(text){
        var data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            return null;
        }
        if (!data || data.emoncms_dashboard === undefined || !Array.isArray(data.widgets)) return null;
        var widgets = data.widgets.filter(function(widget) {
                return widget && typeof widget.type === "string"
                && typeof widget.x === "number" && typeof widget.y === "number"
                && typeof widget.w === "number" && typeof widget.h === "number";
            });
        if (widgets.length === 0) return null;
        return {"emoncms_dashboard": data.emoncms_dashboard, "widgets": widgets};
    },

    // Pastes the system clipboard text when it holds a copy, else the last
    // copy made here. Text equal to the last copy is the same copy, so its
    // stepped positions are kept and a second paste lands further along.
    "paste_text": function(text){
        if (text && text !== designer.clipboard_text) {
            var data = designer.clipboard_widgets(text);
            if (data) {
                designer.clipboard = data;
                designer.clipboard_text = text;
            }
        }
        return designer.paste_widgets(designer.clipboard);
    },

    // Adds copies with new ids and selects them. A paste on the dashboard
    // they came from is stepped one grid spacing right and down, and the
    // clipboard is stepped with it.
    "paste_widgets": function(data){
        if (!data || data.widgets.length === 0) return false;
        var step = 0;
        if (Number(data.emoncms_dashboard) === designer.dashboard_id) {
            step = designer.grid_size > 0 ? designer.grid_size : 20;
        }
        designer.start_save_undo_state();
        var ids = [];
        data.widgets.forEach(function(copy) {
                copy.x = Math.max(0, copy.x + step);
                copy.y = Math.max(0, copy.y + step);
                var widget = JSON.parse(JSON.stringify(copy));
                widget.id = designer.document.next_id++;
                designer.document.widgets.push(widget);
                $("#page").append(designer.box_element(widget));
                ids.push(String(widget.id));
            });
        designer.end_save_undo_state();
        designer.selected_boxes = [];
        designer.scan();
        ids.forEach(function(id) {
                designer.selectbox(id, true);
            });
        designer.draw();
        designer.modified();
        return true;
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
        for (var j in json) {
            var unit = json[j];
            arr.push([unit.short, unit.long +" ("+ unit.short + ")"]);
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
        if (targetTagName === "input" || targetTagName === "textarea") return false;

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
                if (newCenterX < 0 || newCenterX > designer.canvas_width) {
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

    // True while a key event belongs to a field, so the editor leaves it alone
    "typing": function(e){
        var tag = e.target.tagName.toLowerCase();
        return tag === "input" || tag === "textarea" || e.target.isContentEditable;
    },

    "handle_delete_key_event": function(e){
        if (designer.typing(e)) return false;

        if (designer.selected_boxes.length > 0) {
            designer.delete_selected_boxes();
            return true;
        }
        return false;
    },

    "add_events": function(){

        // Double click to display widget options
        $(this.canvas).bind("dblclick", function(e){
                if (designer.selected_boxes.length == 1) {
                    $("#options-button").trigger("click");
                }
            });

        $(this.canvas).bind("touchstart mousedown", function(e){
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

                        var resize = designer.boxlist[selected_box];

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
                            designer.draw();
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
                        var type = designer.create;
                        designer.clear_create();
                        designer.add_widget(mx,my,type);
                    }
                }
            });

        $(this.canvas).bind("touchend touchcancel mouseup", function(e){
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

        $(this.canvas).bind("touchmove mousemove", function(e){
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
                if (mx < 0) mx = 0; else if (mx > designer.canvas_width) mx = designer.canvas_width;
                if (my < 0) my = 0;

                if (designer.create) {
                    designer.draw_ghost(mx,my);
                    return false;
                }

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
                            var resizelocal = designer.boxlist[selected_box];

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
                        });
                    designer.draw();
                    designer.modified();

                    return false;
                }
            });

        // Remove the ghost when the pointer leaves the canvas
        $(this.canvas).bind("mouseleave", function(e){
                if (designer.create) designer.draw();
            });

        // Key events
        $(window).keydown(function(e) {
                var keyCode = e.keyCode;
                switch (keyCode) {
                    case 27: // Escape cancels a pending widget
                        if (designer.create) designer.clear_create();
                        break;
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
                    case 67: // Ctrl+C copies the selection
                        if ((e.ctrlKey || e.metaKey) && !designer.typing(e) && designer.copy_selected_boxes()) {
                            e.preventDefault();
                        }
                        break;
                    case 88: // Ctrl+X cuts the selection
                        if ((e.ctrlKey || e.metaKey) && !designer.typing(e) && designer.cut_selected_boxes()) {
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

        // Ctrl+V. Browser paste event carries the system clipboard text
        // without a permission prompt, so it is used in place of the key.
        $(window).on("paste", function(e) {
                if (designer.typing(e)) return;
                var clipboardData = e.originalEvent.clipboardData;
                var text = clipboardData ? clipboardData.getData("text/plain") : "";
                if (designer.paste_text(text)) e.preventDefault();
            });

        // On save click
        $("#options-save").click(function(){
                // Checked again on save
                $("#widget_options_body").find(".options").each(function(){
                        designer.check_option($(this));
                    });
                var first_bad = $("#widget_options_body").find(".control-group.error").first();
                if (first_bad.length) {
                    first_bad.find(".options").first().focus();
                    return;
                }

                designer.start_save_undo_state();
                var selected_box = designer.selected_boxes[0];
                var selected_widget = designer.widget_by_id(selected_box);
                if (!selected_widget) return;
                if (!selected_widget.options) selected_widget.options = {};
                $(".options").each(function() {
                        var id = $(this).attr("id");
                        // Second select of a dropbox_other option, not an option itself
                        if (id.slice(-9) == "_dropdown") return;
                        if (id=="html"){
                            selected_widget.html = $(this).val();
                        }
                        else if (id=="text"){
                            selected_widget.text = $(this).val();
                        }
                        else if (id=="config"){
                            var config = null;
                            try { config = JSON.parse($(this).val()); } catch (e) {}
                            if (config && typeof config === "object") selected_widget.config = config;
                            else delete selected_widget.config;
                        }
                        else if (id.substring(0,6)=="colour" || $(this).attr("type")=="color"){
                            // Since colour values are generally prefixed with "#", and "#" isn't valid in URLs, we strip out the "#".
                            // It will be replaced by the value-checking in the actual plot function, so this won't cause issues.
                            // Colour options were once all named colour*, so the name is still checked.
                            var colour = $(this).val();
                            colour = colour.replace("#","");
                            // The None box beside a picker writes none in place of
                            // the colour the picker shows.
                            var none = $(".colour-none[data-for='"+$(this).attr("id")+"']");
                            if (none.length && none.is(":checked")) colour = "none";
                            selected_widget.options[id] = colour;
                        }
                        else if (id.indexOf("styleUnit") == 0){
                            //Get styleUnit* options and set it to boxlist array
                            designer.boxlist[selected_box][id]=parseInt($(this).val());
                        }
                        else {
                            selected_widget.options[id] = $(this).val();
                        }
                    });
                $("#widget_options").modal("hide");
                // draw writes the units into the widget, then the box is drawn
                // again from it and mounted again by render.js.
                designer.draw();
                designer.render_box(selected_box);
                designer.end_save_undo_state();
                designer.modified();
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

        $("#copy-button").click(function(event){
                designer.copy_selected_boxes();
            });

        $("#cut-button").click(function(event){
                designer.cut_selected_boxes();
            });

        // Reading the system clipboard needs a secure page and may ask
        // permission. Where it is refused the last copy made here is pasted.
        $("#paste-button").click(function(event){
                if (navigator.clipboard && navigator.clipboard.readText) {
                    navigator.clipboard.readText().then(designer.paste_text, function() {
                            designer.paste_text("");
                        });
                } else {
                    designer.paste_text("");
                }
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
                            var list = designer.document.widgets;
                            var at = designer.widget_index(selected_box);
                            if (at !== -1 && at < list.length - 1) {
                                list.splice(at + 1, 0, list.splice(at, 1)[0]);
                                var selected_box_element = $("#"+selected_box);
                                selected_box_element.insertAfter(selected_box_element.next());
                                need_redraw = true;
                            }
                        });
                    if (need_redraw) {
                        designer.draw();
                        designer.modified();
                        designer.end_save_undo_state("moveforward");
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
                            var list = designer.document.widgets;
                            var at = designer.widget_index(selected_box);
                            if (at > 0) {
                                list.splice(at - 1, 0, list.splice(at, 1)[0]);
                                var selected_box_element = $("#"+selected_box);
                                selected_box_element.insertBefore(selected_box_element.prev());
                                need_redraw = true;
                            }
                        });
                    if (need_redraw) {
                        designer.draw();
                        designer.modified();
                        designer.end_save_undo_state("movebackward");
                    } else {
                        designer.cancel_save_undo_state();
                    }
                }
            });

        $(document).on("keyup", ".input-is-other", function(event) {
                var input = $(event.target);
                var select = input.parent().prev().find("select");
                if (select.val() === "__other") {
                    input.data("last-value", input.val());
                }
            });
        $(document).on("change", ".select-with-other", function(event) {
                var select = $(event.target);
                var other = select.parent().next(".other");
                var input = other.find("input");
                var last_val = input.data("last-value");
                // if user selected "Other" option, show text box
                if (select.val() === "__other") {
                    other.removeClass("hidden");
                    if (last_val) input.val(last_val);
                    input.focus();
                } else {
                    input.val(select.val());
                    other.addClass("hidden");
                }
            });
    }
};
