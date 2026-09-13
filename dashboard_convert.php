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
 tools/SCHEMA.md. Reading only, it returns a document and does not write
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

define('DASHBOARD_CONVERTER_VERSION', 1);

// Elements allowed in the html of a text or container widget.
$dashboard_convert_elements = array(
    'a', 'b', 'strong', 'i', 'em', 'u', 'sub', 'sup', 'br', 'p', 'div', 'span',
    'center', 'font', 'small', 'h1', 'h2', 'h3', 'h4', 'h5',
    'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'img'
);

// Elements removed with everything inside them. Anything else that is not
// allowed is unwrapped instead, so the text inside it survives.
$dashboard_convert_strip = array(
    'script', 'style', 'meta', 'title', 'link', 'object', 'embed', 'iframe',
    'svg', 'form', 'input', 'button', 'select', 'textarea', 'canvas', 'applet',
    'base', 'frame', 'frameset', 'noscript', 'template'
);

// Attributes allowed per element, on top of style which any of them may carry.
$dashboard_convert_attributes = array(
    'a' => array('href', 'target', 'title'),
    'img' => array('src', 'alt', 'width', 'height'),
    'font' => array('color', 'face', 'size'),
    'table' => array('border', 'cellpadding', 'cellspacing'),
    'td' => array('colspan', 'rowspan', 'align'),
    'th' => array('colspan', 'rowspan', 'align')
);

// Style properties allowed, both on a widget box and inside its html.
$dashboard_convert_styles = array(
    'color', 'background-color', 'font-size', 'font-family', 'font-weight',
    'font-style', 'text-align', 'text-decoration', 'line-height',
    'vertical-align', 'padding', 'margin', 'border', 'width', 'height'
);

// Style properties of a widget box that the designer writes and the renderer
// puts back, so they are dropped without a warning.
$dashboard_convert_box_styles = array(
    'position', 'top', 'left', 'width', 'height', 'margin'
);

// Attributes added by browser extensions to the page the editor saved.
$dashboard_convert_extension_attrs = array(
    'bis_skin_checked', '_msttexthash', '_msthash', 'wfd-id',
    'data-darkreader-inline-color', 'data-dashlane-frameid',
    'data-ruffle-polyfilled', 'data-ol-has-click-handler',
    '__gchrome_childframeremotetoken'
);

/**
 * Converts one dashboard's stored content.
 *
 * @param string $html the content column
 * @return array document and warnings
 */
function dashboard_convert($html)
{
    $warnings = array();

    if (trim($html) === '') {
        return dashboard_convert_result(array(), $warnings);
    }

    $root = dashboard_convert_parse($html);
    if ($root === null) {
        dashboard_convert_warn($warnings, null, 'unparsable', '');
        return array('document' => null, 'warnings' => $warnings);
    }

    $registry = widget_registry();
    $widgets = array();

    foreach ($root->childNodes as $node) {
        if ($node->nodeType === XML_TEXT_NODE) {
            if (trim($node->textContent) !== '') {
                dashboard_convert_warn($warnings, null, 'text_outside_widget',
                    dashboard_convert_snippet($node->textContent));
            }
            continue;
        }
        if ($node->nodeType !== XML_ELEMENT_NODE) continue;

        $widget = dashboard_convert_widget($node, count($widgets), $registry, $warnings);
        if ($widget !== null) $widgets[] = $widget;
    }

    return dashboard_convert_result($widgets, $warnings);
}

function dashboard_convert_result($widgets, $warnings)
{
    $document = array(
        'version' => 1,
        'widgets' => $widgets,
        'meta' => array(
            'converted_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'converter' => DASHBOARD_CONVERTER_VERSION,
            'warnings' => $warnings
        )
    );
    return array('document' => $document, 'warnings' => $warnings);
}

// ---------------------------------------------------------------------------
// Parsing
// ---------------------------------------------------------------------------

