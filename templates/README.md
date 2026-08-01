Islandora DXPR template overrides
==================================

`page.html.twig` preserves DXPR's layout settings while placing primary media
inside the main landmark. Institutional subthemes should override
`islandora_content_top`, `content`, or `help`; the upstream `content_top` block
is intentionally empty because DXPR otherwise renders it before `<main>`.

`menu--main.html.twig` retains DXPR's dropdown class contract but uses native
list and button semantics, plus `aria-current`, instead of claiming the ARIA
menubar interaction model. Compare both templates when upgrading DXPR.

`input--submit--islandora-search.html.twig` turns the compact repository-search
submit control into an icon button while preserving its translated label for
assistive technology.

`fieldset--islandora-facet-resource-type.html.twig` presents the primary
Resource type facet with the same disclosure pattern as the other Better
Exposed Filters facets. It starts expanded so common formats remain visible,
but visitors can collapse it when they need more room.

The View-specific templates are coupled to the stable `islandora-*` classes in
`css/dxpr_theme_subtheme.css` and to the shipped Starter Site View displays.
