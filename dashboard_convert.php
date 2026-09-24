<?php

/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project:
 http://openenergymonitor.org
*/

/*
 Converts stored dashboard HTML into the JSON document described in
 notes/SCHEMA.md. Reading only, it returns a document and does not write
 anything.

   require_once "Modules/dashboard/dashboard_convert.php";
   $result = dashboard_convert($html);
   $result['document']   the JSON document, or null if nothing could be read
   $result['warnings']   what was dropped or changed, also inside the document

 The content is parsed once and everything kept is taken from the parsed tree.
 Nothing is repaired with a regular expression, and no string that has not been
 walked is carried through to the output.

 Stored content produces a lot of libxml complaints and parses correctly all the
 same, see the parse error section of SCHEMA.md, so the error count is read but
 never used to reject a dashboard.
*/

defined('EMONCMS_EXEC') or die('Restricted access');

require_once dirname(__FILE__) . "/widget_registry.php";

// Converters for the old text and container widgets, see
// notes/TEXT-IMAGE-PANEL.md. Not called by the converter or the renderer.
require_once dirname(__FILE__) . "/dashboard_convert_text.php";
require_once dirname(__FILE__) . "/dashboard_convert_panel.php";

// Replaces the old widgets of a document with the new ones. Called on save
// and by tools/migrate.php.
require_once dirname(__FILE__) . "/dashboard_migrate.php";

define('DASHBOARD_CONVERTER_VERSION', 1);

// Version of the document the converter writes and the renderer draws. A
// version 1 document has no widget ids and is upgraded on load, see
// dashboard_upgrade_document in dashboard_migrate.php.
define('DASHBOARD_DOCUMENT_VERSION', 2);

/*
 The allowlists.

 These are functions rather than variables because this file is required from
 inside a class method in dashboard_model.php. A variable assigned at the top
 level of an included file takes the scope of whatever included it, so as
 globals they were silently empty when the converter ran from the model.
*/

// Elements allowed in the html of a text or container widget.
function dashboard_convert_allowed_elements()
{
    return [
        'a', 'b', 'strong', 'i', 'em', 'u', 'sub', 'sup', 'br', 'p', 'div', 'span',
        'center', 'font', 'small', 'h1', 'h2', 'h3', 'h4', 'h5',
        'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'img'
    ];
}

// Elements allowed in a text widget. Styling is set with options, so only
// emphasis, line breaks, links and subscript are kept. sub is included
// because units are often written with it.
function dashboard_convert_inline_elements()
{
    // The editor prints them in this order, see content_problem in designer.js.
    return ['b', 'i', 'u', 'sub', 'a', 'br'];
}

// Elements removed with everything inside them. Anything else that is not
// allowed is unwrapped instead, so the text inside it survives.
function dashboard_convert_stripped_elements()
{
    return [
        'script', 'style', 'meta', 'title', 'link', 'object', 'embed', 'iframe',
        'svg', 'form', 'input', 'button', 'select', 'textarea', 'canvas', 'applet',
        'base', 'frame', 'frameset', 'noscript', 'template',
        'xmp', 'noembed', 'noframes', 'plaintext'
    ];
}

// Attributes allowed per element, on top of style which any of them may carry.
function dashboard_convert_allowed_attributes()
{
    return [
        'a' => ['href', 'target', 'title', 'rel'],
        'img' => ['src', 'alt', 'width', 'height', 'referrerpolicy'],
        'font' => ['color', 'face', 'size'],
        'table' => ['border', 'cellpadding', 'cellspacing'],
        'td' => ['colspan', 'rowspan', 'align'],
        'th' => ['colspan', 'rowspan', 'align']
    ];
}

// Style properties allowed, both on a widget box and inside its html. Taken
// from what stored dashboards use, see the style property counts in the census.
// None of them can fetch or run anything: a value carrying url(), expression()
// or a position declaration is dropped whatever the property is, see
// dashboard_convert_style_value_allowed.
function dashboard_convert_allowed_styles()
{
    return [
        // Text
        'color', 'font', 'font-size', 'font-family', 'font-weight', 'font-style',
        'letter-spacing', 'line-height', 'text-align', 'text-decoration',
        'text-transform', 'vertical-align', 'white-space',
        // Background, border and the rest of the paint
        'background', 'background-color', 'border', 'border-top', 'border-right',
        'border-bottom', 'border-left', 'border-color', 'border-style',
        'border-width', 'border-radius', 'border-collapse', 'border-spacing',
        'box-shadow', 'opacity',
        // Box
        'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
        'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
        'width', 'height', 'max-width', 'min-width', 'max-height', 'min-height',
        'display', 'visibility', 'overflow', 'float', 'table-layout',
        'align-items', 'justify-content', 'flex-wrap',
        // Rotation only, see dashboard_convert_style_rotate_only
        'transform'
    ];
}

// Style properties of a widget box that the designer writes and the renderer
// puts back, so they are dropped without a warning. The margin longhands are
// here with the shorthand: the renderer writes margin: 0 on every box, and a
// margin-top written after it would win and move the box off the geometry the
// document gives it. Inside the html of a widget they are kept.
function dashboard_convert_box_styles()
{
    return ['position', 'top', 'left', 'width', 'height',
        'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left'
    ];
}

// Attributes added by browser extensions to the page the editor saved.
function dashboard_convert_extension_attributes()
{
    return [
        'bis_skin_checked', '_msttexthash', '_msthash', 'wfd-id',
        'data-darkreader-inline-color', 'data-dashlane-frameid',
        'data-ruffle-polyfilled', 'data-ol-has-click-handler',
        '__gchrome_childframeremotetoken'
    ];
}

/**
 * Converts page html to a document.
 *
 * Widget ids are numbered from the array index. Stored html carries the
 * designer's counter, which collides within a dashboard. The editor no longer
 * posts html, see notes/EDITOR.md, so this is the migration only.
 *
 * @param string $html
 * @return array document and warnings
 */
function dashboard_convert($html)
{
    $warnings = [];

    if (trim($html) === '') {
        return dashboard_convert_result([], $warnings);
    }

    $root = dashboard_convert_parse($html);
    if ($root === null) {
        dashboard_convert_warn($warnings, null, 'unparsable', '');
        return ['document' => null, 'warnings' => $warnings];
    }

    $registry = widget_registry();
    $widgets = [];

    dashboard_convert_boxes($root, $widgets, $registry, $warnings, true);

    return dashboard_convert_result($widgets, $warnings);
}

