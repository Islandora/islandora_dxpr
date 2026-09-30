<?php

declare(strict_types=1);

namespace Drupal\Tests\islandora_dxpr\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\SkippedTest;

/**
 * Measures record spacing with the real theme styles and browser layout engine.
 */
#[Group('islandora_dxpr')]
#[RunTestsInSeparateProcesses]
final class RecordSpacingTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'dxpr_theme_helper',
    'islandora',
    'media_library_form_element',
    'islandora_dxpr_spacing_test',
  ];

  /**
   * DXPR does not provide a complete settings schema.
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
   * Fails instead of silently skipping layout coverage when WebDriver is down.
   */
  protected function initMink() {
    try {
      return parent::initMink();
    }
    catch (SkippedTest $exception) {
      $this->fail($exception->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('theme_installer')->install(['islandora_dxpr']);
    $this->config('system.theme')->set('default', 'islandora_dxpr')->save();
  }

  /**
   * Keeps every media type close to metadata without overlap or overflow.
   */
  public function testMediaMetadataSpacing(): void {
    $this->drupalCreateContentType(['type' => 'islandora_object']);
    foreach (['img', 'audio', 'video', 'iframe'] as $type) {
      $node = $this->drupalCreateNode([
        'type' => 'islandora_object',
        'title' => $type,
        'body' => 'A description of the repository item.',
      ]);
      foreach ([1366, 768, 400] as $width) {
        $this->getSession()->resizeWindow($width, 900);
        $this->drupalGet($node->toUrl());
        $this->assertSame(
          $width,
          (int) $this->getSession()->evaluateScript('window.innerWidth'),
          'The requested viewport width is used for layout measurements.',
        );
        $this->assertSession()->elementExists('css', '#spacing-test-viewer');
        $this->assertSession()->elementNotExists(
          'css', '.islandora-metadata-group > h2',
        );
        $this->assertJsCondition('document.fonts.status === "loaded"');
        $this->assertJsCondition(
          'Array.from(document.images).every(image => image.complete)',
        );
        $layout = $this->getSession()->evaluateScript(<<<'JS'
          (() => {
            const media = document.querySelector('#spacing-test-media').getBoundingClientRect();
            const viewer = document.querySelector('#spacing-test-viewer').getBoundingClientRect();
            const metadata = document.querySelector('.islandora-metadata-group').getBoundingClientRect();
            const main = document.querySelector('.main-container').getBoundingClientRect();
            const rem = parseFloat(getComputedStyle(document.documentElement).fontSize);
            return {
              gap: (metadata.top - media.bottom) / rem,
              visibleGap: (metadata.top - viewer.bottom) / rem,
              leadingGap: (media.top - main.top) / rem,
              viewerHeight: viewer.height,
              overflow: document.documentElement.scrollWidth > window.innerWidth,
            };
          })()
          JS);
        $message = "$type viewer at {$width}px: " . json_encode($layout);
        $this->assertGreaterThan(0, $layout['viewerHeight'], $message);
        // Allow rounding, but reject collapsed gaps and large spacers.
        $this->assertGreaterThanOrEqual(1, $layout['gap'], $message);
        $this->assertLessThanOrEqual(1.5, $layout['gap'], $message);
        // Include whitespace inside the wrapper, notably native audio margins.
        $this->assertLessThanOrEqual(3, $layout['visibleGap'], $message);
        $this->assertGreaterThanOrEqual(0, $layout['leadingGap'], $message);
        $this->assertLessThanOrEqual(2, $layout['leadingGap'], $message);
        $this->assertFalse($layout['overflow'], $message);
      }
    }
  }

}
