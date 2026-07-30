<?php

declare(strict_types=1);

namespace Drupal\Tests\islandora_dxpr\Functional;

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
      '#fafafa',
      $this->config('islandora_dxpr.settings')->get('color_palette_header'),
    );
    $this->assertSame(
      '#fafafa',
      $this->config('islandora_dxpr.settings')->get('color_palette_footer'),
    );
    $this->assertSession()->elementExists(
      'css',
      'link[rel~="icon"][href*="islandora_dxpr/favicon.png"]',
    );
    $this->assertSession()->elementExists(
      'css',
      'link[href*="islandora_dxpr/css/dxpr_theme_subtheme.css"]',
    );
    $this->assertSession()->elementExists(
      'css',
      '#main-content.islandora-main-content-target[tabindex="-1"]',
    );
  }

}
