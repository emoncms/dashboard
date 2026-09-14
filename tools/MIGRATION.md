# Migrating dashboard content to JSON

A deployment runbook for emoncms.org. For what the stored document looks like
and why, read `SCHEMA.md` first.

## What changes

Dashboard content stops being stored as HTML. It is stored as a JSON document
describing the widgets, and the page is built from that on the server.

A dashboard still holding HTML is converted the first time it is loaded. The
`content` column is never written again, so it keeps what was there before the
conversion. The new `content_json` column holds the document.

Saving goes through the same converter, so the server rather than the browser
decides what a dashboard may hold. AntiXSS is removed.

## The three branches

The work is split so that the inert part can be deployed and measured before
anything changes for users.

| repository | branch | what it does |
| --- | --- | --- |
| Modules/dashboard | `dashboard-content-json` | registry, converter, renderer, tools, the column. Nothing reads it. |
| emoncms (core) | `dashboard-content-json` | the vis widget declarations |
| Modules/graph | `dashboard-content-json` | the graph widget declaration |
| Modules/dashboard | `dashboard-json-switchover` | reads and writes the new column, removes AntiXSS |

`dashboard-json-switchover` is branched from `dashboard-content-json`, so
merging it brings both.

The three `dashboard-content-json` branches must land together. If the vis or
graph declarations are missing, every vis and graph widget is treated as an
unknown type, and each iframe those widgets draw reads as one an author added
by hand. The tools warn when they find that state rather than reporting the
false results, and `migrate.php` refuses to run at all.

`gate-action-widgets` is separate work and is not part of this.

## Requirements

No new ones. The converter needs `ext-mbstring`, which `composer.json` already
requires and `dashboard_model.php` already used. Node is only needed to
regenerate the widget declarations, and the generated files are committed, so
the server does not need it.

## Before you start

Take a database backup, and keep it until step 8 is done.

Note the current commit of each of the three repositories, so a rollback is a
checkout rather than a decision.

Read `SCHEMA.md` under "Cases decided against keeping". Four things are dropped
on purpose: widgets nested inside text boxes, hand added iframes, author
`<style>` blocks, and the styles broken by the old `htmlspecialchars_decode`.
That is a visible change for the dashboards holding them, and step 4 lists which
ones those are.

## Step by step

### 1. Deploy the inert part

Merge the three `dashboard-content-json` branches. Nothing reads the new
column yet, and no page behaves differently.

### 2. Add the column

    php scripts/emoncms-cli admin:dbupdate

Or the admin database page. `content_json` is `mediumtext`, added to the
`dashboard` table. Existing rows get NULL and nothing else changes.

This does not happen on its own. The automatic setup in `index.php` only runs
against an empty database.

### 3. Measure

Both of these read the database and change nothing.

    php Modules/dashboard/tools/convert.php
    php Modules/dashboard/tools/roundtrip.php

`convert.php` sorts dashboards into clean, warned and failed. Clean means
nothing an author wrote was dropped.

`roundtrip.php` converts each dashboard, renders it back and compares the
result against what was there before. It is the one that decides whether a
dashboard can be migrated: it says the page draws with the same widgets, in
the same places, with the same options and the same text. Identical and as
intended are both safe. Faults are not.

Expect the warning counts to be dominated by things that are meant to go:
generated canvas and iframe markup, `units_dropdown` designer artefacts,
browser extension attributes and the broken style fragments. The census of
5996 dashboards found about 18000 attributes in that category.

Three difference kinds are worth reading off `roundtrip.php` by name, because
they are the content rules rather than the markup and no census count predicts
them: `url_dropped`, `option_value_rejected` and `negative_top_clamped`. Step 4
lists the dashboards behind each.

### 4. Look at what will change

    php Modules/dashboard/tools/find.php nested iframe script --full

Lists the dashboards holding the dropped cases, with their ids and userids, so
their owners can be told or the dashboards looked at first. The census found 53
hand added iframes, a few hundred nested widgets and 21 dashboards with an
author `<style>` block.

The stylesheets are the ones to tell first. A dashboard loses every rule in the
block, and what it styled stays on the page unstyled, which on the dashboards
using them for rotated labels is the change an owner sees straight away. The
effect has to be written again as inline style, and `transform` is on the style
list for a single `rotate()` so that it can be.

`FORUM-POST-STYLESHEETS.md` is written for those owners. It says what changed,
how to tell whether their dashboard is one of them, and how to write the rules
again so they are kept.

Then the content rules, which the census cannot predict. It recorded attribute
names and counts, never values, so nothing in it says how many authors have a
tag in an option, a cache busting query on an image, or an opacity of zero:

    php Modules/dashboard/tools/find.php url option-value style-value --full

