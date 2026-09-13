# Dashboard content as JSON

Stage 2 of the move away from storing dashboard content as HTML. This describes
the stored document, the validation rules that replace AntiXSS, and what the
converter does with the existing corpus.

Written against the census of 5996 emoncms.org dashboards, see `census.php`.

## Storage

One new column. Add `content_json`, leave `content` untouched.

```php
'content_json' => array('type' => 'mediumtext'),
```

The existing `content` column stays the HTML it has always been, so the current
renderer keeps working with no changes and a rollback is only a matter of not
reading the new column. Copying the HTML into a `content_backup` column and
putting JSON in `content` would mean rewriting every row at migration time and
would silently break anything that still expects `content` to be HTML.

`content` is `text`, which caps at 64KB. Check the largest dashboard before
assuming JSON fits:

```
php -r '$m=0;foreach(file("census_dashboards.jsonl") as $l){$r=json_decode($l,true);
  if($r["bytes"]>$m)$m=$r["bytes"];} echo "largest: $m bytes\n";'
```

JSON should come out smaller than the HTML it replaces, because the generated
canvas, iframe and tooltip markup is discarded. `mediumtext` removes the
question either way.

Dashboard properties that already have their own columns (`height`, `gridsize`,
`backgroundcolor`, `fullscreen`, `name`) stay in those columns. The document
describes the widgets and nothing else.

Once every dashboard is converted and the renderer reads only `content_json`,
`content` can be dropped in a later migration. Not before.

## Document

```json
{
  "version": 1,
  "widgets": [ ... ],
  "meta": {
    "converted_at": "2026-09-13T09:12:04Z",
    "converter": 1,
    "warnings": [
      { "widget": 3, "code": "dropped_tag", "detail": "iframe" }
    ]
  }
}
```

`meta` is written by the converter and carries its own warnings, so judging how
well a dashboard converted needs no extra columns and no schema change as the
converter improves. It is absent on documents the editor writes.

Array order is DOM order, which is paint order. Preserve it.

## Widget

```json
{
  "type": "dial",
  "x": 0, "y": 40, "w": 140, "h": 120,
  "wunit": "px", "hunit": "px",
  "options": { "feedid": "821", "max": "3600", "scale": "1", "units": "W", "type": "0" }
}
```

| field | rule |
| --- | --- |
| `type` | required, one token, must be a known widget or carry `"unknown": true` |
| `x` `y` | required, integer, may be negative |
| `w` `h` | required, integer, `>= 0` |
| `wunit` `hunit` | `"px"` or `"pc"`, default `"px"` |
| `options` | object, string keys and string values |
| `html` | text and container widgets only, see below |
| `style` | text and container widgets only, allowlisted box styling |
| `unknown` | boolean, set by the converter for types not in the registry |

No `id`. The current markup carries `id="17"` from the designer's counter, and
those ids collide within a dashboard in at least 37 cases. The renderer assigns
ids from the array index instead, which fixes the collisions for free.

No `position` or `margin`. Every widget in the corpus is `position: absolute`
with `margin: 0` apart from a handful of anomalies, so the renderer supplies
both. The three `position: fixed` widgets convert with a warning and lose the
fixed positioning.

`style` only on text and container widgets. The box style of a data widget is
written by its render script at draw time, `draw_feedvalue` ends with a
`.css()` call setting colour, font, alignment and line height, and the editor
saves the drawn page. So the `color` on 5968 boxes and the `font` on 4892 is
generated, not authored, and is dropped without a warning. A `paragraph` has no
render script, so styling on its box is authored and is kept, filtered by the
same property allowlist used inside the html.

## Options

**Option values are strings.** The render scripts compare them as strings, for
example `if (font === "5")` in `feedvalue_render.js`, so a value stored as a
number would silently stop matching. This also preserves `scale: ".001"`,
`decimals: "-1"` and `units_dropdown: "__other"` exactly as written.

**An empty option is omitted.** The corpus is full of `scale=""`, `timeout=""`
and `errormessagedisplayed=""`, which the render scripts already treat as absent
through `x = x || default`. The renderer must emit no attribute at all for an
absent option.

