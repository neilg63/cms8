<?php

/**
 * @file
 * Lists available colors and color schemes for the Frontend theme.
 */

$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'bg' => t('Main background'),
    'text' => t('Text colour'),
    'highlight' => t('Highlighted items'),
    'caption_hover' => t('Slide caption hover background'),
    'link' => t('Link active colour'),
  ],
  // Pre-defined color schemes.
  'schemes' => [
    'default' => [
      'title' => t('Beige Canvas (default)'),
      'colors' => [
        'bg' => '#e2e1dd',
        'text' => '#2c3e50',
        'highlight' => '#ff6600',
        'caption_hover' => '#2c3e50',
        'link' => '#2c3e50',
      ],
    ],
    'slate' => [
      'title' => t('Slate'),
      'colors' => [
        'bg' => '#ffffff',
        'text' => '#3b3b3b',
        'highlight' => '#0073b6',
        'caption_hover' => '#2c3e50',
        'link' => '#0073b6',
      ],
    ],
  ],

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => [
    'css/colors.css',
  ],

  // Files to copy.
  'copy' => [
    'logo.svg',
  ],

  // Gradient definitions.
  'gradients' => [
    [
      // (x, y, width, height).
      'dimension' => [0, 0, 0, 0],
      // Direction of gradient ('vertical' or 'horizontal').
      'direction' => 'vertical',
      // Keys of colors to use for the gradient.
      'colors' => ['bg', 'text'],
    ],
  ],

  // Preview files.
  'preview_library' => 'frontend/color.preview',
  'preview_html' => 'color/preview.html',

  // Attachments.
  '#attached' => [
    'drupalSettings' => [
      'color' => [
        // Put the logo path into JavaScript for the live preview.
        'logo' => theme_get_setting('logo.url', 'frontend'),
      ],
    ],
  ],
];
