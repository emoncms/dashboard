<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

---------------------------------------------------------------------
Stage 4 of the move to JSON dashboard content.

Converts a paragraph, heading or heading-center widget to a text or image
widget. Nothing is written to the database. It is called by
tools/convert_text.php to measure the corpus, and by dashboard_migrate.php on
save and in bulk.

A conversion either keeps the same meaning or is refused. When a value cannot
be carried, the widget is refused and the reason is recorded.

See notes/TEXT-AND-IMAGE-WIDGETS.md.
*/

defined('EMONCMS_EXEC') or die('Restricted access');

// The widget.css styling of the three old types, written out as options
// because the new widget has no styling of its own. A paragraph draws from
// the top of its box, so it is aligned top. A heading has 20px of top
// padding, which has no option, so it is centred and only converted at the
// default box height, where the two draw the same. See the heading defaults
// section of notes/TEXT-AND-IMAGE-WIDGETS.md.
function dashboard_convert_text_widget_defaults()
{
    return array(
        'paragraph' => array('valign' => 'top'),
        'heading' => array('size' => '24', 'weight' => 'bold'),
        'heading-center' => array('size' => '24', 'weight' => 'bold', 'align' => 'center')
    );
}

// Box height in px at which a heading draws the same centred as it does with
// its top padding. The designer's default height for the old text widgets.
define('DASHBOARD_CONVERT_TEXT_HEADING_HEIGHT', 60);

// Whether a heading sits at the height its padding was measured against.
function dashboard_convert_text_heading_height_ok($widget)
{
    if (isset($widget['hunit']) && $widget['hunit'] !== 'px') return false;
    $h = isset($widget['h']) ? $widget['h'] : null;
    return is_numeric($h) && (int) $h === DASHBOARD_CONVERT_TEXT_HEADING_HEIGHT;
}

// Elements removed from the outside of the html and read as options. Each
// one wraps the whole widget, so its styling applies to the whole box.
function dashboard_convert_text_wrapper_elements()
{
    return array('div', 'span', 'center', 'font', 'b', 'strong', 'a');
}

// Elements rewritten to their equivalent in the allowed list.
function dashboard_convert_text_synonyms()
{
    return array('strong' => 'b', 'em' => 'i');
}

// Colour names used in the corpus. Any other name is refused.
function dashboard_convert_text_colour_names()
{
    return array(
        'black' => '000000', 'white' => 'ffffff', 'red' => 'ff0000',
        'green' => '008000', 'blue' => '0000ff', 'yellow' => 'ffff00',
        'orange' => 'ffa500', 'purple' => '800080', 'grey' => '808080',
        'gray' => '808080', 'silver' => 'c0c0c0', 'lime' => '00ff00',
        'navy' => '000080', 'teal' => '008080', 'maroon' => '800000',
        'olive' => '808000', 'aqua' => '00ffff', 'cyan' => '00ffff',
        'fuchsia' => 'ff00ff', 'magenta' => 'ff00ff'
    );
}

// The px size of each font tag size attribute. These are absolute in every
// browser.
function dashboard_convert_text_font_sizes()
{
    return array(1 => 10, 2 => 13, 3 => 16, 4 => 18, 5 => 24, 6 => 32, 7 => 48);
}

// Declarations written by the editor and the browser, not the author. Each
// has one value throughout the corpus, so they are skipped.
function dashboard_convert_text_ignored_styles()
{
    return array('vertical-align', 'user-select', '-webkit-user-select',
        '-moz-user-select', '-ms-user-select');
}

/**
 * Converts an old text widget into a text or image widget.
 *
 * @param array $widget a widget of a converted document, type paragraph,
 *                      heading or heading-center
 * @param string $reason set to why it was refused, when it was
 * @return array|null the new widget, or null
 */
