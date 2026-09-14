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
    return array(
        'a', 'b', 'strong', 'i', 'em', 'u', 'sub', 'sup', 'br', 'p', 'div', 'span',
        'center', 'font', 'small', 'h1', 'h2', 'h3', 'h4', 'h5',
        'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'img'
    );
}

// Elements removed with everything inside them. Anything else that is not
// allowed is unwrapped instead, so the text inside it survives.
function dashboard_convert_stripped_elements()
{
    return array(
        'script', 'style', 'meta', 'title', 'link', 'object', 'embed', 'iframe',
        'svg', 'form', 'input', 'button', 'select', 'textarea', 'canvas', 'applet',
        'base', 'frame', 'frameset', 'noscript', 'template',
        'xmp', 'noembed', 'noframes', 'plaintext'
    );
}

// Attributes allowed per element, on top of style which any of them may carry.
function dashboard_convert_allowed_attributes()
{
    return array(
        'a' => array('href', 'target', 'title', 'rel'),
        'img' => array('src', 'alt', 'width', 'height', 'referrerpolicy'),
        'font' => array('color', 'face', 'size'),
        'table' => array('border', 'cellpadding', 'cellspacing'),
        'td' => array('colspan', 'rowspan', 'align'),
        'th' => array('colspan', 'rowspan', 'align')
    );
}

// Style properties allowed, both on a widget box and inside its html. Taken
// from what stored dashboards use, see the style property counts in the census.
// None of them can fetch or run anything: a value carrying url(), expression()
// or a position declaration is dropped whatever the property is, see
// dashboard_convert_style_value_allowed.
function dashboard_convert_allowed_styles()
{
    return array(
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
    );
}

// Style properties of a widget box that the designer writes and the renderer
// puts back, so they are dropped without a warning. The margin longhands are
// here with the shorthand: the renderer writes margin: 0 on every box, and a
// margin-top written after it would win and move the box off the geometry the
// document gives it. Inside the html of a widget they are kept.
function dashboard_convert_box_styles()
{
    return array('position', 'top', 'left', 'width', 'height',
        'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left');
}