// Reads the widget boxes of a page. $page is true for the page itself, false
// for an element being looked through, see dashboard_convert_holds_box.
function dashboard_convert_boxes($parent, &$widgets, $registry, &$warnings, $page)
{
    foreach ($parent->childNodes as $node) {
        if ($node->nodeType === XML_TEXT_NODE) {
            // Only reported for the page. Text beside a box inside a wrapper is
            // the wrapper's own, and is not a stray line someone left behind.
            if ($page && trim($node->textContent) !== '') {
                dashboard_convert_warn(
                    $warnings,
                    null,
                    'text_outside_widget',
                    dashboard_convert_snippet($node->textContent)
                );
            }
            continue;
        }
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            continue;
        }

        $widget = dashboard_convert_widget($node, count($widgets), $registry, $warnings);
        if ($widget !== null) {
            $widgets[] = $widget;
            continue;
        }

        // Not a box. A stored page is sometimes wrapped in a tag that was
        // opened and never closed, a stray <b> in the corpus, and everything
        // after it parses as its content. The boxes are ordinary ones with
        // their geometry and options intact, so they are read rather than lost
        // with the wrapper. A box is never looked for inside a widget, which is
        // where a nested widget lives and is dropped on purpose.
        if (dashboard_convert_holds_box($node, $registry)) {
            dashboard_convert_boxes($node, $widgets, $registry, $warnings, false);
        }
    }
}

// True when a box sits somewhere inside this element, so there is a reason to
// look through it. Stripped elements are not looked into: what is inside a
// textarea is text on the page today, not a widget, and the migration does not
// draw what a browser does not.
function dashboard_convert_holds_box($node, $registry)
{
    if (in_array(strtolower($node->nodeName), dashboard_convert_stripped_elements())) {
        return false;
    }

    foreach ($node->childNodes as $child) {
        if ($child->nodeType !== XML_ELEMENT_NODE) {
            continue;
        }

        $class = $child->hasAttribute('class') ? trim($child->getAttribute('class')) : '';
        if ($class !== '' && isset($registry[$class])) {
            return true;
        }
        if (dashboard_convert_holds_box($child, $registry)) {
            return true;
        }
    }

    return false;
}

function dashboard_convert_result($widgets, $warnings)
{
    $next_id = dashboard_convert_ids($widgets);
    $document = [
        'version' => DASHBOARD_DOCUMENT_VERSION,
        'next_id' => $next_id,
        'widgets' => $widgets,
        'meta' => [
            'converted_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'converter' => DASHBOARD_CONVERTER_VERSION,
            'warnings' => $warnings
        ]
    ];
    return ['document' => $document, 'warnings' => $warnings];
}

// Numbers every widget from its index, which is what the renderer drew a
// document with no ids as, see the widget ids section of SCHEMA.md. Returns
// the counter to store.
function dashboard_convert_ids(&$widgets)
{
    foreach ($widgets as $index => $widget) {
        $widgets[$index] = ['id' => $index + 1] + $widget;
    }
    return count($widgets) + 1;
}

// ---------------------------------------------------------------------------
// Parsing
// ---------------------------------------------------------------------------

// Null bytes are removed before parsing, as the HTML5 parser in libxml 2.14
// and later ignores them. Earlier libxml (2.9 on Ubuntu 24.04) drops the rest
// of the document after a null byte in an attribute value, including any
// widgets that follow.
function dashboard_convert_parse($html)
{
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    libxml_clear_errors();
    $ok = $doc->loadHTML(
        '<div>' . dashboard_convert_to_entities(str_replace("\0", "", $html)) . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors(false);
    if (!$ok || !$doc->documentElement) {
        return null;
    }
    return $doc->documentElement;
}

// libxml reads bytes as latin1 unless it is told otherwise, so non ASCII text
// is turned into numeric entities before parsing and back again after.
function dashboard_convert_to_entities($html)
{
    return mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
}

function dashboard_convert_from_entities($html)
{
    return mb_decode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
}

// ---------------------------------------------------------------------------
// Widgets
// ---------------------------------------------------------------------------

function dashboard_convert_widget($node, $index, $registry, &$warnings)
{
    $class = $node->hasAttribute('class') ? trim($node->getAttribute('class')) : '';

    if ($class === '') {
        dashboard_convert_warn(
            $warnings,
            $index,
            'widget_without_type',
            strtolower($node->nodeName)
        );
        return null;
    }

    // A widget type is one token because it is the class attribute of the box.
    // More than one is hand written markup rather than a widget the designer
    // wrote, and the renderer will not draw a type carrying a space, so it is
    // dropped here rather than written into a document that cannot be drawn.
    if (preg_match('/\s/', $class)) {
        dashboard_convert_warn(
            $warnings,
            $index,
            'widget_type_not_one_token',
            preg_replace('/\s+/', ' ', $class)
        );
        return null;
    }
    $type = $class;

    $known = isset($registry[$type]);

    // Read as written and settled by dashboard_convert_ids once every box on
    // the page has been read.
    $widget = [
        'id' => $node->hasAttribute('id') ? $node->getAttribute('id') : '',
        'type' => $type
    ];

    $style = $node->hasAttribute('style') ? $node->getAttribute('style') : '';
    $declarations = dashboard_convert_parse_style($style);

    $widget += dashboard_convert_geometry($declarations, $index, $warnings);

    if (
        isset($declarations['position'])
        && strtolower(trim($declarations['position'])) === 'fixed'
    ) {
        dashboard_convert_warn($warnings, $index, 'position_fixed_dropped', $type);
    }

    $widget['options'] = dashboard_convert_options($node, $type, $known, $index, $warnings);

    if ($known) {
        $config = dashboard_convert_config($node, $type, $index, $warnings);
        if ($config !== null) {
            $widget['config'] = $config;
        }
    }

    if (!$known) {
        // Kept as a placeholder, with its geometry and its box styling but
        // without its attributes, see the unknown widget types section of
        // SCHEMA.md.
        $widget['unknown'] = true;
        dashboard_convert_warn($warnings, $index, 'widget_type_unknown', $type);
    }

    $holds_html = dashboard_convert_holds_html($type, $known, $registry);
    $holds_text = dashboard_convert_holds_text($type, $known, $registry);

    if ($holds_html) {
        $html = dashboard_convert_html($node, $index, $registry, $warnings);
        if ($html !== '') {
            $widget['html'] = $html;
        }
    } elseif ($holds_text) {
        $text = dashboard_convert_text($node, $index, $registry, $warnings);
        if ($text !== '') {
            $widget['text'] = $text;
        }
    } else {
        dashboard_convert_check_discarded($node, $type, $index, $registry, $warnings);
    }

    // Author written box styling. A deployed data widget has its box style
    // written by the render script at draw time, so what is stored is
    // generated and is dropped without a warning. Text boxes, containers and
    // placeholders keep theirs.
    if ($holds_html || !$known) {
        $box = dashboard_convert_styles($declarations, $index, $warnings, true);
        if (count($box)) {
            $widget['style'] = $box;
        }
    }

    return $widget;
}

// ---------------------------------------------------------------------------
// Config
// ---------------------------------------------------------------------------

// A widget whose settings do not fit in attributes holds them in one nested
// config, which travels on the page as a json attribute and is stored as its
// own field in the document. The chart of a graph widget is the first of
// these, see the inline config section of SCHEMA.md.
//
// Returns the config to store, or null when there is none to keep.
function dashboard_convert_config($node, $type, $index, &$warnings)
{
    $contract = widget_registry_config($type);
    if ($contract === false) {
        return null;
    }
    if (!$node->hasAttribute('config')) {
        return null;
    }

    $raw = trim($node->getAttribute('config'));
    if ($raw === '') {
        return null;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        dashboard_convert_warn(
            $warnings,
            $index,
            'config_unreadable',
            dashboard_convert_snippet($raw)
        );
        return null;
    }

    return dashboard_convert_config_valid($decoded, $type, $index, $warnings);
}

// Checks a decoded config against what the widget declares. Run on the way in
// and again on the way out, the same as options, so a document that reached
// the column some other way cannot put anything on the page that the
// declaration would have rejected.
//
// @return array|null
function dashboard_convert_config_valid($decoded, $type, $index, &$warnings)
{
    $contract = widget_registry_config($type);
    if ($contract === false || !is_array($decoded)) {
        return null;
    }

    $config = [];
    foreach ($decoded as $block => $value) {
        if (!is_string($block) || !isset($contract[$block])) {
            dashboard_convert_warn(
                $warnings,
                $index,
                'config_block_unknown',
                dashboard_convert_snippet((string) $block)
            );
            continue;
        }
        if (!is_array($value)) {
            dashboard_convert_warn($warnings, $index, 'config_block_unreadable', $block);
            continue;
        }

        // A block is either one set of entries, such as the state of a graph,
        // or a list of them, such as its feeds.
        if (dashboard_convert_is_list($value)) {
            $list = [];
            foreach ($value as $entry) {
                if (!is_array($entry) || dashboard_convert_is_list($entry)) {
                    dashboard_convert_warn($warnings, $index, 'config_entry_unreadable', $block);
                    continue;
                }
                $kept = dashboard_convert_config_entries($entry, $contract[$block], $block, $index, $warnings);
                if (count($kept)) {
                    $list[] = $kept;
                }
            }
            if (count($list)) {
                $config[$block] = $list;
            }
        } else {
            $kept = dashboard_convert_config_entries($value, $contract[$block], $block, $index, $warnings);
            if (count($kept)) {
                $config[$block] = $kept;
            }
        }
    }

    return count($config) ? $config : null;
}

// One set of config entries. Values are stored as strings, the same as option
// values and for the same reason: the render scripts compare them as strings.
// A json true or false is stored as 1 or 0, which is what a boolean option
// holds and what the widget lists coerce anyway.
function dashboard_convert_config_entries($entries, $declared, $block, $index, &$warnings)
{
    $kept = [];
    foreach ($entries as $name => $value) {
        if (!is_string($name) || !isset($declared[$name])) {
            dashboard_convert_warn(
                $warnings,
                $index,
                'config_entry_unknown',
                $block . '.' . dashboard_convert_snippet((string) $name)
            );
            continue;
        }
        if (is_array($value) || $value === null) {
            dashboard_convert_warn(
                $warnings,
                $index,
                'config_value_invalid',
                $block . '.' . $name
            );
            continue;
        }
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }
        $value = (string) $value;

        // An empty entry is kept unchecked, the same as an empty option.
        if ($value !== '' && !dashboard_convert_option_valid($declared[$name], $value)) {
            dashboard_convert_warn(
                $warnings,
                $index,
                'config_value_dropped',
                $block . '.' . $name . '=' . dashboard_convert_snippet($value)
            );
            continue;
        }
        $kept[$name] = $value;
    }
    return $kept;
}

