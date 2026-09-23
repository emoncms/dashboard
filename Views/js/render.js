/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:  http://openenergymonitor.org

  Mounts a widget in each box under #page and keeps it fed, sized and taken
  down. A widget is a global object named <type>_widget with one method,
  mount(el, config, ctx), returning an instance with update, resize, destroy
  and optionally frame. See notes/WIDGET-INTERFACE.md.

  The designer's box model is described in designer.js. The page is the same
  markup whether it was rendered from the stored document or built by the
  designer, so this runs unchanged in both views.
*/

// Live values of every feed the viewer can see, by feed id, from feed/list.json.
var render_live = {};
// "tag:name" => feed id, so an option may name a feed either way.
var render_assoc = {};

// Box id => { el, type, instance, width, height, timer }
var render_instances = {};

var render_observer = null;
var render_mutations = null;

var RENDER_POLL_INTERVAL = 5000;
var RENDER_FRAME_RATE = 25;
var RENDER_RESIZE_DELAY = 150;
// Share of the distance a needle moves each frame, see render_curve.
var RENDER_CURVE_RATE = 0.15;

/* ── What every widget is given ──────────────────────────────────────── */

var render_feeds = {
    online: true,

    // Feed id as a string from an option value, which may be an id or a
    // tag:name pair. Empty when the pair names no feed the viewer can see.
    id: function(feed){
        var key = feed === undefined || feed === null ? "" : String(feed);
        if (key === "") return "";
        if (render_assoc[key] !== undefined) return String(render_assoc[key]);
        return key;
    },

    // Feed's row from the poll, or null.
    get: function(feed){
        var id = render_feeds.id(feed);
        if (id === "" || render_live[id] === undefined) return null;
        return render_live[id];
    }
};

var render_ctx = {
    feeds: render_feeds,
    history: function(options){ return chart_feed_data(options); },
    canvas: function(el){ return render_canvas(el); }
};

// A canvas filling the element. fit() sizes it to the element again and
// returns the context, for a resize.
function render_canvas(el){
    var canvas = document.createElement("canvas");
    el.innerHTML = "";
    el.appendChild(canvas);
    var api = {
        canvas: canvas,
        context: canvas.getContext("2d"),
        fit: function(){
            var width = el.clientWidth;
            var height = el.clientHeight;
            if (canvas.width !== width) canvas.width = width;
            if (canvas.height !== height) canvas.height = height;
            return api.context;
        }
    };
    api.fit();
    return api;
}

// One easing step towards a value, for a needle or a bar drawn each frame.
function render_curve(current, target){
    var to = parseFloat(target);
    if (!isFinite(to)) to = 0;
    var from = parseFloat(current);
    if (!isFinite(from)) from = 0;
    return from + (to - from) * RENDER_CURVE_RATE;
}

/* ── The widget lists, for the designer ──────────────────────────────── */

// Populate widgets with the *_widgetlist of every loaded widget.
function render_widgets_init(widget){
    for (var z in widget){
        var fn = window[widget[z] + "_widgetlist"];
        if (typeof fn === "function") $.extend(widgets, fn());
    }
}

// Convenience function for adding an option to a widget list entry.
function addOption(widget, optionKey, optionType, optionName, optionHint, optionData){
    widget["options"    ].push(optionKey);
    widget["optionstype"].push(optionType);
    widget["optionsname"].push(optionName);
    widget["optionshint"].push(optionHint);
    widget["optionsdata"].push(optionData);
}

/* ── Start ───────────────────────────────────────────────────────────── */

function render_widgets_start(){
    if (typeof ResizeObserver === "function") {
        render_observer = new ResizeObserver(render_resized);
    }

    render_mount();

    var page = document.getElementById("page");
    if (page && typeof MutationObserver === "function") {
        render_mutations = new MutationObserver(function(){ render_mount(); });
        render_mutations.observe(page, { childList: true });
    }

    render_poll();
    setInterval(render_poll, RENDER_POLL_INTERVAL);
    render_frames();
}

/* ── Mounting ────────────────────────────────────────────────────────── */