// Attributes added by browser extensions to the page the editor saved.
function dashboard_convert_extension_attributes()
{
    return array(
        'bis_skin_checked', '_msttexthash', '_msthash', 'wfd-id',
        'data-darkreader-inline-color', 'data-dashlane-frameid',
        'data-ruffle-polyfilled', 'data-ol-has-click-handler',
        '__gchrome_childframeremotetoken'
    );
}

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
    // More than one is hand written markup rather than a widget the designer
    // wrote, and the renderer will not draw a type carrying a space, so it is
    // dropped here rather than written into a document that cannot be drawn.
    if (preg_match('/\s/', $class)) {
        dashboard_convert_warn($warnings, $index, 'widget_type_not_one_token',
            preg_replace('/\s+/', ' ', $class));
        return null;
    }
    $type = $class;

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
        // Kept as a placeholder, with its geometry and its box styling but
        // without its attributes, see the unknown widget types section of
        // SCHEMA.md.
        $widget['unknown'] = true;
        dashboard_convert_warn($warnings, $index, 'widget_type_unknown', $type);
    }

    $holds_html = dashboard_convert_holds_html($type, $known, $registry);

    if ($holds_html) {
        $html = dashboard_convert_html($node, $index, $registry, $warnings);
        if ($html !== '') $widget['html'] = $html;
    } else {
        dashboard_convert_check_discarded($node, $type, $index, $registry, $warnings);
    }

    // Author written box styling. A deployed data widget has its box style
    // written by the render script at draw time, so what is stored is
    // generated and is dropped without a warning. Text boxes, containers and
    // placeholders keep theirs.
    if ($holds_html || !$known) {
        $box = dashboard_convert_styles($declarations, $index, $warnings, true);
        if (count($box)) $widget['style'] = $box;
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

        // Nothing declares this widget, so there is nothing to say which of its
        // attributes are options and which would act on the page. The name on
        // its own does not settle it: onmouseover is shaped like an option
        // name. They are dropped rather than guessed at. The content column
        // still holds the html they came from.
        if (!$known) {
            dashboard_convert_warn($warnings, $index, 'unknown_widget_option_dropped',
                $name . '=' . dashboard_convert_snippet($value));
            continue;
        }

        $option = widget_registry_option($type, $name);
        if ($option === false) {
            dashboard_convert_warn($warnings, $index, 'option_unknown_dropped',
                $name . '=' . dashboard_convert_snippet($value));
            continue;
        }

        // An empty option is kept. Absent and empty are not the same to the
        // render scripts. feedvalue only falls back to its units when both
        // prepend and append are absent, so an author who set one of them and
        // left the other empty gets the word undefined printed beside the
        // reading once the empty one stops being written.
        if ($value !== '' && !dashboard_convert_option_valid($option, $value)) {
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
            // Free text an author types. A render script puts several of these
            // into the page with .html(), so a value holding a tag would be
            // parsed as one, see the option values section of SCHEMA.md.
            // Angle brackets are the only way to open a tag: an entity in an
            // attribute arrives at .html() already decoded and is written back
            // as text. Quotes are kept because the renderer escapes them and
            // the curl widget sends a json payload through one of these.
            return preg_match('/^[^<>]{1,512}$/u', $value) === 1;

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
    $allowed = dashboard_convert_allowed_elements();
    $strip = dashboard_convert_stripped_elements();

    // Collected first because the list is modified while walking it.
    $children = array();
    foreach ($node->childNodes as $child) $children[] = $child;

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
                $child->ownerDocument->createTextNode($child->textContent), $child);
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

        if (in_array($tag, $strip)) {
            $detail = $tag;
            if ($tag === 'iframe') $detail = dashboard_convert_snippet($child->getAttribute('src'));
            dashboard_convert_warn($warnings, $index,
                $tag === 'iframe' ? 'iframe_dropped' : 'tag_dropped', $detail);
            $node->removeChild($child);
            continue;
        }

        if (!in_array($tag, $allowed)) {
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
    $per_element = dashboard_convert_allowed_attributes();
    $extensions = dashboard_convert_extension_attributes();

    $allowed = isset($per_element[$tag]) ? $per_element[$tag] : array();

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
                && !dashboard_convert_url_allowed($value, $lower)) {
                dashboard_convert_warn($warnings, $index, 'url_dropped',
                    dashboard_convert_snippet($value));
                $element->removeAttribute($name);
            }
            continue;
        }

        // on* handlers are covered here along with everything else that is not
        // on the list, and there is no rule above that could have kept one.
        if (!in_array($lower, $extensions)) {
            dashboard_convert_warn($warnings, $index, 'attribute_dropped', "$tag/$name");
        }
        $element->removeAttribute($name);
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
// What is left has to be http, https, mailto or a relative reference, and a url
// pointing back at this emoncms is held to more than that, see
// dashboard_convert_url_own_site.
function dashboard_convert_url_allowed($url, $attribute = 'href')
{
    $url = preg_replace('/[\x00-\x20\x7f]/', '', $url);
    if ($url === '') return false;
    if (preg_match('#^mailto:#i', $url)) return true;

    $host = dashboard_convert_url_host($url);
    if ($host === false) return false;
    if ($host !== '' && $host !== dashboard_convert_request_host()) return true;

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
    if (strpos($head[0], ':') !== false) return false;

    return '';
}

function dashboard_convert_url_strip_port($host)
{
    // Userinfo is dropped with the port, neither says which site is named.
    $at = strrpos($host, '@');
    if ($at !== false) $host = substr($host, $at + 1);

    $colon = strrpos($host, ':');
    if ($colon !== false && strpos($host, ']') === false) $host = substr($host, 0, $colon);

    return $host;
}

// The host this request came in on, or '' from the command line. A migration
// run has no request to read, so it cannot tell an absolute url pointing at
// this site from one pointing anywhere else. The renderer runs the same check
// on the way out, inside a request, and drops it then.
function dashboard_convert_request_host()
{
    if (!isset($_SERVER['HTTP_HOST'])) return '';
    return strtolower(dashboard_convert_url_strip_port($_SERVER['HTTP_HOST']));
}

/**
 * Whether a url pointing at this emoncms may be written.
 *
 * An image is fetched as the page draws, with no click and nothing shown, so a
 * src here has to be a static image file: an emoncms api call reached this way
 * runs as the person looking at the dashboard, and feed/delete.json is a GET.
 * A link needs a click and navigates the page, so an href may point at a page
 * but not at the api, which is what a format extension such as .json selects,
 * see the Route class. A url pointing anywhere else is not this module's to
 * police and is left alone.
 */