function dashboard_convert_text_widget($widget, &$reason = null)
{
    $reason = '';
    $defaults = dashboard_convert_text_widget_defaults();

    $type = isset($widget['type']) ? (string) $widget['type'] : '';
    if (!isset($defaults[$type])) return dashboard_convert_text_refuse($reason, 'not_an_old_text_widget');

    // paragraph, heading and heading-center declare only the html option, so
    // any other option is unexpected.
    if (!empty($widget['options'])) {
        return dashboard_convert_text_refuse($reason, 'widget_has_options');
    }

    // The new widgets hold no box style. The renderer writes the geometry and
    // the rest is options, so a border or background cannot be carried.
    if (!empty($widget['style'])) {
        return dashboard_convert_text_refuse($reason, 'box_style');
    }

    // A heading at another height draws its text somewhere the centred text
    // widget does not, by half the difference in height.
    if ($type !== 'paragraph' && !dashboard_convert_text_heading_height_ok($widget)) {
        $h = isset($widget['h']) ? $widget['h'] : '?';
        $unit = isset($widget['hunit']) && $widget['hunit'] === 'pc' ? '%' : 'px';
        return dashboard_convert_text_refuse($reason, 'heading_height', $h . $unit);
    }

    $html = isset($widget['html']) && is_string($widget['html']) ? $widget['html'] : '';

    if (trim($html) === '') {
        return dashboard_convert_text_build($widget, 'text',
            $defaults[$type] + array('text' => ''), $reason);
    }

    $root = dashboard_convert_parse($html);
    if ($root === null) return dashboard_convert_text_refuse($reason, 'unparsable');

    // The author's styling is on the elements inside the box. The wrappers are
    // removed first and their styling becomes options.
    $styles = $defaults[$type];
    $link = '';
    $body = dashboard_convert_text_peel($root, $styles, $link, $reason);
    if ($body === null) return null;

    $image = dashboard_convert_text_image($body);
    if ($image !== null) {
        return dashboard_convert_text_image_widget($widget, $image, $link, $reason);
    }

    if ($link !== '') {
        // The text widget has no link option, so a link around the whole widget
        // is written back as an a element in the body.
        $inner = dashboard_convert_text_body_text($body, $reason);
        if ($inner === null) return null;
        if (trim(strip_tags($inner)) === '') return dashboard_convert_text_refuse($reason, 'empty_link');
        $styles['text'] = '<a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">'
            . $inner . '</a>';
        return dashboard_convert_text_build($widget, 'text', $styles, $reason);
    }

    $text = dashboard_convert_text_body_text($body, $reason);
    if ($text === null) return null;

    $styles['text'] = $text;
    return dashboard_convert_text_build($widget, 'text', $styles, $reason);
}

function dashboard_convert_text_refuse(&$reason, $code, $detail = '')
{
    $reason = $detail === '' ? $code : $code . ': ' . $detail;
    return null;
}

/**
 * Walks through the elements that wrap the whole widget, reading their
 * styling into $styles. Returns the element holding the content, or null when
 * a value cannot be carried.
 *
 * @param DOMElement $node
 * @param array $styles added to in place
 * @param string $link set to the href of an a wrapping the whole widget
 * @param string $reason
 * @return DOMElement|null
 */
function dashboard_convert_text_peel($node, &$styles, &$link, &$reason)
{
    $wrappers = dashboard_convert_text_wrapper_elements();

    while (true) {
        $element = dashboard_convert_text_only_child($node);
        if ($element === null) return $node;

        $tag = strtolower($element->nodeName);
        if (!in_array($tag, $wrappers)) return $node;

        if ($tag === 'center') $styles['align'] = 'center';
        if ($tag === 'b' || $tag === 'strong') $styles['weight'] = 'bold';

        if ($tag === 'a') {
            // Only one link is carried. The text widget writes it as a single
            // a around the body and the image widget as one link option.
            if ($link !== '') return dashboard_convert_text_refuse($reason, 'nested_link');
            $href = $element->getAttribute('href');
            if (!dashboard_convert_url_allowed($href, 'href')) {
                return dashboard_convert_text_refuse($reason, 'link_url_not_allowed',
                    dashboard_convert_snippet($href));
            }
            $link = $href;
        }

        if ($tag === 'font' && !dashboard_convert_text_font_tag($element, $styles, $reason)) {
            return null;
        }

        if (!dashboard_convert_text_attributes($element, $tag, $styles, $reason)) return null;

        $node = $element;
    }
}