One exception to check before relying on that: `vis_render.js` builds its iframe
URL from every attribute that is not `id`, `class` or `style`, so dropping an
empty option changes the query string from `&colour=` to nothing. Confirm the
vis endpoints treat the two the same.

Validation is per option, driven by the widget registry:

| option type | rule |
| --- | --- |
| `feedid` | digits only |
| `dropbox` | must be one of the declared values |
| `dropbox_other` | a declared value, or free text matching the option's pattern |
| `colour_picker` | 3 or 6 hex digits, no `#` |
| `boolean` | `"0"` or `"1"` |
| `value` | free text, length capped, no control characters |
| `html` | see below |

## The widget registry

The registry used to live only in JavaScript, in the `*_widgetlist` function of
each render script. It is now also written out as JSON so PHP can read it.

Each widget declares itself in a `*_widgets.json` file beside its render
script, following the naming `load_widget` in `Views/loadwidgets.php` already
uses:

    Modules/<module>/widget/<module>_widgets.json
    Modules/<module>/widget/<name>/<name>_widgets.json

The text and container widgets have no render script, so theirs is generated
from `Views/js/widgetlist.js` into `Modules/dashboard/widget/dashboard_widgets.json`.

The JSON holds the validation contract only, which options a widget accepts,
the type of each and the permitted values where the list is fixed. Labels,
hints and box geometry stay in the JavaScript, which is where the designer
reads them.

Read it from PHP with `widget_registry.php`:

    widget_registry()                       every widget type
    widget_registry_has($type)              is the type declared
    widget_registry_option($type, $name)    one option, or false

Three things the reader handles that a plain lookup would get wrong:

Option names are matched without regard to case. The designer declares some in
mixed case, `gradNumber` on `bar`, `periodLength` and six others on
`kwhperiod`, `titleThermometer` on `thermometer`. The browser lowercases
attribute names, so lowercase is the form stored content holds. Matching by
exact name puts 6495 stored options in production in the undeclared pile.

A declaration may carry a `legacy` block, listing options the designer no
longer offers but the render script still reads. There are five: `units` and
`unitend` on `feedvalue`, `feedtimestamp` and `kwhperiod`, and `title` on
`bar`, which `bar_render.js` reads and copies into `title_bar`. Together they
cover 3430 stored options. The block is hand written and preserved when
the declarations are regenerated.

A dropbox marked `dynamic` is filled from the database for the logged in user,
the saved graph list and the multigraph list. There is no fixed set to check
against, so the validator checks the shape of the value only.

### Keeping it in step

The declarations are generated, not hand written:

    node Modules/dashboard/tools/extract_registry.js

The widget lists are built at runtime, partly by literal and partly by
`addOption` calls, so the extractor runs them in a sandbox rather than trying
to parse them. Two checks guard the two ways it can go stale:

    node Modules/dashboard/tools/extract_registry.js --check
    php Modules/dashboard/tools/registry.php --audit

The first regenerates and compares, exiting non zero if a widget list has
changed without the JSON being regenerated. The second compares the
declarations against the attributes the render scripts actually read, which is
how the legacy blocks above were arrived at. It currently reports three, all
checked and none of them real: `feed` in `vis_render.js` and
`graph_render.js` is read into a variable that is never used, and `decimals`
in `feedtime_render.js` is read but was never added to the option list, so no
stored dashboard has one.

### What the registry does not cover

Comparing the registry against the production census, with
`php tools/registry.php --census=census.json`, leaves 1596 stored attributes
that no widget declares. Most of them are dead rather than unknown:

| attribute | count | what it is |
| --- | --- | --- |
| `feedname` | 776 | not read by any current code |
| `feed` | 721 | superseded by `feedid`, spread over `dial`, `jgauge`, `bar`, `led` |
| `botfeed`, `topfeed` | 46 | superseded by `botfeedid` and `topfeedid` on `cylinder` |
| the rest | 53 | one and two use oddities, and options left behind when a widget type was changed |

The converter drops these and records a warning, see the discard list below.