// True for a json array rather than a json object. json_decode gives both as
// php arrays, and the keys are what tells them apart.
function dashboard_convert_is_list($value)
{
    if (!is_array($value)) {
        return false;
    }
    if (count($value) === 0) {
        return true;
    }
    return array_keys($value) === range(0, count($value) - 1);
}

// A widget may hold html when the registry says it has an html option, which
// covers paragraph, heading and heading-center. The containers declare no
// options at all and hold hand built tables, so they are included by name.
function dashboard_convert_holds_html($type, $known, $registry)
{
    if (substr($type, 0, 10) === 'Container-') {
        return true;
    }
    if (!$known) {
        return false;
    }

    foreach ($registry[$type]['options'] as $option) {
        if ($option['type'] === 'html') {
            return true;
        }
    }
    return false;
}

// A widget holds text when the registry gives it an option of type text.
// Only the text widget does. Text is checked against
// dashboard_convert_inline_elements and stored in its own field.
function dashboard_convert_holds_text($type, $known, $registry)
{
    if (!$known) {
        return false;
    }

    foreach ($registry[$type]['options'] as $option) {
        if ($option['type'] === 'text') {
            return true;
        }
    }
    return false;
}

// The element holding the widget content. The text widget render script
// draws a wrapper inside the box so a rotation turns the text and not the box.
// The wrapper is generated and not stored. See text_wrapper in
// widget/text/text_render.js.
function dashboard_convert_text_body($node)
{
    foreach ($node->childNodes as $child) {
        if ($child->nodeType !== XML_ELEMENT_NODE) {
            continue;
        }
        if (strtolower($child->nodeName) !== 'div') {
            continue;
        }
        $class = $child->hasAttribute('class') ? trim($child->getAttribute('class')) : '';
        if ($class === 'text-content') {
            return $child;
        }
    }
    return $node;
}

// The children of a data widget are all generated: the canvas the render
// script draws on, the iframe a chart widget built, the tooltip divs that dial,
// bar and thermometer inject, and the last reading left in the box. None of it
// is worth a warning. A widget nested inside one is worth a warning, because
// it is something the author put there and it is being dropped.
function dashboard_convert_check_discarded($node, $type, $index, $registry, &$warnings)
{
    $stack = [$node];
    while (count($stack)) {
        $current = array_pop($stack);
        foreach ($current->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $class = $child->hasAttribute('class') ? trim($child->getAttribute('class')) : '';
            if ($class !== '' && isset($registry[$class])) {
                dashboard_convert_warn(
                    $warnings,
                    $index,
                    'nested_widget_dropped',
                    "$class inside $type"
                );
                continue;
            }
            if (strtolower($child->nodeName) === 'iframe' && !dashboard_convert_draws_iframe($type, $registry)) {
                dashboard_convert_warn(
                    $warnings,
                    $index,
                    'iframe_dropped',
                    dashboard_convert_snippet($child->getAttribute('src'))
                );
            }
            $stack[] = $child;
        }
    }
}

// The graph widget drew in an iframe before it drew in the page, and the four
// retired vis types are declared as iframe widgets, see widget_registry.php.
function dashboard_convert_draws_iframe($type, $registry)
{
    if (!isset($registry[$type])) {
        return false;
    }
    return $registry[$type]['module'] === 'graph' || !empty($registry[$type]['iframe']);
}

// ---------------------------------------------------------------------------
// Geometry
// ---------------------------------------------------------------------------