// The single element child of a node. Whitespace text is ignored. Any other
// text beside the element is styled differently, which one set of options
// cannot express, so null is returned.
function dashboard_convert_text_only_child($node)
{
    $element = null;
    foreach ($node->childNodes as $child) {
        if ($child->nodeType === XML_TEXT_NODE) {
            if (trim($child->nodeValue) !== '') return null;
            continue;
        }
        if ($child->nodeType === XML_COMMENT_NODE) continue;
        if ($child->nodeType !== XML_ELEMENT_NODE) return null;
        if ($element !== null) return null;
        $element = $child;
    }
    return $element;
}

// Reads the attributes of a wrapper. style and the font tag attributes are
// carried. Any other attribute refuses the widget.
function dashboard_convert_text_attributes($element, $tag, &$styles, &$reason)
{
    $taken = array('style');
    if ($tag === 'font') $taken = array('style', 'size', 'color', 'face');
    if ($tag === 'a') $taken = array('style', 'href', 'target', 'rel', 'title');

    foreach ($element->attributes as $attribute) {
        $name = strtolower($attribute->nodeName);
        if (in_array($name, $taken)) continue;
        if (in_array($name, dashboard_convert_extension_attributes())) continue;
        if ($name === 'class' || $name === 'id') continue;
        return dashboard_convert_text_refuse($reason, 'attribute_not_carried', $tag . ' ' . $name);
    }

    if (!$element->hasAttribute('style')) return true;

    return dashboard_convert_text_styles(
        dashboard_convert_parse_style($element->getAttribute('style')), $styles, $reason);
}

// Reads style declarations into options. A property with no matching option
// refuses the widget.
function dashboard_convert_text_styles($declarations, &$styles, &$reason)
{
    $ignored = dashboard_convert_text_ignored_styles();

    foreach ($declarations as $property => $value) {
        if (!is_string($property) || !is_string($value)) {
            return dashboard_convert_text_refuse($reason, 'style_unreadable');
        }
        $value = trim($value);
        if ($value === '' || in_array($property, $ignored)) continue;

        switch ($property) {
            case 'font-size':
                $size = dashboard_convert_text_size($value);
                if ($size === null) {
                    return dashboard_convert_text_refuse($reason, 'font_size_not_carried', $value);
                }
                $styles['size'] = (string) $size;
                break;

            case 'color':
                $colour = dashboard_convert_text_colour($value);
                if ($colour === null) {
                    return dashboard_convert_text_refuse($reason, 'colour_not_carried', $value);
                }
                $styles['colour'] = $colour;
                break;

            case 'font-weight':
                $weight = dashboard_convert_text_weight($value);
                if ($weight === null) {
                    return dashboard_convert_text_refuse($reason, 'weight_not_carried', $value);
                }
                $styles['weight'] = $weight;
                break;

            case 'font-family':
                $font = dashboard_convert_text_font_family($value);
                if ($font === null) {
                    return dashboard_convert_text_refuse($reason, 'font_not_carried', $value);
                }
                $styles['font'] = $font;
                break;

            case 'text-align':
                $align = strtolower($value);
                if (!in_array($align, array('left', 'center', 'right'))) {
                    return dashboard_convert_text_refuse($reason, 'align_not_carried', $value);
                }
                $styles['align'] = $align;
                break;

            case 'transform':
                // The old widget turns an element the height of its text
                // and the text widget turns the whole box, so the text lands
                // somewhere else. The author sets the rotate option by hand.
                if (dashboard_convert_style_rotate_only($value)) {
                    return dashboard_convert_text_refuse($reason, 'rotation_moves_text', $value);
                }
                return dashboard_convert_text_refuse($reason, 'transform_not_carried', $value);

            default:
                return dashboard_convert_text_refuse($reason, 'style_not_carried', $property);
        }
    }

    return true;
}

// The three attributes of a font tag. size is absolute, so it does not depend
// on the box.
function dashboard_convert_text_font_tag($element, &$styles, &$reason)
{
    if ($element->hasAttribute('size')) {
        $size = trim($element->getAttribute('size'));
        $sizes = dashboard_convert_text_font_sizes();
        // A leading sign is relative to the current size, which is not known
        // here.
        if (!preg_match('/^[1-7]$/', $size)) {
            return dashboard_convert_text_refuse($reason, 'font_size_not_carried', 'size=' . $size);
        }
        $styles['size'] = (string) $sizes[(int) $size];
    }

    if ($element->hasAttribute('color')) {
        $colour = dashboard_convert_text_colour($element->getAttribute('color'));
        if ($colour === null) {
            return dashboard_convert_text_refuse($reason, 'colour_not_carried',
                'color=' . $element->getAttribute('color'));
        }
        $styles['colour'] = $colour;
    }

    if ($element->hasAttribute('face')) {
        $font = dashboard_convert_text_font_family($element->getAttribute('face'));
        if ($font === null) {
            return dashboard_convert_text_refuse($reason, 'font_not_carried',
                'face=' . $element->getAttribute('face'));
        }
        $styles['font'] = $font;
    }

    return true;
}