function dashboard_convert_parse($html)
{
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    libxml_clear_errors();
    $ok = $doc->loadHTML('<div>' . dashboard_convert_to_entities($html) . '</div>',
                         LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors(false);
    if (!$ok || !$doc->documentElement) return null;
    return $doc->documentElement;
}

// libxml reads bytes as latin1 unless it is told otherwise, so non ASCII text
// is turned into numeric entities before parsing and back again after.
function dashboard_convert_to_entities($html)
{
    return mb_encode_numericentity($html, array(0x80, 0x10FFFF, 0, 0x1FFFFF), 'UTF-8');
}

function dashboard_convert_from_entities($html)
{
    return mb_decode_numericentity($html, array(0x80, 0x10FFFF, 0, 0x1FFFFF), 'UTF-8');
}

// ---------------------------------------------------------------------------
// Widgets
// ---------------------------------------------------------------------------

function dashboard_convert_widget($node, $index, $registry, &$warnings)
{
    $class = $node->hasAttribute('class') ? trim($node->getAttribute('class')) : '';

    if ($class === '') {
        dashboard_convert_warn($warnings, $index, 'widget_without_type',
            strtolower($node->nodeName));
        return null;
    }

    // A widget type is one token because it is the class attribute of the box.
    $type = $class;
    if (preg_match('/\s/', $class)) {
        $type = preg_replace('/\s+/', ' ', $class);
        dashboard_convert_warn($warnings, $index, 'widget_type_not_one_token', $type);
    }

    $known = isset($registry[$type]);
    $widget = array('type' => $type);

    $style = $node->hasAttribute('style') ? $node->getAttribute('style') : '';
    $declarations = dashboard_convert_parse_style($style);

    $widget += dashboard_convert_geometry($declarations, $index, $warnings);

    if (isset($declarations['position'])
        && strtolower(trim($declarations['position'])) === 'fixed') {
        dashboard_convert_warn($warnings, $index, 'position_fixed_dropped', $type);
    }

    $widget['options'] = dashboard_convert_options($node, $type, $known, $index, $warnings);

    if (!$known) {
        // Kept with its options so nothing is lost, see the unknown widget
        // types section of SCHEMA.md.
        $widget['unknown'] = true;
        dashboard_convert_warn($warnings, $index, 'widget_type_unknown', $type);
    }

    if (dashboard_convert_holds_html($type, $known, $registry)) {
        $html = dashboard_convert_html($node, $index, $registry, $warnings);
        if ($html !== '') $widget['html'] = $html;

        // Only these widgets can carry author written box styling. On the
        // rest the render script writes the box style at draw time, so what
        // is stored is generated and is dropped without a warning.
        $box = dashboard_convert_styles($declarations, $index, $warnings, true);
        if (count($box)) $widget['style'] = $box;
    } else {
        dashboard_convert_check_discarded($node, $type, $index, $registry, $warnings);
    }

    return $widget;
}

// A widget may hold html when the registry says it has an html option, which
// covers paragraph, heading and heading-center. The containers declare no
// options at all and hold hand built tables, so they are included by name.
function dashboard_convert_holds_html($type, $known, $registry)
{
    if (substr($type, 0, 10) === 'Container-') return true;
    if (!$known) return false;

    foreach ($registry[$type]['options'] as $option) {
        if ($option['type'] === 'html') return true;
    }
    return false;
}

// The children of a data widget are all generated: the canvas the render
// script draws on, the iframe a vis widget builds, the tooltip divs that dial,
// bar and thermometer inject, and the last reading left in the box. None of it
// is worth a warning. A widget nested inside one is worth a warning, because
// it is something the author put there and it is being dropped.
function dashboard_convert_check_discarded($node, $type, $index, $registry, &$warnings)
{
    $stack = array($node);
    while (count($stack)) {
        $current = array_pop($stack);
        foreach ($current->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) continue;

            $class = $child->hasAttribute('class') ? trim($child->getAttribute('class')) : '';
            if ($class !== '' && isset($registry[$class])) {
                dashboard_convert_warn($warnings, $index, 'nested_widget_dropped',
                    "$class inside $type");
                continue;
            }
            if (strtolower($child->nodeName) === 'iframe' && !dashboard_convert_draws_iframe($type, $registry)) {
                dashboard_convert_warn($warnings, $index, 'iframe_dropped',
                    dashboard_convert_snippet($child->getAttribute('src')));
            }
            $stack[] = $child;
        }
    }
}