It also separates out 18269 attributes that were never options in the first
place, so they do not read as registry gaps:

| kind | count |
| --- | --- |
| broken style attributes | 14601 |
| designer `_dropdown` artefacts | 3597 |
| browser extension attributes | 71 |

The broken style attributes are the `htmlspecialchars_decode` corruption
described above. The `_dropdown` ones are a designer bug: the second select of
a `dropbox_other` option carries the same `options` class as the real inputs,
so the save handler in `designer.js` writes its id out as an attribute as
well. Neither is a registry problem, and the converter drops both.

## Text and container widgets

`paragraph`, `heading` and `heading-center` carry author-written HTML, and so do
some `Container-*` widgets, which hold hand-built tables. Those widgets may have
an `html` field. Everything else may not.

The field is validated by parsing it, checking every node against the allowlist,
and re-serialising from the parsed tree. What gets stored is what the validator
saw. Never repair by regex, and never store a string the validator has not
walked.

**Elements**

```
a b strong i em u sub sup br p div span center font small
h1 h2 h3 h4 h5 ul ol li table thead tbody tr th td img
```

That covers the census vocabulary. Everything else is dropped with a warning,
including `script`, `style`, `meta`, `title`, `link`, `object`, `embed`,
`iframe`, `svg`, `form`, `input`, `button` and `canvas`.

**Attributes**

| element | allowed |
| --- | --- |
| any | `style`, restricted below |
| `a` | `href`, `target`, `title` |
| `img` | `src`, `alt`, `width`, `height` |
| `font` | `color`, `face`, `size` |
| `table` | `border`, `cellpadding`, `cellspacing` |
| `td` `th` | `colspan`, `rowspan`, `align` |

Every other attribute is dropped. `on*` is dropped unconditionally and is never
reachable by any other rule.

**URLs** in `href` and `src` must be a relative path, or `http://`, `https://`
or `mailto:`. Everything else is dropped, `data:` included. Strip control
characters before testing the scheme, never after.

**Style properties**

```
color background-color font-size font-family font-weight font-style
text-align text-decoration line-height vertical-align
padding margin border width height
```

Values are matched against a pattern per property. Anything containing `url(`,
`expression(`, or a `position` declaration is dropped.

## What the converter discards

- Every child of a widget that is not a text or container widget. The corpus has
  8850 `canvas`, 6880 generated `iframe` and the `can-N-tooltip-1` and
  `can-N-tooltip-2` divs that `dial`, `bar` and `thermometer` inject. None of it
  is authored and the render scripts recreate all of it.
- The text content of data widgets. `feedvalue` boxes hold their last rendered
  reading, for example `>2.95 kWh<`, saved from whenever the dashboard was last
  edited. Stale data, and it should not be sitting in the database.
- Browser extension debris: `bis_skin_checked`, `_msttexthash`, `_msthash`,
  `wfd-id`, `data-darkreader-inline-color`, `data-dashlane-frameid`,
  `data-ruffle-polyfilled`, `__gchrome_childframeremotetoken` and the rest,
  along with `user-select` and `--darkreader-inline-color` in inline styles.
  These are in the data because the editor saves `$("#page").html()` from a live
  DOM that the reader's extensions have already modified.

Each discard that removes something author-written raises a warning. Discarding
a canvas does not.

## Unknown widget types

Nine classes in use are not in the deployed registry: `jgauge3`,
`timestoredaily`, `histgraph`, `Container-red`, `Container-333-Solid`,
`smoothie`, `stack`, `orderthreshold` and one pasted Tailwind class. 63
dashboards between them.

The converter keeps them, marked `"unknown": true`, with their options intact.
Nothing is lost and the renderer can draw a labelled placeholder, the same
treatment `loadwidgets.php` already gives disabled action widgets. Restoring a
widget later is then a matter of adding it back to the registry.

## Cases decided against keeping

All three are discarded with a warning, so the dashboards holding them can be
listed and looked at by hand. `tools/find.php` lists them with their dashboard
ids and userids.

