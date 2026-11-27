# Plugin Manifest v2

LabPress 1.3.0 reads `plugins/{slug}/plugin.json` before falling back to the legacy `plugin.php` header. The JSON Schema is [plugin.schema.json](plugin.schema.json).

## Minimal manifest

```json
{
  "slug": "footer-info",
  "name": "Footer Info",
  "version": "3.0.0",
  "description": "Footer content and styling.",
  "author": "LabPress",
  "license": "GPL-3.0-or-later"
}
```

The required fields are `slug`, `name`, `version`, `description`, `author`, and `license`. New slugs must be 2–64 characters, lowercase letters, numbers, or hyphens. Legacy directories such as `FooterInfo` remain readable until their 3.0 migration.

## Dependencies

```json
{
  "requires": {
    "core": ">=1.2.0",
    "php": ">=8.1",
    "plugins": {
      "dynamic-multilang": ">=3.0.0"
    }
  },
  "provides": ["footer-info"],
  "conflicts": ["footer-info-legacy"]
}
```

`Manager::activate()` checks `requires.core`, `requires.php`, `requires.plugins`, and `conflicts` before loading the plugin. Direct dependencies must already be active; the admin activates them in order. The version constraint forms currently supported are `*`, exact versions, `>=`, `<=`, `>`, `<`, `=`, `^`, `~`, wildcard `1.2.*`, and `||` alternatives.

## Settings schema

`plugin.json` can declare settings fields. The core renders the page and stores values in `plugins.settings_json`; a plugin can also call `LabPress::registerSettings()` when it needs a custom schema.

```json
{
  "settings": [
    {
      "key": "content",
      "type": "textarea",
      "label": "Footer content",
      "default": "© LabPress",
      "required": true,
      "maxlength": 500
    },
    {
      "key": "align",
      "type": "select",
      "label": "Alignment",
      "options": {"left": "Left", "center": "Center", "right": "Right"},
      "default": "center"
    },
    {
      "key": "color",
      "type": "color",
      "label": "Text color",
      "default": "#666666"
    }
  ]
}
```

Supported field types: `text`, `textarea`, `select`, `checkbox`, `number`, `color`, `url`, `email`. Saving validates required fields, max length, numeric values, select membership, URL/email shape, and CSRF.

When the schema is passed to `Settings::register()`, a field may add `label_module` (for example `plugin:footer-info`) so the label and option labels are translated at render time. The optional `options_module` overrides the module for select options. `plugin.json` labels remain the default, untranslated labels.

## Install pipeline

`PackageInstaller` checks ZIP entries before extraction: path traversal, absolute paths, symlinks, entry count (2000), unpacked size (50MB), compression ratio (100), dangerous extensions, one package root, `plugin.json` validity, and `plugin.php`. It extracts into `storage/tmp/plugin-*`, validates the manifest, then swaps `plugins/{slug}` atomically. Existing directories are backed up to `plugins/{slug}.backup-*` and restored if the swap fails. Every attempt appends a JSON line to `storage/logs/plugin-install.log`.
