<?php

/**
 * @file
 * Theme settings for Islandora DXPR.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter() for system_theme_settings.
 */
function islandora_dxpr_form_system_theme_settings_alter(
  array &$form,
  FormStateInterface $form_state,
  $form_id = NULL,
): void {
  // DXPR builds the settings form twice and only supplies the form ID for the
  // complete pass. Match the base theme's guard so this section is added once.
  if (!isset($form_id)) {
    return;
  }

  $build_info = $form_state->getBuildInfo();
  $subject_theme = $build_info['args'][0] ?? 'islandora_dxpr';
  if (!is_string($subject_theme) || $subject_theme === '') {
    $subject_theme = 'islandora_dxpr';
  }
  $configured_value = islandora_dxpr_get_theme_setting(
    'search_results_per_page_control',
    $subject_theme,
  );

  $form['islandora_dxpr_discovery'] = [
    '#type' => 'details',
    '#title' => t('Islandora discovery'),
    '#description' => t(
      'Configure presentation controls used on repository search and collection result pages.',
    ),
    '#group' => 'dxpr_theme_settings',
  ];
  $form['islandora_dxpr_discovery']['search_results_per_page_control'] = [
    '#type' => 'select',
    '#title' => t('Results-per-page control'),
    '#options' => [
      'dropdown' => t('Dropdown'),
      'links' => t('Links'),
    ],
    '#default_value' => in_array($configured_value, ['dropdown', 'links'], TRUE)
      ? $configured_value
      : 'dropdown',
    '#description' => t(
      'Use a compact dropdown or display every available page-size option as a link.',
    ),
  ];
}
