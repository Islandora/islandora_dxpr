# Islandora DXPR Theme

Islandora DXPR Theme is a contributed Drupal subtheme of
[DXPR Theme](https://www.drupal.org/project/dxpr_theme) for Islandora sites.

Development takes place in the
[Islandora DXPR GitHub repository](https://github.com/Islandora/islandora_dxpr).


## Requirements

- Drupal 10.3 or later in the 10.x series, or Drupal 11
- DXPR Theme 8.1 or later in the 8.x release series


## Installation

Install the theme with Composer:

```shell
composer require drupal/islandora_dxpr
```

Composer installs DXPR Theme as a dependency. Enable Islandora DXPR Theme and
set it as the default theme through the Appearance administration page, or use
Drush:

```shell
drush theme:enable islandora_dxpr
drush config:set system.theme default islandora_dxpr -y
```


## Configuration

Configure the theme at Appearance > Settings > Islandora DXPR Theme. Add custom
styles to `css/dxpr_theme_subtheme.css` and template overrides to `templates/`.

Islandora DXPR ships `css/islandora_dxpr_defaults.css` so its accessible color,
typography, spacing, and component defaults are available immediately after a
clean installation. DXPR subsequently generates
`public://dxpr_theme/css/themesettings-islandora_dxpr.css` from the saved theme
settings. That generated stylesheet loads after the packaged defaults and
overrides them, allowing site administrators to customize the theme without
editing its source files. Do not edit the generated public file directly; use
the theme settings form or an institutional subtheme instead.

The Islandora discovery section at Appearance > Settings > Islandora DXPR Theme
controls how search result page sizes are presented. The compact dropdown is
the default; administrators can switch to visible page-size links when that
interaction better fits their repository.

The default typography uses Inter for body text and controls and Sora for
headings, with system sans-serif fallbacks. Body text is 17px on desktop and
16px on mobile; captions and controls are at least 16px. DXPR manages font
loading through its existing font settings.

Breadcrumbs ship as a block in Content Top, alongside the standard DXPR block
placements. Administrators can move, disable, or set visibility for them in
Structure > Block layout. The page-title breadcrumb setting applies only when
the block is placed in Page Title.

The native breadcrumb builder supplies the trail; Member Of links remain
available in record metadata. Media and downloads precede these fields with a
compact gap. Configure metadata groups and their headings in the site's
Manage display configuration (for example, with Field Group); the theme does
not add a second heading around those groups.

Write descriptive alternative text on image media when cataloging content.
The theme preserves authored descriptions and intentionally empty alternatives.
On full record pages only, filename-only alternatives on media linked through
`field_media_of` use the accessible parent record's title as a fallback. A title
identifies the record; it does not replace an authored image description.
Enable Islandora's image formatter original-file alternative-text option in
the starter site's display configuration to reuse catalogers' descriptions for
derivatives, including search results.

Contact labels belong to site configuration. Rename the feedback contact form
to “Contact us” in the starter site and configure its recipient under Structure
> Contact forms. Before launch, check keyboard navigation, screen-reader output,
200% zoom, mobile browsing, and representative large collections on the deployed
site. Theme checks alone do not establish a VPAT or validate performance.

Run `./scripts/ci.sh` with Docker to execute the functional tests and Chromium
spacing regression test. The browser test renders image, audio, video, and
document-viewer fixtures through the real record template at desktop, tablet,
and phone widths. It measures the media-to-metadata gap (1–1.5rem), checks for
excess whitespace inside the viewer wrapper, and rejects horizontal overflow.
Viewer fixtures are local and do not require repository media services. Advisory
blocking and post-install audits are disabled only in the disposable test
container so the suite can use the dependencies supplied by each CI image.

For Facets 3 exposed-filter Views, use the contributed
[Views Exposed Filters Summary](https://www.drupal.org/project/views_filters_summary)
module and its accessibility companion for selected-value state, individual
removal, reset, and AJAX behavior. Islandora DXPR styles that module's output as
removable chips but does not reconstruct filter state in theme JavaScript. This
keeps search behavior in maintained Drupal modules and leaves the theme
responsible for presentation.


## License

Islandora DXPR Theme is licensed under the GPL-2.0-or-later license.

This project began with the
[DXPR Theme 8.1 starter kit](https://git.drupalcode.org/project/dxpr_theme/-/tree/8.x/dxpr_theme_STARTERKIT),
including its default theme assets and configuration.