// The designer writes top, left, width and height onto the box, and records
// whether width and height were set in per cent, see scan and draw in
// Views/js/designer.js.
function dashboard_convert_geometry($declarations, $index, &$warnings)
{
    $geometry = [
        'x' => dashboard_convert_length($declarations, 'left'),
        'y' => dashboard_convert_length($declarations, 'top'),
        'w' => dashboard_convert_length($declarations, 'width'),
        'h' => dashboard_convert_length($declarations, 'height'),
        'wunit' => dashboard_convert_unit($declarations, 'width'),
        'hunit' => dashboard_convert_unit($declarations, 'height')
    ];

    foreach (['left', 'top', 'width', 'height'] as $property) {
        if (!isset($declarations[$property])) {
            dashboard_convert_warn($warnings, $index, 'geometry_missing', $property);
        }
    }

    if ($geometry['w'] < 0) {
        $geometry['w'] = 0;
    }
    if ($geometry['h'] < 0) {
        $geometry['h'] = 0;
    }

    return $geometry;
}

function dashboard_convert_length($declarations, $property)
{
    if (!isset($declarations[$property])) {
        return 0;
    }
    if (!preg_match('/-?\d+(\.\d+)?/', $declarations[$property], $match)) {
        return 0;
    }
    return (int) round((float) $match[0]);
}

function dashboard_convert_unit($declarations, $property)
{
    if (!isset($declarations[$property])) {
        return 'px';
    }
    return strpos($declarations[$property], '%') === false ? 'px' : 'pc';
}

// ---------------------------------------------------------------------------
// Options
// ---------------------------------------------------------------------------

function dashboard_convert_options($node, $type, $known, $index, &$warnings)
{
    $options = [];
    $broken = dashboard_convert_style_broke_out($node);
    $takes_config = $known && widget_registry_config($type) !== false;

    foreach ($node->attributes as $attribute) {
        $name = $attribute->nodeName;
        $value = $attribute->nodeValue;

        if ($name === 'id' || $name === 'class' || $name === 'style') {
            continue;
        }

        // Not an option. It carries the nested config of a widget that
        // declares one, read by dashboard_convert_config.
        if ($name === 'config' && $takes_config) {
            continue;
        }

        $artefact = dashboard_convert_artefact($name, $type, $known, $broken, $value);
        if ($artefact !== false) {
            // Never authored, so the warning says what it was rather than
            // reading as lost data.
            dashboard_convert_warn($warnings, $index, $artefact, $name);
            continue;
        }

        // Nothing declares this widget, so there is nothing to say which of its
        // attributes are options and which would act on the page. The name on
        // its own does not settle it: onmouseover is shaped like an option
        // name. They are dropped rather than guessed at. The content column
        // still holds the html they came from.
        if (!$known) {
            dashboard_convert_warn(
                $warnings,
                $index,
                'unknown_widget_option_dropped',
                $name . '=' . dashboard_convert_snippet($value)
            );
            continue;
        }

        $option = widget_registry_option($type, $name);
        if ($option === false) {
            dashboard_convert_warn(
                $warnings,
                $index,
                'option_unknown_dropped',
                $name . '=' . dashboard_convert_snippet($value)
            );
            continue;
        }

        // An empty option is kept. Absent and empty are not the same to the
        // render scripts. feedvalue only falls back to its units when both
        // prepend and append are absent, so an author who set one of them and
        // left the other empty gets the word undefined printed beside the
        // reading once the empty one stops being written.
        if ($value !== '' && !dashboard_convert_option_valid($option, $value)) {
            $text = dashboard_convert_option_without_tags($option, $value);
            if ($text === false) {
                dashboard_convert_warn(
                    $warnings,
                    $index,
                    'option_value_dropped',
                    $name . '=' . dashboard_convert_snippet($value)
                );
                continue;
            }
            dashboard_convert_warn(
                $warnings,
                $index,
                'option_value_tags_stripped',
                $name . '=' . dashboard_convert_snippet($value)
            );
            $value = $text;
        }

        // Stored under the name the registry declares, so the renderer and the
        // editor agree on one spelling.
        $options[$option['name']] = (string) $value;
    }

    return $options;
}

// Free text that fails only because it holds a tag keeps its words. The tag
// cannot go back on the page, see the option values section of SCHEMA.md, but
// dropping the option takes the author's label with it, and feedvalue prints
// the word undefined in its place when append is set and prepend is not.
//
// Returns the text to store, or false when there is nothing worth keeping.
function dashboard_convert_option_without_tags($option, $value)
{
    if ($option['type'] !== 'value' && $option['type'] !== 'dropbox_other') {
        return false;
    }
    if (strpos($value, '<') === false && strpos($value, '>') === false) {
        return false;
    }

    // A br is a line break the author wrote, so it leaves a space behind. The
    // spacing either side of a label is kept, only runs of it are collapsed.
    $text = preg_replace('/<br\s*\/?>/i', ' ', $value);
    $text = strip_tags($text);
    $text = preg_replace('/\s+/u', ' ', $text);
    if ($text === null || trim($text) === '') {
        return false;
    }

    return dashboard_convert_option_valid($option, $text) ? $text : false;
}

function dashboard_convert_option_valid($option, $value)
{
    if (strlen($value) > 4096) {
        return false;
    }
    if (preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $value)) {
        return false;
    }

    switch ($option['type']) {
        case 'feedid':
        case 'feedid_realtime':
            // Either a feed id or a tag:name association, see render_feeds
            // in Views/js/render.js.
            return preg_match('/^\d+$/', $value)
                || preg_match('/^[^\x00-\x1f<>"\']{1,128}$/', $value);

        case 'colour_picker':
            // none is no colour at all, which the designer writes for a
            // background that shows the dashboard through.
            return preg_match('/^#?[0-9a-fA-F]{3}$/', $value)
                || preg_match('/^#?[0-9a-fA-F]{6}$/', $value)
                || strtolower($value) === 'none';

        case 'boolean':
            return $value === '0' || $value === '1';

        case 'dropbox':
            // A dynamic list is filled from the database per user, so there is
            // nothing here to check it against.
            if ($option['dynamic']) {
                return preg_match('/^[^\x00-\x1f<>"\']{1,128}$/', $value);
            }
            if ($option['values'] === null) {
                return true;
            }
            return in_array((string) $value, $option['values'], true);

        case 'dropbox_other':
        case 'value':
            // Free text an author types. A render script puts several of these
            // into the page with .html(), so a value holding a tag would be
            // parsed as one, see the option values section of SCHEMA.md.
            // Angle brackets are the only way to open a tag: an entity in an
            // attribute arrives at .html() already decoded and is written back
            // as text. Quotes are kept because the renderer escapes them and
            // the curl widget sends a json payload through one of these.
            return preg_match('/^[^<>]{1,512}$/u', $value) === 1;

        case 'number':
            // An integer within the declared range.
            if (!preg_match('/^-?\d+$/', $value)) {
                return false;
            }
            $number = (int) $value;
            if ($option['min'] !== null && $number < $option['min']) {
                return false;
            }
            if ($option['max'] !== null && $number > $option['max']) {
                return false;
            }
            return true;

        case 'url':
            // A link, checked with the same rules as an href in widget html.
            return dashboard_convert_url_allowed($value, 'href');

        case 'image_url':
            // Fetched by the browser, so a url on this site must name a static
            // image, see dashboard_convert_url_own_site.
            return dashboard_convert_url_allowed($value, 'src');

        case 'text':
            // The text of a widget is the content of its box, not an
            // attribute. It is checked against dashboard_convert_inline_elements
            // and kept in its own field.
            return false;

        case 'html':
            // The html of a widget is the content of its box, never an
            // attribute. The designer writes it with .html(), the document
            // keeps it in its own field, and no dashboard in the census
            // carries an html attribute. One that turns up was not authored
            // and is not put back on the page.
            return false;

        default:
            return true;
    }
}