function dashboard_convert_draws_iframe($type, $registry)
{
    if (!isset($registry[$type])) return false;
    return $registry[$type]['module'] === 'vis' || $registry[$type]['module'] === 'graph';
}

// ---------------------------------------------------------------------------
// Geometry
// ---------------------------------------------------------------------------

// The designer writes top, left, width and height onto the box, and records
// whether width and height were set in per cent, see scan and draw in
// Views/js/designer.js.
function dashboard_convert_geometry($declarations, $index, &$warnings)
{
    $geometry = array(
        'x' => dashboard_convert_length($declarations, 'left'),
        'y' => dashboard_convert_length($declarations, 'top'),
        'w' => dashboard_convert_length($declarations, 'width'),
        'h' => dashboard_convert_length($declarations, 'height'),
        'wunit' => dashboard_convert_unit($declarations, 'width'),
        'hunit' => dashboard_convert_unit($declarations, 'height')
    );

    foreach (array('left', 'top', 'width', 'height') as $property) {
        if (!isset($declarations[$property])) {
            dashboard_convert_warn($warnings, $index, 'geometry_missing', $property);
        }
    }

    if ($geometry['w'] < 0) $geometry['w'] = 0;
    if ($geometry['h'] < 0) $geometry['h'] = 0;

    return $geometry;
}

function dashboard_convert_length($declarations, $property)
{
    if (!isset($declarations[$property])) return 0;
    if (!preg_match('/-?\d+(\.\d+)?/', $declarations[$property], $match)) return 0;
    return (int) round((float) $match[0]);
}

function dashboard_convert_unit($declarations, $property)
{
    if (!isset($declarations[$property])) return 'px';
    return strpos($declarations[$property], '%') === false ? 'px' : 'pc';
}

// ---------------------------------------------------------------------------
// Options
// ---------------------------------------------------------------------------

function dashboard_convert_options($node, $type, $known, $index, &$warnings)
{
    $options = array();
    $broken = dashboard_convert_style_broke_out($node);

    foreach ($node->attributes as $attribute) {
        $name = $attribute->nodeName;
        $value = $attribute->nodeValue;

        if ($name === 'id' || $name === 'class' || $name === 'style') continue;

        $artefact = dashboard_convert_artefact($name, $type, $known, $broken, $value);
        if ($artefact !== false) {
            // Never authored, so the warning says what it was rather than
            // reading as lost data.
            dashboard_convert_warn($warnings, $index, $artefact, $name);
            continue;
        }

        // An unknown widget cannot be checked against anything, so its options
        // are carried through as they are.
        if (!$known) {
            $options[$name] = (string) $value;
            continue;
        }

        $option = widget_registry_option($type, $name);
        if ($option === false) {
            dashboard_convert_warn($warnings, $index, 'option_unknown_dropped',
                $name . '=' . dashboard_convert_snippet($value));
            continue;
        }

        // An empty option is left out. The render scripts already fall back to
        // their default when an attribute is absent.
        if ($value === '') continue;

        if (!dashboard_convert_option_valid($option, $value)) {
            dashboard_convert_warn($warnings, $index, 'option_value_dropped',
                $name . '=' . dashboard_convert_snippet($value));
            continue;
        }

        // Stored under the name the registry declares, so the renderer and the
        // editor agree on one spelling.
        $options[$option['name']] = (string) $value;
    }

    return $options;
}