// Mounts a widget in every box under #page that has none, and takes down the
// instances whose box has gone. Called on start and after the boxes change.
function render_mount(){
    var page = document.getElementById("page");
    if (!page) return;

    for (var id in render_instances){
        var held = render_instances[id];
        if (!page.contains(held.el)) render_unmount(id);
    }

    var boxes = page.children;
    for (var i = 0; i < boxes.length; i++){
        var el = boxes[i];
        var id = el.id;
        if (!id) continue;

        var held = render_instances[id];
        if (held && held.el === el) continue;
        // Designer undo puts back page html, so a box may be a new element
        // with the id of one already mounted.
        if (held) render_unmount(id);

        var type = String(el.className || "").split(/\s+/)[0];
        var widget = window[type + "_widget"];
        if (!widget || typeof widget.mount !== "function") continue;

        var instance;
        try {
            instance = widget.mount(el, render_config(el), render_ctx);
        } catch (err) {
            console.error("widget " + type + " " + id + " failed to mount", err);
            continue;
        }
        if (!instance) continue;

        render_instances[id] = {
            el: el, type: type, instance: instance,
            width: el.clientWidth, height: el.clientHeight, timer: 0
        };
        if (render_observer) render_observer.observe(el);

        if (typeof instance.update === "function") {
            try { instance.update(render_feeds); }
            catch (err) { console.error("widget " + type + " " + id + " failed to update", err); }
        }
    }
}

function render_unmount(id){
    var held = render_instances[id];
    if (!held) return;
    delete render_instances[id];
    clearTimeout(held.timer);
    if (render_observer) render_observer.unobserve(held.el);
    if (typeof held.instance.destroy === "function") {
        try { held.instance.destroy(); }
        catch (err) { console.error("widget " + held.type + " " + id + " failed to destroy", err); }
    }
}

// Box attributes as the widget's settings. Every attribute but id,
// class and style, which are the box, plus the id. Attribute names are
// lowercase, as the browser stores them.
function render_config(el){
    var config = { id: el.id };
    for (var i = 0; i < el.attributes.length; i++){
        var name = el.attributes[i].name;
        if (name === "id" || name === "class" || name === "style") continue;
        config[name] = el.attributes[i].value;
    }
    return config;
}

/* ── The poll ────────────────────────────────────────────────────────── */

function render_poll(){
    var query = path + "feed/list.json";
    var params = [];
    if (typeof public_userid !== "undefined" && public_userid > 0) params.push("userid=" + public_userid);
    if (typeof apikey === "string" && apikey) params.push("apikey=" + encodeURIComponent(apikey));
    if (params.length) query += "?" + params.join("&");

    $.ajax({
            type: "GET",
            url: query,
            dataType: "json",
            success: function(data){
                var live = {};
                var assoc = {};
                for (var z in data){
                    var row = data[z];
                    if (!row || row.id === undefined) continue;
                    live[String(row.id)] = row;
                    assoc[row.tag + ":" + row.name] = row.id;
                }
                render_live = live;
                render_assoc = assoc;
                render_feeds.online = true;
                render_update();
            },
            error: function(){
                render_feeds.online = false;
                render_update();
            }
        });
}

function render_update(){
    for (var id in render_instances){
        var held = render_instances[id];
        if (typeof held.instance.update !== "function") continue;
        try { held.instance.update(render_feeds); }
        catch (err) { console.error("widget " + held.type + " " + id + " failed to update", err); }
    }
}

/* ── Size ────────────────────────────────────────────────────────────── */

// A box changed size. The instance is told once the size has settled, so a
// drag that changes it many times draws once.
function render_resized(entries){
    for (var i = 0; i < entries.length; i++){
        var el = entries[i].target;
        var held = render_instances[el.id];
        if (!held || held.el !== el) continue;
        if (el.clientWidth === held.width && el.clientHeight === held.height) continue;
        held.width = el.clientWidth;
        held.height = el.clientHeight;
        clearTimeout(held.timer);
        held.timer = setTimeout(render_resize_one(held, el.id), RENDER_RESIZE_DELAY);
    }
}

function render_resize_one(held, id){
    return function(){
        if (render_instances[id] !== held) return;
        if (typeof held.instance.resize !== "function") return;
        try { held.instance.resize(); }
        catch (err) { console.error("widget " + held.type + " " + id + " failed to resize", err); }
    };
}

/* ── Frames ──────────────────────────────────────────────────────────── */

// Calls frame(now) on every instance that has one, at RENDER_FRAME_RATE.
function render_frames(){
    setTimeout(function(){
            window.requestAnimationFrame(render_frames);
            var now = Date.now();
            for (var id in render_instances){
                var held = render_instances[id];
                if (typeof held.instance.frame !== "function") continue;
                try { held.instance.frame(now); }
                catch (err) { console.error("widget " + held.type + " " + id + " failed to draw", err); }
            }
        }, 1000 / RENDER_FRAME_RATE);
}