// True when the style attribute of this element lost its quoting and spilled
// the rest of the style out as stray attributes. A property name keeps its
// colon, and the punctuation of the declaration arrives as its own attribute,
// so either is enough to recognise the element. See the decode note in
// SCHEMA.md.
function dashboard_convert_style_broke_out($node)
{
    foreach ($node->attributes as $attribute) {
        if (preg_match('/[:;()\/]|^[0-9]/', $attribute->nodeName)) {
            return true;
        }
    }
    return false;
}

// Attributes in stored content that were never options. Returns the warning
// code, or false if the attribute is a real option.
//
// $broken says the style of this element broke out, in which case its values
// are sitting alongside as valueless attributes. A word list would be needed
// to name them one by one, so they are recognised by where they are instead.
function dashboard_convert_artefact($name, $type, $known, $broken, $value)
{
    if (in_array($name, dashboard_convert_extension_attributes())) {
        return 'browser_extension_attribute';
    }

    if (preg_match('/[:;()\/]|^[0-9]/', $name)) {
        return 'broken_style_attribute';
    }

    if ($broken && $value === '' && ($known ? !widget_registry_option($type, $name) : true)) {
        return 'broken_style_attribute';
    }

    // The Other box of a dropbox_other option is a second select carrying the
    // same class as the real inputs, so the designer saves its id as well.
    if (
        $known && substr($name, -9) === '_dropdown'
        && widget_registry_option($type, substr($name, 0, -9))
    ) {
        return 'designer_artefact_attribute';
    }

    return false;
}

// ---------------------------------------------------------------------------
// Html of text and container widgets
// ---------------------------------------------------------------------------

function dashboard_convert_html(
    $node,
    $index,
    $registry,
    &$warnings,
    $allowed = null,
    $allow_style = true
) {
    $doc = $node->ownerDocument;

    // Worked on a copy so the caller's tree is not modified.
    $copy = $node->cloneNode(true);
    dashboard_convert_clean($copy, $index, $registry, $warnings, $allowed, $allow_style);

    $html = '';
    foreach ($copy->childNodes as $child) {
        $html .= $doc->saveHTML($child);
    }

    return trim(dashboard_convert_from_entities($html));
}

// The body of a text widget, limited to the inline elements with no style
// attributes.
function dashboard_convert_text($node, $index, $registry, &$warnings)
{
    return dashboard_convert_html(
        dashboard_convert_text_body($node),
        $index,
        $registry,
        $warnings,
        dashboard_convert_inline_elements(),
        false
    );
}

/**
 * Removes elements and attributes that are not allowed in a text widget.
 * Used by the renderer, the same as dashboard_convert_sanitise_html.
 *
 * @param string $text
 * @param array $warnings added to in place
 * @param int $index widget the text belongs to, for the warnings
 * @return string
 */
function dashboard_convert_sanitise_text($text, &$warnings, $index = null)
{
    if (trim($text) === '') {
        return '';
    }

    $root = dashboard_convert_parse($text);
    if ($root === null) {
        dashboard_convert_warn($warnings, $index, 'unparsable', '');
        return '';
    }

    return dashboard_convert_html(
        $root,
        $index,
        widget_registry(),
        $warnings,
        dashboard_convert_inline_elements(),
        false
    );
}

/**
 * Runs a piece of html through the allowlist and returns what survived.
 *
 * The renderer uses this on the way out as well, so content that reached the
 * column another way still cannot carry anything the allowlist rejects.
 *
 * @param string $html
 * @param array $warnings added to in place
 * @param int $index widget the html belongs to, for the warnings
 * @return string
 */
function dashboard_convert_sanitise_html($html, &$warnings, $index = null)
{
    if (trim($html) === '') {
        return '';
    }

    $root = dashboard_convert_parse($html);
    if ($root === null) {
        dashboard_convert_warn($warnings, $index, 'unparsable', '');
        return '';
    }

    return dashboard_convert_html($root, $index, widget_registry(), $warnings);
}

function dashboard_convert_clean(
    $node,
    $index,
    $registry,
    &$warnings,
    $allowed = null,
    $allow_style = true
) {
    if ($allowed === null) {
        $allowed = dashboard_convert_allowed_elements();
    }
    $strip = dashboard_convert_stripped_elements();

    // Collected first because the list is modified while walking it.
    $children = [];
    foreach ($node->childNodes as $child) {
        $children[] = $child;
    }

    foreach ($children as $child) {
        if ($child->nodeType === XML_COMMENT_NODE) {
            $node->removeChild($child);
            continue;
        }
        // libxml parses the content of a raw text element (xmp, noembed,
        // noframes, plaintext) into a CDATA section, and saveHTML writes a
        // CDATA section out verbatim, markup and all, wherever it ends up.
        // Unwrapping the element moved that live markup into the page. Turned
        // into an ordinary text node here, which serialises escaped.
        if ($child->nodeType === XML_CDATA_SECTION_NODE) {
            $node->replaceChild(
                $child->ownerDocument->createTextNode($child->textContent),
                $child
            );
            continue;
        }
        if ($child->nodeType !== XML_ELEMENT_NODE) {
            continue;
        }

        $tag = strtolower($child->nodeName);
        $class = $child->hasAttribute('class') ? trim($child->getAttribute('class')) : '';

        // A widget nested inside a text box. The flat document has no place
        // for it, so it goes, and the author is told through the warning.
        if ($class !== '' && isset($registry[$class])) {
            dashboard_convert_warn($warnings, $index, 'nested_widget_dropped', $class);
            $node->removeChild($child);
            continue;
        }

        if (in_array($tag, $strip)) {
            $detail = $tag;
            if ($tag === 'iframe') {
                $detail = dashboard_convert_snippet($child->getAttribute('src'));
            }
            dashboard_convert_warn(
                $warnings,
                $index,
                $tag === 'iframe' ? 'iframe_dropped' : 'tag_dropped',
                $detail
            );
            $node->removeChild($child);
            continue;
        }

        if (!in_array($tag, $allowed)) {
            // Not dangerous, just not part of the vocabulary, so the text
            // inside it is kept and the element itself is unwrapped.
            dashboard_convert_warn($warnings, $index, 'tag_unwrapped', $tag);
            dashboard_convert_clean($child, $index, $registry, $warnings, $allowed, $allow_style);
            while ($child->firstChild) {
                $node->insertBefore($child->firstChild, $child);
            }
            $node->removeChild($child);
            continue;
        }

        dashboard_convert_attributes($child, $tag, $index, $warnings, $allow_style);
        dashboard_convert_clean($child, $index, $registry, $warnings, $allowed, $allow_style);
    }
}

