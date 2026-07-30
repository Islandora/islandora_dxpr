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
    $this->assertSession()->elementExists(
      'css',
      'link[href*="islandora_dxpr/css/dxpr_theme_subtheme.css"]',
    );
  }

}