// px and pt convert to a whole number of px. em, per cent and keywords are
// relative to a size that is not known here, so they are refused.
function dashboard_convert_text_size($value)
{
    if (!preg_match('/^(\d+(\.\d+)?)\s*(px|pt)?$/i', trim($value), $match)) return null;
    $number = (float) $match[1];
    if (isset($match[3]) && strtolower($match[3]) === 'pt') $number = $number * 4 / 3;
    $px = (int) round($number);
    if ($px < 6 || $px > 200) return null;
    return $px;
}

// A colour as the six hex digits the colour_picker option holds.
function dashboard_convert_text_colour($value)
{
    $value = strtolower(trim($value));

    if (preg_match('/^#?([0-9a-f]{6})$/', $value, $match)) return $match[1];
    if (preg_match('/^#?([0-9a-f]{3})$/', $value, $match)) {
        return $match[1][0] . $match[1][0] . $match[1][1] . $match[1][1]
            . $match[1][2] . $match[1][2];
    }
    if (preg_match('/^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/', $value, $match)) {
        $hex = '';
        for ($i = 1; $i <= 3; $i++) {
            if ((int) $match[$i] > 255) return null;
            $hex .= sprintf('%02x', (int) $match[$i]);
        }
        return $hex;
    }

    $names = dashboard_convert_text_colour_names();
    return isset($names[$value]) ? $names[$value] : null;
}

function dashboard_convert_text_weight($value)
{
    $value = strtolower(trim($value));
    if (in_array($value, array('bold', 'bolder', '600', '700', '800', '900'))) return 'bold';
    if (in_array($value, array('normal', 'lighter', '100', '200', '300', '400', '500'))) return 'normal';
    return null;
}

// The first family named, which must be one the widget offers. The option is
// a single family, so fallbacks are dropped.
function dashboard_convert_text_font_family($value)
{
    $first = trim(explode(',', $value)[0]);
    $first = trim($first, "\"'");
    if ($first === '') return null;

    $option = widget_registry_option('text', 'font');
    if ($option === false || $option['values'] === null) return null;

    foreach ($option['values'] as $offered) {
        if ($offered !== '' && strcasecmp($offered, $first) === 0) return $offered;
    }
    return null;
}

// ---------------------------------------------------------------------------
// The body
// ---------------------------------------------------------------------------

// The image of a widget that holds an image and no text, or null.
function dashboard_convert_text_image($body)
{
    $image = null;
    foreach ($body->childNodes as $child) {
        if ($child->nodeType === XML_TEXT_NODE) {
            if (trim($child->nodeValue) !== '') return null;
            continue;
        }
        if ($child->nodeType === XML_COMMENT_NODE) continue;
        if ($child->nodeType !== XML_ELEMENT_NODE) return null;
        if (strtolower($child->nodeName) !== 'img') return null;
        if ($image !== null) return null;
        $image = $child;
    }
    return $image;
}

function dashboard_convert_text_image_widget($widget, $image, $link, &$reason)
{
    $src = $image->getAttribute('src');
    if (!dashboard_convert_url_allowed($src, 'src')) {
        return dashboard_convert_text_refuse($reason, 'image_url_not_allowed',
            dashboard_convert_snippet($src));
    }

    // The image widget fills the box. contain keeps the aspect ratio.
    $options = array('src' => $src, 'fit' => 'contain');
    if ($image->hasAttribute('alt')) $options['alt'] = $image->getAttribute('alt');
    if ($link !== '') $options['link'] = $link;

    return dashboard_convert_text_build($widget, 'image', $options, $reason);
}