function dashboard_convert_attributes($element, $tag, $index, &$warnings, $allow_style = true)
{
    $per_element = dashboard_convert_allowed_attributes();
    $extensions = dashboard_convert_extension_attributes();

    $allowed = isset($per_element[$tag]) ? $per_element[$tag] : [];

    // The attribute nodes are collected rather than their names, because
    // removeAttribute cannot remove an attribute in the reserved xml and xmlns
    // namespaces. libxml holds those apart from the rest and the call returns
    // having done nothing, so xmlns, xmlns:x, xml:lang and xml:base were
    // reported as dropped and stayed on the element. removeAttributeNode
    // removes them along with everything else.
    $attributes = [];
    foreach ($element->attributes as $attribute) {
        $attributes[] = $attribute;
    }

    foreach ($attributes as $attribute) {
        $name = $attribute->nodeName;
        $lower = strtolower($name);
        $value = $attribute->nodeValue;

        if ($lower === 'style') {
            // Styling in a text widget is set with options, so a style
            // attribute is dropped.
            if (!$allow_style) {
                dashboard_convert_warn($warnings, $index, 'attribute_dropped', "$tag/style");
                $element->removeAttributeNode($attribute);
                continue;
            }
            $declarations = dashboard_convert_parse_style($value);
            $kept = dashboard_convert_styles($declarations, $index, $warnings, false);
            if (count($kept)) {
                $element->setAttribute('style', dashboard_convert_write_style($kept));
            } else {
                $element->removeAttributeNode($attribute);
            }
            continue;
        }

        if (in_array($lower, $allowed)) {
            if (
                ($lower === 'href' || $lower === 'src')
                && !dashboard_convert_url_allowed($value, $lower)
            ) {
                dashboard_convert_warn(
                    $warnings,
                    $index,
                    'url_dropped',
                    dashboard_convert_snippet($value)
                );
                $element->removeAttributeNode($attribute);
            }
            continue;
        }

        // on* handlers are covered here along with everything else that is not
        // on the list, and there is no rule above that could have kept one.
        if (!in_array($lower, $extensions)) {
            dashboard_convert_warn($warnings, $index, 'attribute_dropped', "$tag/$name");
        }
        $element->removeAttributeNode($attribute);
    }

    // A link opening in another tab hands that tab a handle to this one unless
    // it is told not to. Written here rather than left to the author, and
    // written on the way out as well, so a link that arrived some other way
    // carries it too. rel is on the allowlist so it survives the round trip.
    if ($tag === 'a' && $element->hasAttribute('target')) {
        $element->setAttribute('rel', 'noopener noreferrer');
    }

    // An image is fetched from wherever the author pointed it, and the referer
    // of that fetch is the dashboard url, which carries an apikey or a readkey
    // when the page was opened with one. Set here rather than left to the
    // author, and overwritten rather than trusted, so the author cannot ask
    // for a weaker policy than this. Emoncms sends a Referrer-Policy header as
    // well, see set_referrer_policy in core.php.
    // An image with no src left is inert and is not marked.
    if ($tag === 'img' && $element->hasAttribute('src')) {
        $element->setAttribute('referrerpolicy', 'no-referrer');
    }
}

// Control characters and whitespace are stripped before the scheme is tested,
// never after, so a scheme cannot be hidden inside one.
//
// What is left has to be http or https pointing at another site. A url
// pointing back at this emoncms is held to more than that, see
// dashboard_convert_url_own_site. mailto and every other scheme is dropped:
// mailto is harmless but no dashboard needs it, and a link is plain text once
// its href is gone.
function dashboard_convert_url_allowed($url, $attribute = 'href')
{
    $url = preg_replace('/[\x00-\x20\x7f]/', '', $url);
    if ($url === '') {
        return false;
    }

    $host = dashboard_convert_url_host($url);
    if ($host === false) {
        return false;
    }
    if ($host !== '' && $host !== dashboard_convert_request_host()) {
        return true;
    }

    // What is left points back at this emoncms, so the browser sends the
    // session of whoever is looking at the dashboard with it. See
    // dashboard_convert_url_own_site.
    return dashboard_convert_url_own_site($url, $attribute);
}

// The host a url names, '' when it names none and so points at this site, or
// false when the url is not one that may be written at all.
//
// A relative reference cannot carry a colon before its first path separator,
// so testing for that rejects every scheme without having to name them, and
// rejects the ones dressed up to look like something else. A null byte in
// java\0script: comes back out of the parser as a replacement character, which
// is not a control character and would pass a scheme shaped pattern.
function dashboard_convert_url_host($url)
{
    if (preg_match('#^https?://([^/?\#]*)#i', $url, $match)) {
        return strtolower(dashboard_convert_url_strip_port($match[1]));
    }
    // Scheme relative, //host/path, which is absolute to another site.
    if (substr($url, 0, 2) === '//') {
        $head = preg_split('#[/?\#]#', substr($url, 2), 2);
        return strtolower(dashboard_convert_url_strip_port($head[0]));
    }

    $head = preg_split('#[/?\#]#', $url, 2);
    if (strpos($head[0], ':') !== false) {
        return false;
    }

    return '';
}

function dashboard_convert_url_strip_port($host)
{
    // Userinfo is dropped with the port, neither says which site is named.
    $at = strrpos($host, '@');
    if ($at !== false) {
        $host = substr($host, $at + 1);
    }

    $colon = strrpos($host, ':');
    if ($colon !== false && strpos($host, ']') === false) {
        $host = substr($host, 0, $colon);
    }

    // A trailing dot names the same host to dns, so emoncms.org. is emoncms.org.
    // Without this it compares unequal and dodges the same-site rules, leaving
    // https://emoncms.org./feed/delete.json reachable. An IPv6 literal in
    // brackets carries no trailing dot, so it is untouched.
    return rtrim($host, '.');
}

// The host this request came in on, or '' from the command line. A migration
// run has no request to read, so it cannot tell an absolute url pointing at
// this site from one pointing anywhere else. The renderer runs the same check
// on the way out, inside a request, and drops it then.
function dashboard_convert_request_host()
{
    if (!isset($_SERVER['HTTP_HOST'])) {
        return '';
    }
    return strtolower(dashboard_convert_url_strip_port($_SERVER['HTTP_HOST']));
}

/**
 * Whether a url pointing at this emoncms may be written.
 *
 * An image is fetched as the page draws, with no click and nothing shown, so a
 * src here has to be a static image file. An emoncms api call reached this way
 * runs as the person looking at the dashboard, and feed/delete.json is a GET.
 * The file extension cannot tell the two apart: emoncms serves real files off
 * disk and routes everything else through index.php on the controller and
 * action, whatever the extension, so feed/delete.png routes just as
 * feed/delete.json does. A src is held instead to the one directory
 * server-hosted diagrams live in, see dashboard_convert_url_is_stored_image.
 *
 * A link navigates the page in the viewer's session, and emoncms routes on the
 * controller and action whatever the path ends in, so a link back at this site
 * can reach an api that acts on a GET, such as feed/delete or app/remove. The
 * file extension does not say which, and several such actions run whatever the
 * format. So an internal link is kept only when it is a page view, see
 * dashboard_convert_url_is_view_link. A link to another site is not this
 * module's to police and is left alone, see dashboard_convert_url_allowed.
 */
