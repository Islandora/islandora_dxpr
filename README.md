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
