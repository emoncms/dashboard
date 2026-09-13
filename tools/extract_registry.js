/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
  ---------------------------------------------------------------------
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

  Generates the PHP readable widget registry from the existing JavaScript
  widget lists.

    node tools/extract_registry.js [--root=/var/www/emoncms] [--check]

  Each widget list is a JavaScript object built at runtime, partly by literal
  and partly by addOption calls, so the only accurate way to read it is to run
  it. This script evaluates each *_widgetlist function in a sandbox and writes
  what the server needs to validate stored dashboards to a *_widgets.json file
  beside the render script.

  The JSON holds the validation contract only: which options a widget accepts,
  the type of each one and the permitted values where the list is fixed. Option
  labels, hints and box geometry stay in the JavaScript, which is where the
  designer reads them.

  With --check nothing is written. The generated JSON is compared against what
  is on disk and the exit code is 1 if they differ, so drift between the two
  can be caught after a widget changes.
*/

const fs = require('fs');
const path = require('path');
const vm = require('vm');
const { execFileSync } = require('child_process');

const args = process.argv.slice(2);
const opt = (name, def) => {
    const hit = args.find(a => a.startsWith('--' + name + '='));
    return hit ? hit.slice(name.length + 3) : def;
};
const CHECK = args.includes('--check');
const ROOT = path.resolve(opt('root', path.join(__dirname, '..', '..', '..')));
const MODULES = path.join(ROOT, 'Modules');

// Marker returned for any global the widget list reads that we cannot supply,
// such as the multigraph and saved graph lists that vis_widget.php and
// graph_widget.php build per user. A dropbox filled from one of these has no
// fixed set of values, so the registry marks it dynamic instead.
const DYNAMIC = Symbol('dynamic');

// The unit list offered by the dropbox_other options. The designer fetches
// this over ajax from Lib/units.php, see designer.get_SI. It is a static file
// rather than per user data, so the real list can be read here.
function si_units() {
    const file = path.join(ROOT, 'Lib', 'units.php');
    if (!fs.existsSync(file)) return DYNAMIC;
    try {
        const units = JSON.parse(execFileSync('php', [file], { encoding: 'utf8' }));
        return units.map(u => [u.short, u.long + ' (' + u.short + ')']);
    } catch (e) {
        console.error('warning: could not read ' + path.relative(ROOT, file) + ', unit lists left dynamic');
        return DYNAMIC;
    }
}

const SI = si_units();

function sandbox() {
    // Some widget lists wire up jQuery handlers while they build, see
    // button_events in button_render.js. Nothing is rendered here, so a stub
    // that accepts any call and any property lets them run.
    const chain = new Proxy(function () {}, {
        get: () => chain,
        apply: () => chain,
        construct: () => chain
    });

    const base = {
        console: console,
        $: chain,
        jQuery: chain,
        window: {},
        document: chain,
        _SI: SI,
        // Translation shims. Labels are not stored in the registry, so the
        // identity function is enough to let the widget list build.
        _Tr: s => s,
        _Tr_Vis: s => s,
        // Copied from Views/js/render.js. Widget lists call this to append an
        // option to the parallel arrays.
        addOption: (widget, key, type, name, hint, data) => {
            widget['options'].push(key);
            widget['optionstype'].push(type);
            widget['optionsname'].push(name);
            widget['optionshint'].push(hint);
            widget['optionsdata'].push(data);
        }
    };

    // Reading an undeclared global normally throws. Return the marker instead
    // so a widget list that depends on per user data still builds.
    return new Proxy(base, {
        has: () => true,
        get: (target, key) => {
            if (key === Symbol.unscopables) return undefined;
            if (key in target) return target[key];
            return DYNAMIC;
        }
    });
}

// Runs one JavaScript file and returns the widget object it defines. Files
// under widget/ define a named *_widgetlist function. Views/js/widgetlist.js
// is a bare var widgets assignment, so it is handled by name.
function widgets_from(file, fname) {
    const src = fs.readFileSync(file, 'utf8');
    const context = vm.createContext(sandbox());
    const call = fname ? `\n;${fname}();` : '\n;widgets;';
    return vm.runInContext(src + call, context, { filename: file, timeout: 10000 });
}

// Turns one widget definition into the options map the server validates
// against. Values are kept as strings because the render scripts compare
// them with strict equality, see draw_feedvalue in feedvalue_render.js.
function options_of(def) {
    const keys = def['options'] || [];
    const types = def['optionstype'] || [];
    const data = def['optionsdata'] || [];
    const options = {};

    for (let i = 0; i < keys.length; i++) {
        const key = keys[i];
        if (typeof key !== 'string' || key === '') continue;
        const option = { type: types[i] || 'value' };
        const values = value_list(data[i]);

        if (values === DYNAMIC) {
            // Filled from the database per user, so the set is not knowable
            // here. The validator checks the shape of the value only.
            option.dynamic = true;
        } else if (values && option.type === 'dropbox') {
            option.values = values;
        } else if (values && option.type === 'dropbox_other') {
            // The designer offers these in a list but writes whatever the
            // user types in the Other box, so they are suggestions rather
            // than a closed set.
            option.suggested = values;
        }
        options[key] = option;
    }
    return options;
}