| category | what it finds |
| --- | --- |
| `url` | a `src` or `href` the allowlist drops, which since the same site rule went in includes an image or a link pointing back at emoncms. A same site `src` has to name a static image file with no query string. |
| `option-value` | an option value the widget will not accept as written: a tag in free text, or over 512 characters. A tag keeps its words and loses its formatting, so `P<sub>L1</sub>:` draws as `PL1:`. Anything else here is dropped, and long `curl` payloads are the ones to look at. |
| `style-value` | a negative margin, or an opacity below the 0.2 floor. |

These are changes to how a dashboard draws, not faults. `roundtrip.php` counts
them as intended, so they do not stop the migration, but the authors are worth
telling. A dropped image is the visible one.

For anything `roundtrip.php` called a fault:

    php Modules/dashboard/tools/roundtrip.php --show=option_lost
    php Modules/dashboard/tools/roundtrip.php --id=1861
    php Modules/dashboard/tools/convert.php --id=1861

Do not go past this step while there are faults you have not understood. A
fault means the dashboard does not draw the same afterwards.

### 5. Convert ahead of time

Optional. Without it the switch over converts each dashboard as it is opened,
which is fine for a small install. On emoncms.org it is worth doing the whole
table in one go, so the first person to open a dashboard is not the first
person to convert it.

    php Modules/dashboard/tools/roundtrip.php --faulty-ids > /var/tmp/faulty.txt
    php Modules/dashboard/tools/migrate.php --write --skip-ids=/var/tmp/faulty.txt

`migrate.php` reports and changes nothing without `--write`. It only ever
writes `content_json`, it skips dashboards that already hold a document, and
it leaves the `content` column alone.

Dashboards left out by `--skip-ids` are converted when they are next opened,
the same as any other. Leaving them out of this step only means they are not
converted in bulk while their faults are still being looked at.

Run it again after the last step to confirm it has nothing left to do.

### 6. Deploy the switch over

Merge `dashboard-json-switchover` in the dashboard repository.

From here dashboards are drawn from `content_json`, saves go through the
converter, and `content` is frozen.

### 7. Check

Open a dashboard of each kind: one with vis or graph widgets, one with a
text or container widget holding hand written HTML, one public dashboard
while logged out, and one of the dashboards `find.php` listed in step 4.

Open a dashboard in the editor, move a widget, save, and reload. If the save
dropped anything the save button turns amber and its tooltip says what.

Watch the emoncms log. The conversion writes an info line per dashboard, and
anything the renderer refuses is a warning.

    grep dashboard /var/log/emoncms/emoncms.log | tail -50

### 8. Later

`content` can be dropped once every dashboard has been converted and you are
no longer going to want the original HTML. Not in the same release.

    SELECT COUNT(*) FROM dashboard
    WHERE TRIM(COALESCE(content,'')) <> '' AND COALESCE(content_json,'') = '';

Running `migrate.php --write` with no `--skip-ids` brings this down to the
dashboards whose content holds no widget at all, which never get a document
because there is nothing to write. `migrate.php` counts those separately as
"no widgets" and lists their ids. They are mostly rows holding a stray line of
text. Check what they are with `show.php NNN` before dropping the column,
because for those rows it is the only copy.

## Rolling back

Check out the previous commit of the dashboard repository and run nothing
else. `content` still holds the original HTML, so dashboards go back to what
they were.

The catch is edits. Anything anyone saved after step 6 lives only in
`content_json`, and a rollback loses it. The window is small at first and
grows, so roll back early or not at all. After a few days, prefer fixing
forward.

Rolling back does not need the column removed. An unused column costs nothing
and leaves the option of trying again.

## If something goes wrong

**A dashboard is blank.** The document held no widgets. The original HTML is
still in `content`, so look at it:

    php Modules/dashboard/tools/show.php NNN
    php Modules/dashboard/tools/convert.php --id=NNN

**A dashboard lost something.** Compare the two:

    php Modules/dashboard/tools/roundtrip.php --id=NNN

Clearing `content_json` for that row makes the next load convert it again,
which is how a dashboard picks up a fix to the converter:

    UPDATE dashboard SET content_json = NULL WHERE id = NNN;

**Saving reports that the table has no content_json column.** Step 2 was
missed. Dashboards still draw, converting on each load, and saving starts
working as soon as the column is added.

**A widget draws as an unknown placeholder.** Its declaration is not
deployed. Check with:

    php Modules/dashboard/tools/registry.php

and confirm the vis and graph branches from step 1 are in.
