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
| `x` `y` | required, integer, may be negative, `y` is clamped to `0` when drawn |
| `w` `h` | required, integer, `>= 0` |
| `wunit` `hunit` | `"px"` or `"pc"`, default `"px"` |
| `options` | object, string keys and string values |
| `html` | text and container widgets only, see below |
| `style` | text and container widgets only, allowlisted box styling |
| `unknown` | boolean, set by the converter for types not in the registry |

No `id`. The current markup carries `id="17"` from the designer's counter, and
those ids collide within a dashboard in at least 37 cases. The renderer assigns
ids from the array index instead, which fixes the collisions for free.

A negative `y` is drawn at the top of the page. Widgets are positioned inside
`#page-container`, which starts below the emoncms menu bar, so a negative top
lifts author content over the site's own chrome. A negative `x` is left as
written, it only moves the box off the side of the page where there is nothing
to sit on top of.

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

**An empty option is kept.** The corpus is full of `scale=""`, `timeout=""` and
`errormessagedisplayed=""`. These were omitted at first, on the grounds that the
render scripts read them through `x = x || default` and so treat absent and
empty the same. Enough of them do not.

`feedvalue_render.js` falls back to its units only when `prepend` and `append`
are both absent, so an author who set one and left the other empty got the word
undefined printed beside the reading. `vis_render.js` builds its iframe URL from
every attribute that is not `id`, `class` or `style`, so an omitted empty option
changes the query string from `&colour=` to nothing.

Absent and empty are not the same thing to a render script, and which of the two
an author meant is not knowable from the html. An empty option is written back
as the author left it, and validation is skipped for it: an empty string carries
nothing, and several of the option rules require at least one character.

Validation is per option, driven by the widget registry:

| option type | rule |
| --- | --- |
| `feedid` | digits, or a tag:name association, no control characters and no `<>"'` |
| `dropbox` | must be one of the declared values, or the same as `feedid` when the list is filled per user |
| `dropbox_other` | free text, see below |
| `colour_picker` | 3 or 6 hex digits, `#` optional |
| `boolean` | `"0"` or `"1"` |
| `value` | free text, see below |
| `html` | never an attribute, always dropped, see below |

**An `html` option is not an attribute.** It names the content of the widget's
box, which the designer writes with `.html()` and the document keeps in its own
`html` field. No dashboard in the census carries an `html` attribute, so one
that turns up was not authored, and it is dropped rather than put back on the
page.

**Free text options**, `value` and `dropbox_other`, are 1 to 512 characters with
no control characters and no `<` or `>`. These hold what an author types: units,
a title, a prepend and append, a curl url and payload. Several render scripts
put them into the page with `.html()`, `feedvalue` and `kwhperiod` write
`prepend + val + append` and `feedtime` writes `val + units`, so a value holding
a tag would be parsed as one. Angle brackets are the only way to open a tag. An
entity written in the attribute arrives already decoded, so it cannot spell one
another way, and an entity that survives as text is written back out as text by
`.html()`.

Quotes are kept. The renderer escapes them, and the `curl` widget sends a json
payload through one of these options. Nothing may build markup by concatenating
an option value into an html string. `graph_render.js` and `vis_render.js` used
to build their embed iframe that way, and now set the url on an element they
create, with both halves of every query parameter encoded.

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
| `a` | `href`, `target`, `title`, `rel` |
| `img` | `src`, `alt`, `width`, `height` |
| `font` | `color`, `face`, `size` |
| `table` | `border`, `cellpadding`, `cellspacing` |
| `td` `th` | `colspan`, `rowspan`, `align` |

Every other attribute is dropped. `on*` is dropped unconditionally and is never
reachable by any other rule.

`rel` is written rather than read. An `a` with a `target` is given
`rel="noopener noreferrer"`, on the way in and on the way out, so a link opening
in another tab does not hand that tab a handle to the dashboard. It is on the
allowlist so it survives the round trip.

**URLs** in `href` and `src` must be a relative path, or `http://`, `https://`
or `mailto:`. Everything else is dropped, `data:` included. Strip control
characters before testing the scheme, never after.

A url pointing back at this emoncms is held to more than that. The browser of
whoever is looking at the dashboard sends it, with their session, and an
emoncms api call is a GET: `feed/delete.json?id=1` in the `src` of an image is
a feed deleted with no click and nothing shown. So on this site:

