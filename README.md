# Islandora DXPR Theme

Islandora DXPR Theme is a contributed Drupal subtheme of
[DXPR Theme](https://www.drupal.org/project/dxpr_theme) for Islandora sites.

Development takes place in the
[Islandora DXPR GitHub repository](https://github.com/Islandora/islandora_dxpr).


## Requirements

- Drupal 11
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


## License

Islandora DXPR Theme is licensed under the GPL-2.0-or-later license.

This project began with the
[DXPR Theme 8.1 starter kit](https://git.drupalcode.org/project/dxpr_theme/-/tree/8.x/dxpr_theme_STARTERKIT),
including its default theme assets and configuration.
