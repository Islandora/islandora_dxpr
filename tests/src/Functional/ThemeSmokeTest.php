<?php

declare(strict_types=1);

namespace Drupal\Tests\islandora_dxpr\Functional;

use Drupal\block\Entity\Block;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
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
    'block',
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
      'dropdown',
      $this->config('islandora_dxpr.settings')
        ->get('search_results_per_page_control'),
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
    $expected_templates = [
      'block/block--system-breadcrumb-block.html.twig',
      'block/mirador.html.twig',
      'block/openseadragon-iiif-manifest-block.html.twig',
      'content/node--islandora-object--teaser.html.twig',
      'field/openseadragon-formatter.html.twig',
      'form/fieldset--islandora-facet-resource-type.html.twig',
      'form/input--submit--islandora-search.html.twig',
      'layout/page.html.twig',
      'navigation/facets-result-item.html.twig',
      'navigation/menu--main.html.twig',
      'views/views-bootstrap-grid--solr-search-content--block-1.html.twig',
      'views/views-bootstrap-grid--top-level-collections.html.twig',
      'views/views-view-fields--solr-search-content--block-1.html.twig',
      'views/views-view-fields--solr-search-content--page-1.html.twig',
      'views/views-view-fields--top-level-collections.html.twig',
    ];
    foreach ($expected_templates as $template) {
      $this->assertFileExists(
        DRUPAL_ROOT . '/' . $theme_path . '/templates/' . $template,
      );
    }
    $this->assertSame(
      [],
      glob(DRUPAL_ROOT . '/' . $theme_path . '/templates/*.html.twig') ?: [],
    );
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
    $this->assertStringContainsString(
      '.islandora-search-results-toolbar',
      $structural_css,
    );
    $this->assertStringContainsString(
      'grid-template-columns: minmax(12rem, 1fr) repeat(3, auto)',
      $structural_css,
    );
    $this->assertStringContainsString(
      '> :is(.pager, .pager-nav)',
      $structural_css,
    );
    $this->assertStringContainsString(
      '.pager__results--links',
      $structural_css,
    );
    $this->assertStringContainsString(
      '.pager__items-per-page-select',
      $structural_css,
    );
    $this->assertStringContainsString(
      '.pager__results--dropdown.is-enhanced',
      $structural_css,
    );
    $this->assertStringContainsString(
      '.facets-exposed-range-slider__slider.noUi-horizontal',
      $structural_css,
    );
    $this->assertStringContainsString(
      '[data-facets-exposed-range-slider-min]',
      $structural_css,
    );
    $this->assertStringContainsString(
      '[data-facets-exposed-range-slider-max]',
      $structural_css,
    );
    $this->assertStringContainsString(
      '.noUi-handle:focus-visible',
      $structural_css,
    );
    $this->assertStringContainsString(
      '.facets-exposed-range-slider__slider--no-range',
      $structural_css,
    );
    $this->assertStringContainsString(
      '.view-solr-search-content .views-filters-summary',
      $structural_css,
    );
    $this->assertStringContainsString(
      ':is(a.remove-filter, strong.value)',
      $structural_css,
    );
    $this->assertStringContainsString(
      '.views-filters-summary a.reset',
      $structural_css,
    );
    $this->assertStringContainsString(
      '.views-filters-summary:not(:has(.value-container))',
      $structural_css,
    );
    $this->assertStringNotContainsString(
      '.islandora-selected-filters',
      $structural_css,
    );
    $javascript = file_get_contents(DRUPAL_ROOT . '/' . $javascript_asset);
    $this->assertIsString($javascript);
    $this->assertStringContainsString(
      'islandoraDxprHeaderSearch',
      $javascript,
    );
    $this->assertStringContainsString(
      'islandoraDxprResultsPerPage',
      $javascript,
    );
    $this->assertStringContainsString(
      '[data-islandora-results-per-page]',
      $javascript,
    );
    $this->assertStringContainsString(
      'select.dataset.islandoraResultsPerPageUrls',
      $javascript,
    );
    $this->assertStringContainsString(
      'new URL(destination, window.location.origin)',
      $javascript,
    );
    $this->assertStringContainsString(
      "control.classList.add('is-enhanced')",
      $javascript,
    );
    $this->assertStringNotContainsString(
      'islandoraDxprSelectedFilters',
      $javascript,
    );
    $this->assertStringNotContainsString('selectedCount', $javascript);
    $this->assertStringNotContainsString(
      'input[type="checkbox"]:checked',
      $javascript,
    );
    $this->assertStringNotContainsString(
      'Show filters (@count selected)',
      $javascript,
    );

    require_once DRUPAL_ROOT . '/' . $theme_path . '/theme-settings.php';
    $settings_form = [];
    $settings_form_state = (new FormState())->setBuildInfo([
      'args' => ['islandora_dxpr'],
    ]);
    \islandora_dxpr_form_system_theme_settings_alter(
      $settings_form,
      $settings_form_state,
      'system_theme_settings',
    );
    $page_size_setting =
      $settings_form['islandora_dxpr_discovery']['search_results_per_page_control'];
    $this->assertSame('select', $page_size_setting['#type']);
    $this->assertSame(
      ['dropdown' => 'Dropdown', 'links' => 'Links'],
      array_map('strval', $page_size_setting['#options']),
    );
    $this->assertSame('dropdown', $page_size_setting['#default_value']);

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

    $pager_block = $this->buildSearchPagerBlock();
    \islandora_dxpr_preprocess_block($pager_block);
    $this->assertArrayNotHasKey('result_summary', $pager_block['content']);
    $toolbar = $pager_block['content']['container'];
    $this->assertStringContainsString(
      'islandora-search-results-toolbar',
      $toolbar['#prefix'],
    );
    $toolbar_order = Element::children($toolbar, TRUE);
    $this->assertSame(
      [
        'result_summary',
        'sort_by',
        'results_per_page_links',
        'display_links',
        'pager',
      ],
      array_slice($toolbar_order, 0, 5),
    );
    $this->assertStringContainsString(
      'role="status"',
      $toolbar['result_summary']['#prefix'],
    );
    $this->assertStringContainsString(
      'aria-live="polite"',
      $toolbar['result_summary']['#prefix'],
    );
    $this->assertContains(
      'config:islandora_dxpr.settings',
      $pager_block['content']['#cache']['tags'],
    );

    $page_size_dropdown = $toolbar['results_per_page_links'];
    $this->assertSame('container', $page_size_dropdown['#type']);
    $this->assertContains(
      'pager__results--dropdown',
      $page_size_dropdown['#attributes']['class'],
    );
    $page_size_control = $page_size_dropdown['select'];
    $this->assertSame('select', $page_size_control['#type']);
    $this->assertSame('items_per_page', $page_size_control['#name']);
    $this->assertSame('invisible', $page_size_control['#title_display']);
    $this->assertSame('', array_key_first($page_size_control['#options']));
    $this->assertSame(
      'Results per page (15)',
      (string) $page_size_control['#options'][''],
    );
    $this->assertSame(
      ['', '60', '120'],
      array_map('strval', array_keys($page_size_control['#options'])),
    );
    $this->assertSame(
      'Results per page (60)',
      (string) $page_size_control['#options'][60],
    );
    $this->assertSame('', $page_size_control['#value']);
    $this->assertTrue($page_size_control['#attributes']['disabled']);
    $this->assertSame(
      'true',
      $page_size_control['#attributes']['data-islandora-results-per-page'],
    );
    $page_size_urls = json_decode(
      $page_size_control['#attributes']['data-islandora-results-per-page-urls'],
      TRUE,
      flags: JSON_THROW_ON_ERROR,
    );
    $this->assertSame(
      ['15', '60', '120'],
      array_map('strval', array_keys($page_size_urls)),
    );
    $this->assertStringContainsString(
      'items_per_page=60',
      $page_size_urls['60'],
    );
    $this->assertContains(
      'pager__results--links',
      $page_size_dropdown['fallback']['#wrapper_attributes']['class'],
    );
    $renderable_page_size_control = $page_size_dropdown;
    $page_size_markup = (string) $this->container->get('renderer')
      ->renderRoot($renderable_page_size_control);
    $this->assertMatchesRegularExpression(
      '/<option value=""\s+selected="selected">Results per page \(15\)<\/option>/',
      $page_size_markup,
    );
    $this->assertStringContainsString(
      '>Results per page (60)</option>',
      $page_size_markup,
    );
    $this->assertStringNotContainsString('<noscript>', $page_size_markup);
    $this->assertStringContainsString(
      'pager__results--fallback',
      $page_size_markup,
    );
    $this->assertStringContainsString('items_per_page=120', $page_size_markup);

    $current_sixty_block = $this->buildSearchPagerBlock();
    $current_sixty_links =
      $current_sixty_block['content']['container']['results_per_page_links'];
    unset($current_sixty_links['#items'][0]['#attributes']['aria-current']);
    $current_sixty_links['#items'][1]['#attributes']['class'][] =
      'pager__link--is-active';
    $current_sixty_control =
      \islandora_dxpr_build_results_per_page_select($current_sixty_links);
    $this->assertSame(
      'Results per page (60)',
      (string) $current_sixty_control['select']['#options'][''],
    );
    $this->assertSame(
      ['', '15', '120'],
      array_map(
        'strval',
        array_keys($current_sixty_control['select']['#options']),
      ),
    );

    $sort = $toolbar['sort_by'];
    $sort_options = $sort['#options'];
    $this->assertArrayNotHasKey('search_api_relevance_asc', $sort_options);
    $this->assertArrayHasKey('search_api_relevance_desc', $sort_options);
    $this->assertSame(
      'Sort by Relevance',
      (string) $sort_options['search_api_relevance_desc'],
    );
    $this->assertArrayHasKey('title_asc', $sort_options);
    $this->assertSame(
      'Sort by Title (A–Z)',
      (string) $sort_options['title_asc'],
    );
    $this->assertSame(
      'Sort by Items in collection',
      (string) $sort_options['collection_count'],
    );
    foreach ($sort_options as $sort_label) {
      $this->assertStringStartsWith('Sort by ', (string) $sort_label);
    }
    $sort_attributes = $sort['#options_attributes'];
    $this->assertArrayNotHasKey(
      'search_api_relevance_asc',
      $sort_attributes,
    );

    $this->config('islandora_dxpr.settings')
      ->set('search_results_per_page_control', 'links')
      ->save();
    // Drupal 10 uses theme_get_setting(), which caches for the request.
    \drupal_static_reset('theme_get_setting');
    $links_pager_block = $this->buildSearchPagerBlock();
    \islandora_dxpr_preprocess_block($links_pager_block);
    $links_control =
      $links_pager_block['content']['container']['results_per_page_links'];
    $this->assertSame('item_list', $links_control['#theme']);
    $this->assertSame('Results per page', (string) $links_control['#title']);
    $this->assertContains(
      'pager__results--links',
      $links_control['#wrapper_attributes']['class'],
    );
    $this->assertNotContains(
      'container',
      $links_control['#wrapper_attributes']['class'],
    );
    $this->assertArrayNotHasKey('#type', $links_control);
    $renderable_links_control = $links_control;
    $links_markup = (string) $this->container->get('renderer')
      ->renderRoot($renderable_links_control);
    $this->assertStringContainsString(
      '<h3>Results per page</h3>',
      $links_markup,
    );
    $this->assertStringContainsString('<ul>', $links_markup);
    $this->config('islandora_dxpr.settings')
      ->set('search_results_per_page_control', 'dropdown')
      ->save();
    \drupal_static_reset('theme_get_setting');

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
      '/templates/form/fieldset--islandora-facet-resource-type.html.twig',
    );
    $this->assertIsString($resource_type_template);
    $this->assertStringContainsString('<details', $resource_type_template);
    $this->assertStringNotContainsString(
      ".setAttribute('open', 'open')",
      $resource_type_template,
    );

    $page_template = file_get_contents(
      DRUPAL_ROOT . '/' . $theme_path . '/templates/layout/page.html.twig',
    );
    $this->assertIsString($page_template);
    $this->assertStringContainsString('id="main-content"', $page_template);
    $this->assertStringContainsString('tabindex="-1"', $page_template);
    $this->assertStringContainsString(
      'data-islandora-sidebar="primary"',
      $page_template,
    );
    $this->assertStringContainsString('{{ page.content_top }}', $page_template);
    $this->assertStringContainsString(
      "set content_top_content = block('islandora_content_top')",
      $page_template,
    );
    $this->assertStringContainsString(
      'if _self.hasContent(content_top_content)',
      $page_template,
    );
    $this->assertStringContainsString(
      'if _self.hasContent(page_title_content)',
      $page_template,
    );
    $main_position = strpos($page_template, 'id="main-content"');
    $content_top_position = strpos(
      $page_template,
      "set content_top_content = block('islandora_content_top')",
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
      DRUPAL_ROOT . '/' . $theme_path .
      '/templates/navigation/menu--main.html.twig',
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

    Block::create([
      'id' => 'islandora_dxpr_test_empty_breadcrumbs',
      'status' => TRUE,
      'theme' => 'islandora_dxpr',
      'region' => 'content_top',
      'plugin' => 'system_breadcrumb_block',
      'settings' => [
        'id' => 'system_breadcrumb_block',
        'label' => 'Breadcrumbs',
        'label_display' => '0',
        'provider' => 'system',
      ],
      'visibility' => [],
      'weight' => 0,
    ])->save();
    $this->drupalGet('<front>');
    $this->assertSession()->elementNotExists(
      'css',
      '.islandora-content-top',
    );
    $this->assertSession()->elementNotExists(
      'css',
      '#page-title-full-width-container',
    );

    Block::create([
      'id' => 'islandora_dxpr_test_content_top',
      'status' => TRUE,
      'theme' => 'islandora_dxpr',
      'region' => 'content_top',
      'plugin' => 'system_powered_by_block',
      'settings' => [
        'id' => 'system_powered_by_block',
        'label' => 'Powered by Drupal',
        'label_display' => '0',
        'provider' => 'system',
      ],
      'visibility' => [],
      'weight' => 10,
    ])->save();
    $this->drupalGet('<front>');
    $this->assertSession()->elementExists(
      'css',
      '.islandora-content-top',
    );
    $this->assertSession()->elementExists(
      'css',
      '.islandora-content-top .block-system-powered-by-block',
    );
    $this->assertSession()->pageTextContains('Powered by Drupal');

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

  /**
   * Builds a representative Advanced Search pager block render array.
   */
  private function buildSearchPagerBlock(): array {
    $relevance_ascending = new TranslatableMarkup('Relevance ascending');
    $relevance_descending = new TranslatableMarkup('Relevance descending');
    $title_ascending = new TranslatableMarkup('Title ascending');
    $collection_count = new TranslatableMarkup('Items in collection');

    return [
      'plugin_id' => 'advanced_search_result_pager:solr_search_content__page_1',
      'attributes' => [],
      'content' => [
        'result_summary' => [
          '#prefix' => '<div class="pager__summary">',
          '#suffix' => '</div>',
          '#markup' => 'Displaying 1 - 15 of 97',
        ],
        'container' => [
          '#prefix' => '<div class="pager__group">',
          '#suffix' => '</div>',
          'results_per_page_links' => [
            '#theme' => 'item_list',
            '#title' => 'Results per page',
            '#items' => [
              [
                '#type' => 'link',
                '#title' => '15',
                '#url' => Url::fromUri('internal:/search', [
                  'query' => ['items_per_page' => 15, 'page' => 0],
                ]),
                '#attributes' => [
                  'itemsperpage' => '15',
                  'aria-current' => 'true',
                ],
              ],
              [
                '#type' => 'link',
                '#title' => '60',
                '#url' => Url::fromUri('internal:/search', [
                  'query' => ['items_per_page' => 60, 'page' => 0],
                ]),
                '#attributes' => ['itemsperpage' => '60'],
              ],
              [
                '#type' => 'link',
                '#title' => '120',
                '#url' => Url::fromUri('internal:/search', [
                  'query' => ['items_per_page' => 120, 'page' => 0],
                ]),
                '#attributes' => ['itemsperpage' => '120'],
              ],
            ],
            '#wrapper_attributes' => [
              'class' => ['pager__results', 'container'],
            ],
          ],
          'display_links' => [
            '#theme' => 'item_list',
            '#items' => [],
            '#wrapper_attributes' => [
              'class' => ['pager__display', 'container'],
            ],
          ],
          'sort_by' => [
            '#type' => 'select',
            '#title' => 'Sort',
            '#options' => [
              'search_api_relevance_asc' => $relevance_ascending,
              'search_api_relevance_desc' => $relevance_descending,
              'title_asc' => $title_ascending,
              'collection_count' => $collection_count,
            ],
            '#options_attributes' => [
              'search_api_relevance_asc' => ['data-sort-order' => 'ASC'],
              'search_api_relevance_desc' => ['data-sort-order' => 'DESC'],
            ],
            '#wrapper_attributes' => [
              'class' => ['pager__sort', 'container'],
            ],
          ],
          'pager' => ['#markup' => ''],
        ],
      ],
    ];
  }

}