- a `src` must name a static image file, one of `png jpg jpeg gif webp svg bmp
  ico avif`, and carry no query string. A cache busting `?v=2` goes with the
  rest, it is not needed to name a file.
- an `href` may point at a page but not at the api. A format extension is what
  selects the api, see the `Route` class, so `.json`, `.csv` and the rest are
  dropped while `dashboard/view?id=2` is kept.

A url pointing anywhere else is not this module's to police and is left alone.
Same site is decided by comparing the host against the host the request came in
on. A migration run from the command line has no request to read, so it cannot
tell an absolute url pointing at this site from any other. The renderer runs
the same check on the way out, inside a request, and drops it then.

The endpoints are the other half of this. An emoncms api call that changes or
deletes something should not answer a GET, and several still do.

**Style properties**

```
text        color font font-size font-family font-weight font-style
            letter-spacing line-height text-align text-decoration
            text-transform vertical-align white-space

paint       background background-color border border-top border-right
            border-bottom border-left border-color border-style border-width
            border-radius border-collapse border-spacing box-shadow opacity

box         padding padding-top padding-right padding-bottom padding-left
            margin margin-top margin-right margin-bottom margin-left
            width height max-width min-width max-height min-height
            display visibility overflow float table-layout
            align-items justify-content flex-wrap

other       transform, rotation only
```

The list is drawn from the style properties stored dashboards actually use. A
value is dropped whatever the property is if it calls anything but `rgb`,
`rgba`, `hsl`, `hsla`, `calc` or `rotate`, or if it is a `position` declaration, so
nothing on the list can fetch or run anything. The functions are named the
allowed way round because the ways of writing a fetch are not a list to keep up
with: `url()`, `image-set()` and its vendor spellings, `element()`, `paint()`.

`opacity` is floored at 0.2, on a widget box and inside its html. A widget at
zero opacity is invisible and still takes clicks, which on a public dashboard
puts an unseen `curl` or `button` widget over something the visitor means to
press. A value below the floor is raised and the author is told. An opacity
written some other way, `calc(0.1)` for example, cannot be read here, so it is
dropped rather than left through. `display: none` and `visibility: hidden` are
left alone, they take the box out of hit testing.

`box-shadow` can paint outside its own box, so a widget can put colour over the
rest of the page. It cannot take a click, so it is kept.

A negative margin is dropped, with a warning. Inside the html of a widget it
pulls content out of the widget box and up over the emoncms menu bar, which is
the same overlay a negative `top` gives. A subtraction inside `calc()` goes
with it, the result of that can be negative too. On a widget box margin is
dropped before this, see below.

`position`, `top`, `left`, `width`, `height` and the margins are dropped from a
widget box without a warning. The designer writes them and the renderer puts
them back from the document, so what is stored is generated. Inside the html of
a text or container widget they are kept.

Off the list on purpose: `position` and `z-index`, which lift a box out of the
page and restack it.

`transform` is on the list for a single `rotate()` and nothing else. Rotation
turns a box where it stands, so it covers no more of the page than its geometry
already allows, while `translate`, `scale` and `matrix` move or grow it, which
is the overlay `gate_action_widgets` closes. Authors use it to stand a label on
its side, and 21 dashboards in the corpus write their rotation in a `<style>`
block, which is stripped whole, so an inline `transform` is the only way they
can put those labels back. The prefixed spellings are dropped without a warning:
they say the same thing as the property that is kept.

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
  along with `user-select`, `word-break`, `pointer-events`, `font-stretch`,
  `font-width`, `font-size-adjust`, `font-kerning`, `font-feature-settings`,
  `font-optical-sizing`, `font-variation-settings`, the `font-variant` family
  and the `--darkreader-*` custom properties in inline styles. The style
  properties are dropped without a warning, see
  `dashboard_convert_style_property_silent`.
  These are in the data because the editor saves `$("#page").html()` from a live
  DOM that the reader's extensions have already modified.

Each discard that removes something author-written raises a warning. Discarding
a canvas does not.

## Unknown widget types

Nine classes in use are not in the deployed registry: `jgauge3`,
`timestoredaily`, `histgraph`, `Container-red`, `Container-333-Solid`,
`smoothie`, `stack`, `orderthreshold` and one pasted Tailwind class. 63
dashboards between them.

