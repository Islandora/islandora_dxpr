<?php

declare(strict_types=1);

namespace Drupal\Tests\islandora_dxpr\Functional;

use Drupal\Core\Form\FormState;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Verifies that Islandora DXPR installs and renders as the default theme.
 */
#[Group('islandora_dxpr')]
#[RunTestsInSeparateProcesses]
final class ThemeSmokeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'dxpr_theme_helper',
    'islandora',
    'media_library_form_element',
  ];

  /**
   * DXPR Theme 8.1 does not provide a schema for dxpr_theme.settings.
   *
   * @var bool
   */
  // phpcs:ignore DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('theme_installer')->install(['islandora_dxpr']);
    $this->config('system.theme')
      ->set('default', 'islandora_dxpr')
      ->save();
  }

  /**
   * Tests theme installation and its global stylesheet attachment.
   */
  public function testThemeRenders(): void {
    $this->drupalGet('<front>');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame(
      'islandora_dxpr',
      $this->config('system.theme')->get('default'),
    );
    $this->assertTrue(
      $this->container->get('theme_handler')->themeExists('dxpr_theme'),
    );
    $this->assertSame(
      'themes/contrib/islandora_dxpr/favicon.png',
      $this->config('islandora_dxpr.settings')->get('favicon.path'),
    );
    $this->assertFalse(
      $this->config('islandora_dxpr.settings')->get('favicon.use_default'),
    );
    $this->assertSame(
      '0Ancizar+Serif:300',
      $this->config('islandora_dxpr.settings')->get('body_font_face'),
    );
    $this->assertSame(
      '#ffffff',
      $this->config('islandora_dxpr.settings')->get('color_palette_body'),
    );
    $this->assertSame(
      '#fafafa',
      $this->config('islandora_dxpr.settings')->get('color_palette_header'),
    );
    $this->assertSame(
      '#fafafa',
      $this->config('islandora_dxpr.settings')->get('color_palette_footer'),
    );
    $this->assertSame(
      0,
      $this->config('islandora_dxpr.settings')->get('page_title_home_hide'),
    );
    $this->assertSame(
      '96',
      $this->config('islandora_dxpr.settings')->get('page_title_height'),
    );

    $theme_path = $this->container->get('extension.list.theme')
      ->getPath('islandora_dxpr');
    $defaults_path = DRUPAL_ROOT . '/' . $theme_path .
      '/css/islandora_dxpr_defaults.css';
    $this->assertFileExists($defaults_path);
    $defaults_css = file_get_contents($defaults_path);
    $this->assertIsString($defaults_css);
    $this->assertMatchesRegularExpression(
      '/--dxt-color-body:\s*#(?:fff|ffffff)\b/i',
      $defaults_css,
    );
    $this->assertMatchesRegularExpression(
      '/--dxt-color-header:\s*#fafafa\b/i',
      $defaults_css,
    );
    $this->assertMatchesRegularExpression(
      '/--dxt-color-footer:\s*#fafafa\b/i',
      $defaults_css,
    );
    $this->assertMatchesRegularExpression(
      '/--dxt-setting-body-font-face:\s*["\']Ancizar Serif["\']/',
      $defaults_css,
    );

    $library = $this->container->get('library.discovery')
      ->getLibraryByName('islandora_dxpr', 'global-styling');
    $assets = [];
    foreach ($library['css'] as $asset) {
      $assets[$asset['data']] = $asset;
    }
    $defaults_asset = $theme_path . '/css/islandora_dxpr_defaults.css';
    $structural_asset = $theme_path . '/css/dxpr_theme_subtheme.css';
    $this->assertArrayHasKey($defaults_asset, $assets);
    $this->assertArrayHasKey($structural_asset, $assets);
    $this->assertLessThan(
      $assets[$structural_asset]['weight'],
      $assets[$defaults_asset]['weight'],
    );
    $javascript_asset = $theme_path . '/js/islandora_dxpr.js';
    $javascript_assets = array_column($library['js'], 'data');
    $this->assertContains($javascript_asset, $javascript_assets);
    $this->assertContains(
      'bootstrap5/bootstrap5-js-latest',
      $library['dependencies'],
    );
    $this->assertContains('core/once', $library['dependencies']);
    $structural_css = file_get_contents(
      DRUPAL_ROOT . '/' . $structural_asset,
    );
    $this->assertIsString($structural_css);
    $this->assertStringContainsString(
      '--islandora-search-control-size: 2.75rem',
      $structural_css,
    );
    $this->assertStringContainsString(
      'details.form-wrapper[open]',
      $structural_css,
    );
    $this->assertStringContainsString(
      '.islandora-advanced-search .islandora-advanced-search__condition',
      $structural_css,
    );
    $this->assertStringContainsString(
      '.islandora-advanced-search .islandora-advanced-search__conditions',
      $structural_css,
    );
    $this->assertStringContainsString(
      '.islandora-advanced-search .islandora-advanced-search__condition-actions',
      $structural_css,
    );
    $this->assertStringContainsString(
      '.islandora-advanced-search .islandora-advanced-search__condition--first',
      $structural_css,
    );
    $this->assertStringContainsString(
      'grid-template-areas: "field operator value term-actions"',
      $structural_css,
    );
    $this->assertStringContainsString(
      '.islandora-header-search--enhanced',
      $structural_css,
    );
    $this->assertStringContainsString(
      '.page-route-view-solr-search-content-page-1',
      $structural_css,
    );
    $javascript = file_get_contents(DRUPAL_ROOT . '/' . $javascript_asset);
    $this->assertIsString($javascript);
    $this->assertStringContainsString(
      'islandoraDxprHeaderSearch',
      $javascript,
    );

    $search_block = [
      'plugin_id' => 'search_block',
      'attributes' => [],
      'content' => [
        'search-textfield' => [
          '#type' => 'textfield',
          '#attributes' => ['placeholder' => ''],
        ],
        'actions' => [
          'submit' => [
            '#type' => 'submit',
            '#value' => 'Search',
          ],
        ],
      ],
    ];
    \islandora_dxpr_preprocess_block($search_block);
    $this->assertContains(
      'islandora-header-search',
      $search_block['content']['#attributes']['class'],
    );
    $this->assertContains(
      'islandora-header-search__input',
      $search_block['content']['search-textfield']['#attributes']['class'],
    );
    $this->assertSame(
      'Search the repository',
      (string) $search_block['content']['search-textfield']['#attributes']['placeholder'],
    );
    $this->assertContains(
      'islandora-header-search__actions',
      $search_block['content']['actions']['#attributes']['class'],
    );
    $this->assertContains(
      'islandora-header-search__submit',
      $search_block['content']['actions']['submit']['#attributes']['class'],
    );
    $search_submit_attributes =
      $search_block['content']['actions']['submit']['#attributes'];
    $this->assertSame(
      'Search the repository',
      (string) $search_submit_attributes['aria-label'],
    );
    $this->assertSame(
      'tooltip',
      $search_submit_attributes['data-bs-toggle'],
    );
    $this->assertArrayNotHasKey('title', $search_submit_attributes);
    $this->assertSame(
      ['input__submit__islandora_search'],
      $search_block['content']['actions']['submit']['#theme_wrappers'],
    );

    $syndicate_block = [
      'plugin_id' => 'node_syndicate_block',
      'attributes' => ['role' => 'complementary'],
      'content' => [],
    ];
    \islandora_dxpr_preprocess_block($syndicate_block);
    $this->assertArrayNotHasKey('role', $syndicate_block['attributes']);

    $search_submit = $search_block['content']['actions']['submit'];
    $search_submit_markup = (string) $this->container->get('renderer')
      ->renderRoot($search_submit);
    $this->assertStringContainsString('<button', $search_submit_markup);
    $this->assertStringContainsString(
      'class="islandora-search-icon"',
      $search_submit_markup,
    );
    $this->assertStringContainsString('btn-primary', $search_submit_markup);
    $this->assertStringContainsString(
      '<span class="visually-hidden">Search</span>',
      $search_submit_markup,
    );

    $pager_plugin_id =
      'advanced_search_result_pager:solr_search_content__page_1';
    $pager_block = [
      'plugin_id' => $pager_plugin_id,
      'attributes' => [],
      'content' => [
        'container' => [
          'sort_by' => [
            '#options' => [
              'search_api_relevance_asc' => 'Relevance ascending',
              'search_api_relevance_desc' => 'Relevance descending',
              'title_asc' => 'Title ascending',
            ],
            '#options_attributes' => [
              'search_api_relevance_asc' => ['data-sort-order' => 'ASC'],
              'search_api_relevance_desc' => ['data-sort-order' => 'DESC'],
            ],
          ],
        ],
      ],
    ];
    \islandora_dxpr_preprocess_block($pager_block);
    $sort = $pager_block['content']['container']['sort_by'];
    $sort_options = $sort['#options'];
    $this->assertArrayNotHasKey('search_api_relevance_asc', $sort_options);
    $this->assertArrayHasKey('search_api_relevance_desc', $sort_options);
    $this->assertSame(
      'Relevance',
      (string) $sort_options['search_api_relevance_desc'],
    );
    $this->assertArrayHasKey('title_asc', $sort_options);
    $sort_attributes = $sort['#options_attributes'];
    $this->assertArrayNotHasKey(
      'search_api_relevance_asc',
      $sort_attributes,
    );

    $advanced_search_form = [
      'ajax' => [
        'terms' => [
          0 => [
            'search' => ['#attributes' => []],
            'include' => ['#attributes' => []],
            'value' => ['#attributes' => []],
            'actions' => [
              '#attributes' => [],
              'add' => ['#attributes' => []],
            ],
          ],
          1 => [
            'conjunction' => ['#attributes' => []],
            'search' => ['#attributes' => []],
            'include' => ['#attributes' => []],
            'value' => ['#attributes' => []],
            'actions' => [
              '#attributes' => [],
              'add' => ['#attributes' => []],
              'remove' => ['#attributes' => []],
            ],
          ],
        ],
        '#attributes' => [],
      ],
      'reset' => ['#attributes' => []],
      'submit' => ['#attributes' => []],
      '#attributes' => [],
    ];
    $form_state = new FormState();
    \islandora_dxpr_form_advanced_search_form_alter(
      $advanced_search_form,
      $form_state,
      'advanced_search_form',
    );
    $first_condition = $advanced_search_form['ajax']['terms'][0];
    $this->assertSame('container', $first_condition['#type']);
    $this->assertContains(
      'islandora-advanced-search__condition--first',
      $first_condition['#attributes']['class'],
    );
    $this->assertSame('group', $first_condition['#attributes']['role']);
    $this->assertSame(
      'Search condition 1',
      (string) $first_condition['#attributes']['aria-label'],
    );
    $this->assertContains(
      'islandora-advanced-search__field',
      $first_condition['search']['#attributes']['class'],
    );
    $this->assertContains(
      'islandora-advanced-search__condition-actions',
      $first_condition['actions']['#attributes']['class'],
    );
    $this->assertSame(
      'Add another search condition',
      (string) $first_condition['actions']['add']['#attributes']['aria-label'],
    );
    $this->assertSame(
      'tooltip',
      $first_condition['actions']['add']['#attributes']['data-bs-toggle'],
    );
    $this->assertSame(
      'Add another search condition',
      (string) $first_condition['actions']['add']['#attributes']['data-bs-title'],
    );
    $this->assertArrayNotHasKey(
      'title',
      $first_condition['actions']['add']['#attributes'],
    );
    $this->assertSame(10, $first_condition['actions']['add']['#weight']);
    $second_condition = $advanced_search_form['ajax']['terms'][1];
    $this->assertSame(0, $second_condition['actions']['remove']['#weight']);
    $this->assertSame(10, $second_condition['actions']['add']['#weight']);
    $this->assertContains(
      'islandora-advanced-search__reset',
      $advanced_search_form['reset']['#attributes']['class'],
    );

    $fieldset_suggestions = ['fieldset'];
    $fieldset_variables = [
      'element' => [
        '#attributes' => [
          'data-drupal-selector' => 'edit-resource-type',
        ],
      ],
    ];
    \islandora_dxpr_theme_suggestions_fieldset_alter(
      $fieldset_suggestions,
      $fieldset_variables,
    );
    $this->assertContains(
      'fieldset__islandora_facet_resource_type',
      $fieldset_suggestions,
    );

    $resource_type_template = file_get_contents(
      DRUPAL_ROOT . '/' . $theme_path .
      '/templates/fieldset--islandora-facet-resource-type.html.twig',
    );
    $this->assertIsString($resource_type_template);
    $this->assertStringContainsString('<details', $resource_type_template);
    $this->assertStringNotContainsString(
      ".setAttribute('open', 'open')",
      $resource_type_template,
    );

    $page_template = file_get_contents(
      DRUPAL_ROOT . '/' . $theme_path . '/templates/page.html.twig',
    );
    $this->assertIsString($page_template);
    $this->assertStringContainsString('id="main-content"', $page_template);
    $this->assertStringContainsString('tabindex="-1"', $page_template);
    $this->assertStringContainsString(
      'data-islandora-sidebar="primary"',
      $page_template,
    );
    $this->assertStringContainsString('{{ page.content_top }}', $page_template);
    $main_position = strpos($page_template, 'id="main-content"');
    $content_top_position = strpos(
      $page_template,
      "{{ block('islandora_content_top') }}",
    );
    $page_title_position = strpos(
      $page_template,
      "{{ block('islandora_page_title') }}",
    );
    $this->assertIsInt($main_position);
    $this->assertIsInt($content_top_position);
    $this->assertIsInt($page_title_position);
    $this->assertGreaterThan($main_position, $page_title_position);
    $this->assertGreaterThan($main_position, $content_top_position);

    $menu_template = file_get_contents(
      DRUPAL_ROOT . '/' . $theme_path . '/templates/menu--main.html.twig',
    );
    $this->assertIsString($menu_template);
    $this->assertStringContainsString('aria-current="page"', $menu_template);
    $this->assertStringNotContainsString('role="menubar"', $menu_template);
    $this->assertStringNotContainsString('role="menuitem"', $menu_template);
    $this->assertStringContainsString('clean_unique_id', $menu_template);
    $this->assertStringContainsString("'id': item_menu_id", $menu_template);
    $this->assertStringContainsString(
      "'aria-labelledby': item_link_id",
      $menu_template,
    );
    $this->assertStringNotContainsString(
      '{% set attributes = create_attribute() %}',
      $menu_template,
    );

    $this->assertSession()->elementsCount('css', '[role="main"]', 1);
    $this->assertSession()->elementExists(
      'css',
      '#main-content.islandora-main-landmark[role="main"][tabindex="-1"]',
    );
    $this->assertSession()->elementExists(
      'css',
      'link[rel~="icon"][href*="islandora_dxpr/favicon.png"]',
    );

    $filename_alt = [
      'style_name' => 'islandora_card',
      'image' => [
        '#alt' => 'postcard4.tif.thumbnail.jpg',
        '#uri' => 'fedora://2026-07/postcard4.tif_.thumbnail.jpg',
        '#attributes' => ['alt' => 'postcard4.tif.thumbnail.jpg'],
      ],
    ];
    \islandora_dxpr_preprocess_image_style($filename_alt);
    $this->assertSame('', $filename_alt['image']['#alt']);

    $authored_alt = [
      'style_name' => 'islandora_card',
      'image' => [
        '#alt' => 'A parade passing the public library',
        '#uri' => 'fedora://2026-07/parade.tif_.thumbnail.jpg',
        '#attributes' => ['alt' => 'A parade passing the public library'],
      ],
    ];
    \islandora_dxpr_preprocess_image_style($authored_alt);
    $this->assertSame(
      'A parade passing the public library',
      $authored_alt['image']['#alt'],
    );
  }

}