/**
 * The content of the widget as the text field holds it.
 *
 * Every element must be in the allowed list. An element that would be dropped
 * refuses the whole widget.
 *
 * @return string|null
 */
function dashboard_convert_text_body_text($body, &$reason)
{
    $vocabulary = dashboard_convert_inline_elements();
    $synonyms = dashboard_convert_text_synonyms();

    $stack = array($body);
    while (count($stack)) {
        $current = array_pop($stack);
        foreach ($current->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) continue;
            if ($child->nodeType === XML_COMMENT_NODE) continue;
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                return dashboard_convert_text_refuse($reason, 'node_not_carried');
            }

            $tag = strtolower($child->nodeName);
            if (isset($synonyms[$tag])) $tag = $synonyms[$tag];
            if (!in_array($tag, $vocabulary)) {
                return dashboard_convert_text_refuse($reason, 'tag_not_in_vocabulary', $tag);
            }

            foreach ($child->attributes as $attribute) {
                $name = strtolower($attribute->nodeName);
                if (in_array($name, dashboard_convert_extension_attributes())) continue;
                if ($tag === 'a' && in_array($name, array('href', 'target', 'rel', 'title'))) continue;
                return dashboard_convert_text_refuse($reason, 'inline_attribute_not_carried',
                    $tag . ' ' . $name);
            }

            if ($tag === 'a' && !dashboard_convert_url_allowed($child->getAttribute('href'), 'href')) {
                return dashboard_convert_text_refuse($reason, 'link_url_not_allowed',
                    dashboard_convert_snippet($child->getAttribute('href')));
            }

            $stack[] = $child;
        }
    }

    // strong and em are not in the allowed list, so they are rewritten as b
    // and i before the body is read back.
    dashboard_convert_text_rewrite_synonyms($body);

    $warnings = array();
    $text = dashboard_convert_html($body, null, widget_registry(), $warnings,
        $vocabulary, false);

    foreach ($warnings as $warning) {
        if ($warning['code'] === 'url_dropped' || $warning['code'] === 'attribute_dropped'
            || $warning['code'] === 'tag_dropped' || $warning['code'] === 'tag_unwrapped') {
            return dashboard_convert_text_refuse($reason, 'lost_on_the_way_out', $warning['code']);
        }
    }

    return $text;
}

// ---------------------------------------------------------------------------
// The new widget
// ---------------------------------------------------------------------------

/**
 * Builds the new widget with the geometry of the old one and checks every
 * value against the registry, so the result can always be stored and drawn.
 *
 * @return array|null
 */
function dashboard_convert_text_build($widget, $type, $values, &$reason)
{
    $new = array('type' => $type);
    foreach (array('x', 'y', 'w', 'h', 'wunit', 'hunit') as $key) {
        if (isset($widget[$key])) $new[$key] = $widget[$key];
    }

    $text = '';
    $options = array();
    foreach ($values as $name => $value) {
        if ($value === '') continue;

        $option = widget_registry_option($type, $name);
        if ($option === false) return dashboard_convert_text_refuse($reason, 'option_unknown', $name);

        if ($option['type'] === 'text') {
            $text = (string) $value;
            continue;
        }
        if (!dashboard_convert_option_valid($option, (string) $value)) {
            return dashboard_convert_text_refuse($reason, 'option_value_refused',
                $name . '=' . dashboard_convert_snippet($value));
        }
        $options[$option['name']] = (string) $value;
    }

    $new['options'] = $options;
    if ($text !== '') $new['text'] = $text;

    return $new;
}

// Renames synonym elements in place. Collected first because the tree is
// changed while walking it.
function dashboard_convert_text_rewrite_synonyms($body)
{
    $synonyms = dashboard_convert_text_synonyms();

    $found = array();
    $stack = array($body);
    while (count($stack)) {
        $current = array_pop($stack);
        foreach ($current->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) continue;
            if (isset($synonyms[strtolower($child->nodeName)])) $found[] = $child;
            $stack[] = $child;
        }
    }

    foreach ($found as $element) {
        $replacement = $element->ownerDocument->createElement(
            $synonyms[strtolower($element->nodeName)]);
        while ($element->firstChild) $replacement->appendChild($element->firstChild);
        $element->parentNode->replaceChild($replacement, $element);
    }
}