The converter keeps them, marked `"unknown": true`, with their geometry and
their box styling. Their attributes are dropped, each with an
`unknown_widget_option_dropped` warning.

The attributes go because nothing declares the widget, so nothing says which of
them are options and which would act on the page. The name alone does not
settle it: `onmouseover` is shaped exactly like an option name, and a widget
type is whatever an author typed into a class attribute. Rendering an
undeclared attribute put an author written event handler on the page, on public
dashboards included.

The renderer draws a labelled placeholder in the author's box, the same
treatment `loadwidgets.php` already gives disabled action widgets. A container
draws itself from its own html and keeps it.

What this costs, across the corpus: `jgauge3` loses `feedid scale max min units
decimals` on 20 dashboards, `histgraph` and `timestoredaily` lose `feedid` and
`units` on 13 and 12, `orderthreshold` and `smoothie` lose theirs on one and
three. None of those widgets have a render script deployed, so none of them
draw today and none of the values are in use. The `content` column still holds
the html they came from. Export it before that column is dropped if the
configurations are wanted for anything.

Restoring a widget is still a matter of adding it back to the registry. Its
boxes come back in place, to be configured again.

## Cases decided against keeping

All four are discarded with a warning, so the dashboards holding them can be
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

**Author stylesheets.** 21 dashboards hold a `<style>` block, pasted in with the
rest of an html document. A stylesheet is not scoped to the widget holding it,
so a rule in one reaches every widget on the page, and `id` and `class` are not
kept on the elements inside a widget, so a selector would have nothing left to
match anyway. The block is stripped whole. What it styled stays on the page
unstyled, and the effect has to be written again as inline style on the elements
themselves. The rotations these blocks mostly carry are what put `transform` on
the style list. `find.php script` lists them, along with the other tags that are
stripped whole.

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

Tags and text on their own do not show a url going missing: an image whose src
was dropped is still an image with no text, so the src and href values inside a
widget are compared as well. A url that the allowlist rejects counts as
intended, one it would have kept is a fault. This is what shows the same site
rule working on a real dashboard, `--show=url_dropped` lists them.

A widget the renderer moves is intended when it moved for a reason the renderer
gives: `geometry_supplied` for a widget whose style was destroyed and has no
geometry to preserve, `negative_top_clamped` and `negative_size_clamped` for the
clamps. Anything else moving is `geometry_changed` and is a fault.

## Migration

1. Add the `content_json` column. Done, see `dashboard_schema.php`.
2. Measure. `tools/convert.php` for what is dropped, `tools/roundtrip.php` for
   whether it still draws the same. Not a parse success count.
3. Convert and write `content_json`, leaving `content` alone. Done, in
   `Dashboard::convert_content`.
4. Read `content_json`. Done, in `Dashboard::content_html`.
5. Save through the converter. Done, in `Dashboard::set_content`.
6. Drop `content` in a later release, not the same one.

Steps 4 and 5 had to go together. Reading the new column while the editor still
wrote html to the old one would leave the two disagreeing the first time anyone
saved, with the reader showing one thing and the editor another.

### How it works now

A dashboard still holding html is converted the first time it is loaded, and
the document is stored. Nothing else has to be run, and an install migrates
itself as its dashboards are opened.

The `content` column is not written again. It holds what was there before the
conversion, so a dashboard that converted badly can be looked at and converted
again. That is what it is for until it is dropped.

`set_content` receives the page html the designer built and converts it, which
is what makes the server rather than the browser decide what a dashboard may
hold. The designer needed no changes. Anything the allowlist does not keep is
reported back in the save response and shown in the editor, rather than
disappearing without comment.

The document the editor writes carries no `meta` block. It records how a
conversion went, which belongs to the migration, and its warnings would
otherwise store fragments of whatever was posted.

This replaced the AntiXSS filter, which has been removed. It looked for markup
known to be dangerous and refused the save when it found any. Listing what is
allowed does not depend on having thought of every way of writing an attack.

### One thing to know when changing the converter

`dashboard_convert.php` is required from inside a class method. A variable
assigned at the top level of an included file takes the scope of whatever
included it, so the allowlists are functions rather than variables. As globals
they were silently empty when the converter ran from the model, and everything
still parsed. Keep them as functions.