function dashboard_convert_url_own_site($url, $attribute)
{
    $path = preg_split('#[?\#]#', $url, 2);
    $path = $path[0];

    $segment = strrchr($path, '/');
    if ($segment !== false) $path = substr($segment, 1);

    $dot = strrpos($path, '.');
    $extension = ($dot === false) ? '' : strtolower(substr($path, $dot + 1));

    $images = array('png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'ico', 'avif');

    if ($attribute === 'src') {
        // A query string on a src is never needed to name a file and is the
        // shape every api call takes, so it goes with the rest.
        if (strpos($url, '?') !== false) return false;
        return in_array($extension, $images);
    }

    return $extension === '' || in_array($extension, $images)
        || in_array($extension, array('htm', 'html', 'pdf', 'txt'));
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
    $allowed = dashboard_convert_allowed_styles();
    $box_styles = dashboard_convert_box_styles();

    $kept = array();
    foreach ($declarations as $property => $value) {
        // The html path hands this parsed declarations, which are always
        // strings. A decoded document hands it whatever the column held, so
        // the shape is checked before anything is read from it.
        if (!is_string($property) || !is_string($value)) {
            dashboard_convert_warn($warnings, $index, 'style_declaration_unreadable',
                is_string($property) ? $property : '');
            continue;
        }
        $property = strtolower(trim($property));

        if ($box && in_array($property, $box_styles)) continue;

        if (!in_array($property, $allowed)) {
            if (!dashboard_convert_style_property_silent($property)) {
                dashboard_convert_warn($warnings, $index, 'style_property_dropped', $property);
            }
            continue;
        }

        if (!dashboard_convert_style_value_allowed($value)) {
            dashboard_convert_warn($warnings, $index, 'style_value_dropped',
                $property . ': ' . dashboard_convert_snippet($value));
            continue;
        }

        if ($property === 'opacity') {
            $opacity = dashboard_convert_style_opacity($value);
            if ($opacity === false) {
                dashboard_convert_warn($warnings, $index, 'style_value_dropped',
                    $property . ': ' . dashboard_convert_snippet($value));
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
            dashboard_convert_warn($warnings, $index, 'style_value_dropped',
                $property . ': ' . dashboard_convert_snippet($value));
            continue;
        }

        // A negative margin pulls content out of the widget box and up over the
        // emoncms menu bar, the same overlay the top clamp in the renderer
        // closes. Only ever reached for the html of a widget, the box path
        // drops margin before this, see dashboard_convert_box_styles. A
        // subtraction in calc() goes with it, the result can be negative too.
        if (substr($property, 0, 6) === 'margin' && preg_match('/-\s*[\d.]/', $value)) {
            dashboard_convert_warn($warnings, $index, 'style_value_dropped',
                $property . ': ' . dashboard_convert_snippet($value));
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
    if (substr($property, 0, 2) === '--') return true;
    if (substr($property, 0, 12) === 'font-variant') return true;
    // A prefixed transform is written beside the plain one, which is kept, so
    // there is nothing for the author to act on. No browser still needs them.
    if (preg_match('/^-(webkit|moz|ms|o)-transform$/', $property)) return true;

    return in_array($property, array('user-select', 'font-stretch', 'font-width',
        'font-size-adjust', 'font-kerning', 'font-feature-settings',
        'font-optical-sizing', 'font-variation-settings', 'word-break',
        'pointer-events'));
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
        $value) === 1;
}

function dashboard_convert_allowed_style_functions()
{
    return array('rgb', 'rgba', 'hsl', 'hsla', 'calc', 'rotate');
}

function dashboard_convert_style_value_allowed($value)
{
    if ($value === '' || strlen($value) > 256) return false;
    if (preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $value)) return false;
    // A backslash writes a css escape, which could spell a function name
    // another way, and the rest cannot appear in a value at all.
    if (preg_match('/[\\\\<>{}]/', $value)) return false;
    // A semicolon ends the declaration and starts another, which is how a
    // value carries position or z-index past the property allowlist. The html
    // path splits on it before this is reached, a decoded document does not,
    // and no value in the census holds either character.
    if (preg_match('/[;:]/', $value)) return false;
    // position on its own lifts a box out of the page.
    if (preg_match('/^\s*position\s*$/i', $value)) return false;

    if (preg_match_all('/([A-Za-z_-][A-Za-z0-9_-]*)\s*\(/', $value, $matches)) {
        $allowed = dashboard_convert_allowed_style_functions();
        foreach ($matches[1] as $function) {
            if (!in_array(strtolower($function), $allowed)) return false;
        }
    }

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