**Nested widgets.** 243 divs and 61 canvases sit inside `paragraph` widgets,
meaning some users have nested real widgets inside text boxes. The flat model
has no place for them. The text converts, the nested widget is dropped.

**Hand-added iframes.** 53 of them, embedding external content, mostly inside
`paragraph`. The allowlist drops them. This is a capability reduction and
someone will notice. An explicit embed widget with a host allowlist would give
it back later without reopening the allowlist.

**The corrupted font values.** Roughly 3000 widgets have a truncated `style`
where `"Arial Black"` broke out of the attribute, caused by the
`htmlspecialchars_decode` in `set_content`. The broken style and the stray
attributes it produced are dropped. The widget's real options are unaffected,
so the render scripts draw from those. No attempt is made to reconstruct the
intended font from the fragments.

## The converter

`dashboard_convert.php` holds the conversion, `tools/convert.php` runs it over
the corpus and reports, and `tools/convert_test.php` tests it against shapes
taken from the census.

    php Modules/dashboard/tools/convert_test.php
    php Modules/dashboard/tools/convert.php
    php Modules/dashboard/tools/convert.php --id=1861
    php Modules/dashboard/tools/convert.php --show=iframe_dropped

Nothing is written to the database. `--out=FILE` writes the converted documents
to a JSONL file for inspection.

Dashboards are sorted into three groups. Clean means nothing an author wrote
was dropped, and is the group that can be migrated without being looked at.
Warned means something authored was dropped, and the warning codes say what.
Failed means the content could not be read, or held no widget worth keeping.

The warning codes marked with a star in the report are the authored ones. The
rest are the generated markup, designer artefacts and browser extension debris
described above, and a dashboard raising only those is clean.

## The renderer

`dashboard_render.php` turns a document back into the markup the widget scripts
expect, the same shape `designer.add_widget` writes in `Views/js/designer.js`,
so `render.js`, the designer and every widget script work on it unchanged.

```php
$result = dashboard_render($dashboard['content_json']);
echo $result['html'];
```

Everything is validated on the way out as well as on the way in. A document
that reached the column some other way cannot put a tag, an attribute or a url
into the page that the allowlist would have rejected. The cost is one parse of
the html of each text widget, on the one dashboard being viewed.

Widget ids are assigned from the array index, counting from one, not stored.

## The round trip check

`tools/roundtrip.php` converts stored content, renders it back and compares the
result against what was there before.

    php Modules/dashboard/tools/roundtrip.php
    php Modules/dashboard/tools/roundtrip.php --id=1861
    php Modules/dashboard/tools/roundtrip.php --show=option_lost

This is the measure of whether a dashboard can be migrated. The converter
report says nothing an author wrote was dropped. This says the dashboard draws
with the same widgets, in the same places, with the same options and the same
text.

Both sides are read with the same deliberately simple extraction that knows
nothing about the converter, so a fault in the converter and a fault in the
renderer both show up. A difference is expected when it is something the
migration set out to drop. Anything else is a fault, and the exit code is non
zero if there are any.

Dashboards come out in three groups. Identical means nothing changed at all. As
intended means the only changes were ones the migration set out to make. Both
are safe to migrate. Faults are not.

## Migration

1. Add the `content_json` column. Done, see `dashboard_schema.php`.
2. Measure. `tools/convert.php` for what is dropped, `tools/roundtrip.php` for
   whether it still draws the same. Not a parse success count.
3. Convert and write `content_json`, leaving `content` alone.
4. Read `content_json` when it is there, falling back to `content`.
5. Save through the converter, so what is stored is what the allowlist passed.
6. Drop `content` in a later release, not the same one.

Steps 4 and 5 go together. Reading the new column while the editor still writes
html to the old one leaves the two disagreeing the first time anyone saves,
with the reader showing one thing and the editor another.

The smallest way to do both at once is to convert on save rather than rewriting
the designer. `set_content` already receives the page html the designer built.
Putting it through `dashboard_convert` and storing the document means the
server decides what is kept, which is the allowlist the whole exercise is for,
and the designer needs no changes. The round trip figures are the evidence that
this loses nothing.
