Islandora DXPR template overrides
==================================

`page.html.twig` preserves DXPR's layout settings while placing primary media
and the page-title region inside the main landmark. Institutional subthemes
should override `islandora_content_top`, `content`, or `help`; the upstream
`content_top` block is intentionally empty because DXPR otherwise renders it
before `<main>`.

`menu--main.html.twig` retains DXPR's dropdown class contract but uses native
list and button semantics, plus `aria-current`, instead of claiming the ARIA
menubar interaction model. Compare both templates when upgrading DXPR.

`input--submit--islandora-search.html.twig` turns the compact repository-search
submit control into an icon button while preserving its translated label for
assistive technology.

`fieldset--islandora-facet-resource-type.html.twig` presents the primary
Resource type facet with the same disclosure pattern as the other Better
Exposed Filters facets. The Starter Site config starts it collapsed, while BEF
can open it when needed to reveal an active value.

`views-view-fields--solr-search-content--page-1.html.twig` gives search results
a stable research-oriented scan order: type and date, title, creator or
publisher, collection context, description, matching text, and thumbnail.

`views-view-fields--top-level-collections.html.twig` adds a concise description
to each visual collection card. `node--islandora-object--teaser.html.twig`
provides a compact object summary for linked taxonomy and other browse pages.

The View-specific templates are coupled to the stable `islandora-*` classes in
`css/dxpr_theme_subtheme.css` and to the shipped Starter Site View displays.