function dashboard_convert_url_own_site($url, $attribute)
{
    if ($attribute === 'src') {
        // A query string on a src is never needed to name a file and is the
        // shape every api call takes, so it goes with the rest.
        if (strpos($url, '?') !== false) {
            return false;
        }
        return dashboard_convert_url_is_stored_image($url);
    }

    return dashboard_convert_url_is_view_link($url);
}

// Controller routes a link back at this site may name: pages that show
// something and act on nothing. app with no action is the app view.
const DASHBOARD_CONVERT_VIEW_ROUTES = [
    'dashboard' => '/^view$/',
    'app'       => '/^(?:view)?$/',
    'graph'     => '/^\d+$/',
];

/**
 * Whether a link back at this site is a page view.
 *
 * A path segment naming an installed module is a controller route, and is
 * kept only as one of DASHBOARD_CONVERT_VIEW_ROUTES with nothing after it.
 * Every segment is checked rather than the first, as an install in a
 * subdirectory carries its base in front of the route. A path naming no
 * module is a public profile, username/dashboard for example, which index.php
 * serves with admin, write and read switched off, so it cannot act for the
 * viewer.
 *
 * Refused whatever the path: a q parameter, since the rewrite rule appends
 * the query string and a q in it replaces the route, and a php file, which
 * reaches index.php or a script directly. Also refused, a path that decodes
 * to & ? # = ; or a further %. The rewrite rule (Apache without the B flag,
 * nginx with $uri) writes the decoded path into the query string, so
 * EnergyPi%26q=feed/delete.json routes to feed/delete.json.
 */
function dashboard_convert_url_is_view_link($url)
{
    // A browser reads a backslash in an http url as a slash.
    $url = str_replace('\\', '/', $url);
    // Drop the scheme and host of an absolute link, leaving the path.
    $url = preg_replace('#^(?:https?:)?//[^/?\#]*#i', '', $url);

    $parts = preg_split('/[?#]/', $url, 2);
    $path = urldecode($parts[0]);
    if (preg_match('/[&?#=;%]/', $path)) {
        return false;
    }
    $query = '';
    if (preg_match('/\?([^#]*)/', $url, $match)) {
        $query = urldecode($match[1]);
    }
    if (preg_match('/(?:^|[&;])\s*q\s*(?:[\[=&;]|$)/i', $query)) {
        return false;
    }

    // A relative path resolves against the page, dashboard/view, so delete
    // on its own reaches dashboard/delete.
    $segments = [];
    if ($path !== '' && $path[0] !== '/') {
        $segments[] = 'dashboard';
    }
    foreach (explode('/', $path) as $segment) {
        if ($segment === '..') {
            array_pop($segments);
        } elseif ($segment !== '' && $segment !== '.') {
            $segments[] = strtolower($segment);
        }
    }

    $modules = dashboard_convert_installed_modules();
    foreach ($segments as $i => $segment) {
        if (preg_match('/\.(?:php\d*|phtml|phar|phps)$/', $segment)) {
            return false;
        }
        if (!in_array($segment, $modules, true)) {
            continue;
        }
        if (!isset(DASHBOARD_CONVERT_VIEW_ROUTES[$segment])) {
            return false;
        }
        $action = isset($segments[$i + 1]) ? $segments[$i + 1] : '';
        if (!preg_match(DASHBOARD_CONVERT_VIEW_ROUTES[$segment], $action) || count($segments) > $i + 2) {
            return false;
        }
        return true;
    }
    return true;
}

// Names of the directories in Modules, lower case, symlinked modules included.
function dashboard_convert_installed_modules()
{
    static $modules = null;
    if ($modules === null) {
        $modules = [];
        foreach (glob(dirname(__DIR__) . '/*', GLOB_ONLYDIR) as $dir) {
            $modules[] = strtolower(basename($dir));
        }
    }
    return $modules;
}

// A file directly inside the dashboard's own images directory, the one place
// server-hosted diagrams live. The path is app-root-relative, so it matches
// whatever base a subdirectory install carries in front of it. A single
// filename only, no subdirectory and no traversal, so nothing outside the
// directory can be reached.
function dashboard_convert_url_is_stored_image($url)
{
    $path = preg_split('#[?\#]#', $url, 2);
    // Decode first, so ..%2f cannot smuggle a traversal past the segment check.
    $path = urldecode($path[0]);

    foreach (explode('/', $path) as $segment) {
        if ($segment === '..') {
            return false;
        }
    }

    if (!preg_match('#(?:^|/)Modules/dashboard/Views/images/([^/]+)$#', $path, $match)) {
        return false;
    }
    return dashboard_convert_url_is_image($match[1]);
}

function dashboard_convert_url_is_image($file)
{
    $dot = strrpos($file, '.');
    if ($dot === false) {
        return false;
    }
    $extension = strtolower(substr($file, $dot + 1));
    return in_array(
        $extension,
        ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'ico', 'avif'],
        true
    );
}

// ---------------------------------------------------------------------------
// Styles
// ---------------------------------------------------------------------------

function dashboard_convert_parse_style($style)
{
    $declarations = [];
    foreach (explode(';', $style) as $declaration) {
        if (strpos($declaration, ':') === false) {
            continue;
        }
        list($property, $value) = explode(':', $declaration, 2);
        $property = strtolower(trim($property));
        if ($property === '') {
            continue;
        }
        $declarations[$property] = trim($value);
    }
    return $declarations;
}

/**
 * Filters a set of style declarations down to the allowed properties.
 *
 * @param array $declarations property => value
 * @param bool $box true for a widget box, where the geometry properties are
 *                  dropped without a warning because the renderer writes them
 * @return array the declarations kept
 */
