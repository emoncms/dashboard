# Dashboards: a change for anyone using a `<style>` block

We are changing how dashboards are stored. Until now a dashboard was kept as a
page of HTML. From this release it is kept as a description of the widgets on
it, and the page is built from that when you open it.

Almost everyone will see no difference. Your widgets keep their positions, their
sizes, their options and their text.

There is one group this does affect. If you pasted a `<style>` block into a text
or container widget, those rules are not kept, and anything they styled now
draws without that styling. A small number of dashboards do this. The most
common use is standing an axis label on its side.

## How to tell if this is you

Open your dashboard and look for text that has moved, is the wrong size, or is
no longer rotated. If you have labels reading along the side of a chart and they
are now lying flat and overflowing, this is the cause.

## Why the rules cannot be kept

A `<style>` block is not limited to the widget holding it. One rule reaches
every widget on the page, and on a public dashboard it reaches whatever anyone
else is looking at. The same applies to `id` and `class` on the elements inside
a widget, which are no longer kept either, so a selector would have nothing to
match.

## How to put the styling back

Write the rules directly on the element instead. Here is a rotated label as it
was written before, with the rule in a `<style>` block:

```html
<style>
  #container {
    width: 120px;
    height: 12px;
    font-size: 8pt;
    transform: rotate(90deg);
  }
</style>
<div id="container">Amb Temp</div>
```

And the same label written so it is kept:

```html
<div style="width: 120px; height: 12px; font-size: 8pt; transform: rotate(90deg);">Amb Temp</div>
```

Delete the `<style>` block once you have moved its rules. The `-webkit-`,
`-moz-` and `-o-` versions of `transform` can go with it. No current browser
needs them.

A box turns about its centre, which is what these rules were already doing, so
the label should land back where it was.

## What a style attribute may hold

Anything that only changes how a thing looks:

    colour and text    color font font-size font-family font-weight font-style
                       letter-spacing line-height text-align text-decoration
                       text-transform vertical-align white-space

    background
    and borders        background background-color border and its longhands
                       border-radius border-collapse border-spacing box-shadow
                       opacity

    size and spacing   padding margin width height max-width min-width
                       max-height min-height display visibility overflow float
                       table-layout align-items justify-content flex-wrap

    rotation           transform, written as a single rotate()

`transform` takes a rotation and nothing else. `translate`, `scale` and
`matrix` move a widget away from where the dashboard says it is, which is how
one widget ends up covering another, so they are not kept.

Two other limits are worth knowing if you are editing by hand. An opacity below
0.2 is raised to 0.2, because a widget you cannot see still takes clicks. A
negative margin is dropped, because it pulls content up out of the dashboard and
over the menu bar.

## If something else changed

Your original dashboard is still stored. Nothing has overwritten it, and we can
look at what it held. Post the dashboard name here, or open a thread with a
screenshot of what you expected and what you see now, and we will tell you what
happened to it.
