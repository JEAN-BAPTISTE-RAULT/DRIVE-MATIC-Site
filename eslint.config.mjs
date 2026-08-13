import js from '@eslint/js';
import globals from 'globals';

// Flat config ESLint 9. Cible : JavaScript vanilla cote theme/module Drupal.
// Les comportements Drupal s'ecrivent en IIFE (sourceType "script"), pas en modules ES.
export default [
  {
    // Code tiers / genere : jamais linte.
    ignores: [
      'node_modules/**',
      'vendor/**',
      'dist/**',
      'web/core/**',
      'web/libraries/**',
      'web/modules/contrib/**',
      'web/themes/contrib/**',
      'web/sites/**',
      '**/*.min.js',
    ],
  },

  js.configs.recommended,

  {
    files: ['**/*.js'],
    languageOptions: {
      ecmaVersion: 2023,
      sourceType: 'script',
      globals: {
        ...globals.browser,
        // Globals fournis par Drupal core au runtime (cross-fichiers).
        // A completer si le projet en introduit d'autres (CKEDITOR, Backbone, _, Cookies...).
        Drupal: 'readonly',
        drupalSettings: 'readonly',
        once: 'readonly',
        jQuery: 'readonly',
        $: 'readonly',
        // Librairie slideshow vendorisee (web/themes/custom/drive_matic/vendor/swiper).
        Swiper: 'readonly',
      },
    },
    rules: {
      // Warnings : signales sans casser la pipeline (utile en cours d'implementation).
      'no-unused-vars': 'warn',
      'no-console': ['warn', { allow: ['warn', 'error'] }],
    },
  },
];