function dashboard_convert_styles($declarations, $index, &$warnings, $box)
{
    $allowed = dashboard_convert_allowed_styles();
    $box_styles = dashboard_convert_box_styles();

    $kept = [];
    foreach ($declarations as $property => $value) {
        // The html path hands this parsed declarations, which are always
        // strings. A decoded document hands it whatever the column held, so
        // the shape is checked before anything is read from it.
        if (!is_string($property) || !is_string($value)) {
            dashboard_convert_warn(
                $warnings,
                $index,
                'style_declaration_unreadable',
                is_string($property) ? $property : ''
            );
            continue;
        }
        $property = strtolower(trim($property));

        if ($box && in_array($property, $box_styles)) {
            continue;
        }

        if (!in_array($property, $allowed)) {
            if (!dashboard_convert_style_property_silent($property)) {
                dashboard_convert_warn($warnings, $index, 'style_property_dropped', $property);
            }
            continue;
        }

        if (!dashboard_convert_style_value_allowed($value)) {
            dashboard_convert_warn(
                $warnings,
                $index,
                'style_value_dropped',
                $property . ': ' . dashboard_convert_snippet($value)
            );
            continue;
        }

        if ($property === 'opacity') {
            $opacity = dashboard_convert_style_opacity($value);
            if ($opacity === false) {
                dashboard_convert_warn(
                    $warnings,
                    $index,
                    'style_value_dropped',
                    $property . ': ' . dashboard_convert_snippet($value)
                );
                continue;
            }
            if ($opacity !== $value) {
                dashboard_convert_warn($warnings, $index, 'opacity_raised', $value);
            }
            $value = $opacity;
        }

        // transform is allowed to rotate and nothing else. Rotation turns a box
        // where it stands, so it can cover no more of the page than the
        // geometry already lets it. translate, scale and matrix move or grow
        // it, which is how a widget ends up over one the visitor means to
        // press, the overlay gate_action_widgets closes.
        if ($property === 'transform' && !dashboard_convert_style_rotate_only($value)) {
            dashboard_convert_warn(
                $warnings,
                $index,
                'style_value_dropped',
                $property . ': ' . dashboard_convert_snippet($value)
            );
            continue;
        }

        // A negative margin pulls content out of the widget box and up over the
        // emoncms menu bar, the same overlay the top clamp in the renderer
        // closes. Only ever reached for the html of a widget, the box path
        // drops margin before this, see dashboard_convert_box_styles. A
        // subtraction in calc() goes with it, the result can be negative too.
        if (substr($property, 0, 6) === 'margin' && preg_match('/-\s*[\d.]/', $value)) {
            dashboard_convert_warn(
                $warnings,
                $index,
                'style_value_dropped',
                $property . ': ' . dashboard_convert_snippet($value)
            );
            continue;
        }

        $kept[$property] = $value;
    }
    return $kept;
}

// Style properties written by a browser extension or by the browser itself,
// not by the author. Dropped like any other property off the list, but without
// a warning, as there is nothing for the author to act on. The counts are in
// the census: darkreader writes the custom properties, and the font-variant
// family comes from readability and translation extensions.
function dashboard_convert_style_property_silent($property)
{
    if (substr($property, 0, 2) === '--') {
        return true;
    }
    if (substr($property, 0, 12) === 'font-variant') {
        return true;
    }
    // A prefixed transform is written beside the plain one, which is kept, so
    // there is nothing for the author to act on. No browser still needs them.
    if (preg_match('/^-(webkit|moz|ms|o)-transform$/', $property)) {
        return true;
    }

    return in_array($property, ['user-select', 'font-stretch', 'font-width',
        'font-size-adjust', 'font-kerning', 'font-feature-settings',
        'font-optical-sizing', 'font-variation-settings', 'word-break',
        'pointer-events'
    ]);
}

// Opacity is floored at 0.2. A widget at zero opacity is invisible and still
// takes clicks, which on a public dashboard puts an unseen curl or button
// widget over something the visitor means to press. Below the floor the value
// is raised and the author is told. display: none and visibility: hidden do
// not have this problem, they take the box out of hit testing, so they are
// left alone.
function dashboard_convert_style_opacity($value)
{
    $value = trim($value);

    if (preg_match('/^(\d*\.?\d+)$/', $value, $match)) {
        return ((float) $match[1] < 0.2) ? '0.2' : $value;
    }
    if (preg_match('/^(\d*\.?\d+)\s*%$/', $value, $match)) {
        return ((float) $match[1] < 20) ? '20%' : $value;
    }

    // Anything else cannot be read here, so the floor cannot be applied to it.
    return false;
}

// The functions a style value may call. Named the other way round because the
// ways of writing a fetch are not a list to keep up with: url() and image-set()
// and its vendor spellings fetch, expression() runs, element() and paint() draw
// from elsewhere in the page, and the next one is not written yet. These few
// only compute a value.
// One rotate() and nothing else. The angle takes any css unit, or none, which
// css reads as degrees. A second function in the value, or anything either
// side of it, is not a rotation and is not kept.
function dashboard_convert_style_rotate_only($value)
{
    return preg_match(
        '/^\s*rotate\(\s*[-+]?(\d+(\.\d+)?|\.\d+)(deg|grad|rad|turn)?\s*\)\s*$/i',
        $value
    ) === 1;
}

function dashboard_convert_allowed_style_functions()
{
    return ['rgb', 'rgba', 'hsl', 'hsla', 'calc', 'rotate'];
}

function dashboard_convert_style_value_allowed($value)
{
    if ($value === '' || strlen($value) > 256) {
        return false;
    }
    if (preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $value)) {
        return false;
    }
    // A backslash writes a css escape, which could spell a function name
    // another way, and the rest cannot appear in a value at all.
    if (preg_match('/[\\\\<>{}]/', $value)) {
        return false;
    }
    // A semicolon ends the declaration and starts another, which is how a
    // value carries position or z-index past the property allowlist. The html
    // path splits on it before this is reached, a decoded document does not,
    // and no value in the census holds either character.
    if (preg_match('/[;:]/', $value)) {
        return false;
    }
    // position on its own lifts a box out of the page.
    if (preg_match('/^\s*position\s*$/i', $value)) {
        return false;
    }

    if (preg_match_all('/([A-Za-z_-][A-Za-z0-9_-]*)\s*\(/', $value, $matches)) {
        $allowed = dashboard_convert_allowed_style_functions();
        foreach ($matches[1] as $function) {
            if (!in_array(strtolower($function), $allowed)) {
                return false;
            }
        }
    }

    return true;
}

function dashboard_convert_write_style($declarations)
{
    $parts = [];
    foreach ($declarations as $property => $value) {
        $parts[] = "$property: $value";
    }
    return implode('; ', $parts);
}

// ---------------------------------------------------------------------------
// Warnings
// ---------------------------------------------------------------------------

function dashboard_convert_warn(&$warnings, $index, $code, $detail)
{
    $warnings[] = [
        'widget' => $index,
        'code' => $code,
        'detail' => (string) $detail
    ];
}

/**
 * Encodes a converted document for storage.
 *
 * A widget with no options holds an empty PHP array, which json_encode writes
 * as [] rather than {}. The column has to hold one shape, so the empty case is
 * turned into an object on the way out.
 *
 * @param array $document as returned by dashboard_convert
 * @param int $flags extra json_encode flags, such as JSON_PRETTY_PRINT
 * @return string|false
 */
function dashboard_convert_encode($document, $flags = 0)
{
    if (isset($document['widgets'])) {
        foreach ($document['widgets'] as $i => $widget) {
            if (isset($widget['options']) && !count($widget['options'])) {
                $document['widgets'][$i]['options'] = new stdClass();
            }
        }
    }
    return json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        | JSON_INVALID_UTF8_SUBSTITUTE | $flags);
}

function dashboard_convert_snippet($text)
{
    $text = preg_replace('/\s+/', ' ', trim((string) $text));
    if (strlen($text) <= 80) {
        return $text;
    }
    return substr($text, 0, 77) . '...';
}