// optionsdata holds [value, label] pairs for a dropbox, a bare default colour
// string for a colour picker, and nothing at all for a free text value.
function value_list(entry) {
    if (entry === DYNAMIC) return DYNAMIC;
    if (!Array.isArray(entry)) return null;
    const values = [];
    for (const pair of entry) {
        if (pair === DYNAMIC) return DYNAMIC;
        if (!Array.isArray(pair) || pair.length === 0) continue;
        values.push(String(pair[0]));
    }
    return values.length ? values : null;
}

// The source files, in the order loadwidgets.php loads them. The output path
// follows the same naming as load_widget, so a widget directory holds
// name_render.js beside name_widgets.json.
function sources() {
    const found = [];
    const dashboard = path.join(MODULES, 'dashboard');

    // Text and container widgets. These have no render script, the designer
    // reads them straight from widgetlist.js.
    found.push({
        src: path.join(dashboard, 'Views', 'js', 'widgetlist.js'),
        fname: null,
        out: path.join(dashboard, 'widget', 'dashboard_widgets.json')
    });

    for (const module of fs.readdirSync(MODULES).sort()) {
        const base = path.join(MODULES, module, 'widget');
        if (!isdir(base)) continue;
        add_if_present(found, base, module);
        for (const entry of fs.readdirSync(base).sort()) {
            const nested = path.join(base, entry);
            if (isdir(nested)) add_if_present(found, nested, entry);
        }
    }
    return found;
}

function add_if_present(found, folder, name) {
    const src = path.join(folder, name + '_render.js');
    if (!fs.existsSync(src)) return;
    if (!/function\s+[A-Za-z0-9_$]+_widgetlist/.test(fs.readFileSync(src, 'utf8'))) return;
    found.push({ src: src, fname: name + '_widgetlist', out: path.join(folder, name + '_widgets.json') });
}

// Regenerating a declaration must not lose the hand written legacy lists, so
// they are read back out of the file being replaced.
function legacy_blocks(existing, outrel) {
    const kept = {};
    if (!existing) return kept;
    try {
        const previous = JSON.parse(existing);
        for (const name of Object.keys(previous.widgets || {})) {
            const legacy = previous.widgets[name].legacy;
            if (legacy && typeof legacy === 'object' && Object.keys(legacy).length) {
                kept[name] = legacy;
            }
        }
    } catch (e) {
        console.error('warning: could not read ' + outrel + ', any legacy list in it is lost');
    }
    return kept;
}

function isdir(p) {
    try { return fs.statSync(p).isDirectory(); } catch (e) { return false; }
}

let written = 0, unchanged = 0, differs = 0, failed = 0, types = 0;

// A dropbox is only marked dynamic when its list comes from a global this
// script cannot supply. That switches off value checking for the option, so
// the list is reported rather than left for someone to notice later.
const dynamic = [];

for (const source of sources()) {
    const rel = path.relative(ROOT, source.src);
    let list;
    try {
        list = widgets_from(source.src, source.fname);
    } catch (e) {
        console.error('FAIL  ' + rel + ': ' + e.message);
        failed++;
        continue;
    }
    if (!list || typeof list !== 'object') {
        console.error('FAIL  ' + rel + ': no widget object returned');
        failed++;
        continue;
    }

    const outrel = path.relative(ROOT, source.out);
    const existing = fs.existsSync(source.out) ? fs.readFileSync(source.out, 'utf8') : null;
    const kept = legacy_blocks(existing, outrel);

    const out = { version: 1, source: rel.split(path.sep).join('/'), widgets: {} };
    for (const name of Object.keys(list).sort()) {
        const widget = { options: options_of(list[name]) };
        for (const option of Object.keys(widget.options)) {
            if (widget.options[option].dynamic) dynamic.push(name + '.' + option);
        }
        // Options the designer no longer offers but the render script still
        // reads, so stored dashboards keep working. Hand written, see the
        // legacy note in tools/SCHEMA.md.
        if (kept[name]) widget.legacy = kept[name];
        out.widgets[name] = widget;
        types++;
    }

    const json = JSON.stringify(out, null, 2) + '\n';

    if (CHECK) {
        if (existing === json) { unchanged++; continue; }
        console.error((existing === null ? 'MISSING  ' : 'STALE    ') + outrel);
        differs++;
        continue;
    }
    if (existing === json) { unchanged++; continue; }
    fs.writeFileSync(source.out, json);
    console.log('wrote ' + outrel + ' (' + Object.keys(out.widgets).length + ' types)');
    written++;
}

if (dynamic.length) {
    console.log('\nfilled per user, so their values are not checked: ' + dynamic.join(' '));
    console.log('any option here that should have a fixed list is a gap in this script\n');
}

console.log((CHECK ? 'checked' : 'done') + ': ' + types + ' widget types, '
    + (CHECK ? differs + ' stale, ' : written + ' written, ')
    + unchanged + ' unchanged, ' + failed + ' failed');

process.exit(failed || differs ? 1 : 0);