function dashboard_convert_option_valid($option, $value)
{
    if (strlen($value) > 4096) return false;
    if (preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $value)) return false;

    switch ($option['type']) {
        case 'feedid':
        case 'feedid_realtime':
            // Either a feed id or a tag:name association, see assocfeed in
            // Views/js/render.js.
            return preg_match('/^\d+$/', $value)
                || preg_match('/^[^\x00-\x1f<>"\']{1,128}$/', $value);

        case 'colour_picker':
            return preg_match('/^#?[0-9a-fA-F]{3}$/', $value)
                || preg_match('/^#?[0-9a-fA-F]{6}$/', $value);

        case 'boolean':
            return $value === '0' || $value === '1';

        case 'dropbox':
            // A dynamic list is filled from the database per user, so there is
            // nothing here to check it against.
            if ($option['dynamic']) return preg_match('/^[^\x00-\x1f<>"\']{1,128}$/', $value);
            if ($option['values'] === null) return true;
            return in_array((string) $value, $option['values'], true);

        case 'dropbox_other':
        case 'value':
        case 'html':
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
        if (preg_match('/[:;()\/]|^[0-9]/', $attribute->nodeName)) return true;
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
    global $dashboard_convert_extension_attrs;

    if (in_array($name, $dashboard_convert_extension_attrs)) {
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
    if ($known && substr($name, -9) === '_dropdown'
        && widget_registry_option($type, substr($name, 0, -9))) {
        return 'designer_artefact_attribute';
    }

    return false;
}

// ---------------------------------------------------------------------------
// Html of text and container widgets
// ---------------------------------------------------------------------------

function dashboard_convert_html($node, $index, $registry, &$warnings)
{
    $doc = $node->ownerDocument;

    // Worked on a copy so the caller's tree is not modified.
    $copy = $node->cloneNode(true);
    dashboard_convert_clean($copy, $index, $registry, $warnings);

    $html = '';
    foreach ($copy->childNodes as $child) {
        $html .= $doc->saveHTML($child);
    }

    return trim(dashboard_convert_from_entities($html));
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
    if (trim($html) === '') return '';

    $root = dashboard_convert_parse($html);
    if ($root === null) {
        dashboard_convert_warn($warnings, $index, 'unparsable', '');
        return '';
    }

    return dashboard_convert_html($root, $index, widget_registry(), $warnings);
}

function dashboard_convert_clean($node, $index, $registry, &$warnings)
{
    global $dashboard_convert_elements, $dashboard_convert_strip;

    // Collected first because the list is modified while walking it.
    $children = array();
    foreach ($node->childNodes as $child) $children[] = $child;

    foreach ($children as $child) {
        if ($child->nodeType === XML_COMMENT_NODE) {
            $node->removeChild($child);
            continue;
        }
        if ($child->nodeType !== XML_ELEMENT_NODE) continue;

        $tag = strtolower($child->nodeName);
        $class = $child->hasAttribute('class') ? trim($child->getAttribute('class')) : '';

        // A widget nested inside a text box. The flat document has no place
        // for it, so it goes, and the author is told through the warning.
        if ($class !== '' && isset($registry[$class])) {
            dashboard_convert_warn($warnings, $index, 'nested_widget_dropped', $class);
            $node->removeChild($child);
            continue;
        }

        if (in_array($tag, $dashboard_convert_strip)) {
            $detail = $tag;
            if ($tag === 'iframe') $detail = dashboard_convert_snippet($child->getAttribute('src'));
            dashboard_convert_warn($warnings, $index,
                $tag === 'iframe' ? 'iframe_dropped' : 'tag_dropped', $detail);
            $node->removeChild($child);
            continue;
        }

        if (!in_array($tag, $dashboard_convert_elements)) {
            // Not dangerous, just not part of the vocabulary, so the text
            // inside it is kept and the element itself is unwrapped.
            dashboard_convert_warn($warnings, $index, 'tag_unwrapped', $tag);
            dashboard_convert_clean($child, $index, $registry, $warnings);
            while ($child->firstChild) {
                $node->insertBefore($child->firstChild, $child);
            }
            $node->removeChild($child);
            continue;
        }

        dashboard_convert_attributes($child, $tag, $index, $warnings);
        dashboard_convert_clean($child, $index, $registry, $warnings);
    }
}

function dashboard_convert_attributes($element, $tag, $index, &$warnings)
{
    global $dashboard_convert_attributes, $dashboard_convert_extension_attrs;

    $allowed = isset($dashboard_convert_attributes[$tag])
        ? $dashboard_convert_attributes[$tag] : array();

    $attributes = array();
    foreach ($element->attributes as $attribute) $attributes[] = $attribute->nodeName;

    foreach ($attributes as $name) {
        $lower = strtolower($name);
        $value = $element->getAttribute($name);

        if ($lower === 'style') {
            $declarations = dashboard_convert_parse_style($value);
            $kept = dashboard_convert_styles($declarations, $index, $warnings, false);
            if (count($kept)) {
                $element->setAttribute('style', dashboard_convert_write_style($kept));
            } else {
                $element->removeAttribute($name);
            }
            continue;
        }

        if (in_array($lower, $allowed)) {
            if (($lower === 'href' || $lower === 'src')
                && !dashboard_convert_url_allowed($value)) {
                dashboard_convert_warn($warnings, $index, 'url_dropped',
                    dashboard_convert_snippet($value));
                $element->removeAttribute($name);
            }
            continue;
        }

        // on* handlers are covered here along with everything else that is not
        // on the list, and there is no rule above that could have kept one.
        if (!in_array($lower, $dashboard_convert_extension_attrs)) {
            dashboard_convert_warn($warnings, $index, 'attribute_dropped', "$tag/$name");
        }
        $element->removeAttribute($name);
    }
}

// Control characters are stripped before the scheme is tested, never after, so
// a scheme cannot be hidden inside one.
function dashboard_convert_url_allowed($url)
{
    $url = preg_replace('/[\x00-\x20\x7f]/', '', $url);
    if ($url === '') return false;
    if (preg_match('#^(https?://|mailto:)#i', $url)) return true;
    // A relative path, which must not start a scheme of its own.
    return !preg_match('#^[a-z0-9.+-]*:#i', $url);
}

// ---------------------------------------------------------------------------
// Styles
// ---------------------------------------------------------------------------

function dashboard_convert_parse_style($style)
{
    $declarations = array();
    foreach (explode(';', $style) as $declaration) {
        if (strpos($declaration, ':') === false) continue;
        list($property, $value) = explode(':', $declaration, 2);
        $property = strtolower(trim($property));
        if ($property === '') continue;
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
    global $dashboard_convert_styles, $dashboard_convert_box_styles;

    $kept = array();
    foreach ($declarations as $property => $value) {
        if ($box && in_array($property, $dashboard_convert_box_styles)) continue;

        if (!in_array($property, $dashboard_convert_styles)) {
            // Extension styling and vendor variables are not author written.
            if (substr($property, 0, 2) !== '--' && $property !== 'user-select') {
                dashboard_convert_warn($warnings, $index, 'style_property_dropped', $property);
            }
            continue;
        }

        if (!dashboard_convert_style_value_allowed($value)) {
            dashboard_convert_warn($warnings, $index, 'style_value_dropped',
                $property . ': ' . dashboard_convert_snippet($value));
            continue;
        }

        $kept[$property] = $value;
    }
    return $kept;
}

function dashboard_convert_style_value_allowed($value)
{
    if ($value === '' || strlen($value) > 256) return false;
    if (preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $value)) return false;
    // url() can fetch, expression() can run, and position can lift a box out
    // of the page.
    if (preg_match('/url\s*\(|expression\s*\(|[\\\\<>{}]/i', $value)) return false;
    if (preg_match('/^\s*position\s*$/i', $value)) return false;
    return true;
}

function dashboard_convert_write_style($declarations)
{
    $parts = array();
    foreach ($declarations as $property => $value) $parts[] = "$property: $value";
    return implode('; ', $parts);
}

// ---------------------------------------------------------------------------
// Warnings
// ---------------------------------------------------------------------------

function dashboard_convert_warn(&$warnings, $index, $code, $detail)
{
    $warnings[] = array(
        'widget' => $index,
        'code' => $code,
        'detail' => (string) $detail
    );
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
    if (strlen($text) <= 80) return $text;
    return substr($text, 0, 77) . '...';
}
