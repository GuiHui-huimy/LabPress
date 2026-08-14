# Hooks Reference

This chapter provides a reference for the actions and filters exposed by the LabPress core. It is intended for plugin developers who need to know where they can attach custom behavior or modify data.

The list is based on the current LabPress codebase and covers the main hooks used by the core and the included plugins. Hooks are organized by category: global lifecycle, frontend rendering, content rendering, list loops, admin panel, plugin lifecycle, and translation.

Each entry includes:

- **Type** – whether the hook is an action or a filter.
- **Trigger location** – where the hook is fired or applied in the core flow.
- **Arguments** – the values passed to callbacks.
- **Description** – the purpose and typical use.

If a hook is marked as reserved or placeholder, it is currently present in templates but may not yet be connected to a full implementation.

## 1. Global Lifecycle Hooks

### 1.1 `init`

- **Type**: Action
- **Trigger location**: End of `includes/functions.php`, after `loadPlugins()`.
- **Arguments**: None.
- **Description**: Fired after all active plugins have been loaded and before the main request content is rendered. Plugins should register most of their actions, filters, and admin pages inside this hook.

### 1.2 `after_body_open`

- **Type**: Action
- **Trigger location**: `includes/header.php`, immediately after the opening `<body>` tag.
- **Arguments**: None.
- **Description**: Fired on the frontend after the body tag opens. Useful for injecting early scripts or banner markup.

## 2. Frontend Head and Footer Hooks

### 2.1 `before_head_end`

- **Type**: Action
- **Trigger location**: Frontend templates, inside the `<head>` element before the closing `</head>` tag.
- **Arguments**: None.
- **Description**: Fired on public pages. Plugins can use it to enqueue stylesheets or meta tags.

### 2.2 `footer_scripts`

- **Type**: Action
- **Trigger location**: `includes/footer.php`, before the closing `</body>` tag.
- **Arguments**: None.
- **Description**: Fired in the frontend footer. It is commonly used to output custom scripts or footer content. The FooterInfo plugin uses this hook to render its footer information block.

## 3. Frontend Section Hooks

These hooks are fired around major sections of the homepage template `index.php`.

### 3.1 `before_hero_slider`

- **Type**: Action
- **Trigger location**: `index.php`, before the hero slider section.
- **Arguments**: None.
- **Description**: Fired before the homepage carousel.

### 3.2 `after_hero_slider`

- **Type**: Action
- **Trigger location**: `index.php`, after the hero slider section.
- **Arguments**: None.
- **Description**: Fired after the homepage carousel.

### 3.3 `before_about_section`

- **Type**: Action
- **Trigger location**: `index.php`, before the About Us section.
- **Arguments**: None.

### 3.4 `after_about_section`

- **Type**: Action
- **Trigger location**: `index.php`, after the About Us section.
- **Arguments**: None.

### 3.5 `before_research_section`

- **Type**: Action
- **Trigger location**: `index.php`, before the Research Directions section.
- **Arguments**: None.

### 3.6 `after_research_section`

- **Type**: Action
- **Trigger location**: `index.php`, after the Research Directions section.
- **Arguments**: None.

### 3.7 `before_projects_section`

- **Type**: Action
- **Trigger location**: `index.php`, before the Projects section.
- **Arguments**: None.

### 3.8 `after_projects_section`

- **Type**: Action
- **Trigger location**: `index.php`, after the Projects section.
- **Arguments**: None.

### 3.9 `before_publications_section`

- **Type**: Action
- **Trigger location**: `index.php`, before the Latest Publications section.
- **Arguments**: None.

### 3.10 `after_publications_section`

- **Type**: Action
- **Trigger location**: `index.php`, after the Latest Publications section.
- **Arguments**: None.

## 4. Navigation Hooks

### 4.1 `before_nav`

- **Type**: Action
- **Trigger location**: `includes/header.php`, before the main navigation menu.
- **Arguments**: None.
- **Description**: Fired before the `<nav>` element containing the header menu.

### 4.2 `after_nav`

- **Type**: Action
- **Trigger location**: `includes/header.php`, after the main navigation menu.
- **Arguments**: None.
- **Description**: Fired after the header navigation menu.

### 4.3 `nav_menu_item`

- **Type**: Filter
- **Trigger location**: `includes/header.php` and `includes/footer.php` when rendering each menu item.
- **Arguments**:
  - `$html` – the generated HTML string for the menu link.
  - `$menu` – the raw menu item array, including keys such as `id`, `title`, `url`, and `target`.
- **Description**: Applied to each menu item before output. Plugins can use this filter to modify menu HTML or to translate menu titles. The Dynamic Multi-Language plugin uses it for menu translation.

## 5. Content Detail Hooks

These hooks are fired on detail pages such as news, projects, and tools.

### 5.1 `before_content`

- **Type**: Action
- **Trigger location**: Detail templates (`news-detail.php`, `project-detail.php`, `tool-detail.php`), before the main article content.
- **Arguments**: None.

### 5.2 `after_content`

- **Type**: Action
- **Trigger location**: Detail templates, after the main article content.
- **Arguments**: None.

### 5.3 `before_sidebar`

- **Type**: Action
- **Trigger location**: Detail templates, before any sidebar-related output.
- **Arguments**: None.
- **Description**: The templates may not currently render a sidebar, but the hook is reserved for future use and for plugins that want to insert sidebar content before the main sidebar area.

### 5.4 `after_sidebar`

- **Type**: Action
- **Trigger location**: Detail templates, after any sidebar-related output.
- **Arguments**: None.
- **Description**: Reserved hook for sidebar extension.

### 5.5 `comments_area`

- **Type**: Action
- **Trigger location**: Detail templates, after the article content and before the end of the article container.
- **Arguments**: None.
- **Description**: Reserved hook. The core does not include a comments system, but this hook allows plugins to inject a comment or discussion area.

### 5.6 `page_title`

- **Type**: Filter
- **Trigger location**: Detail templates for news, projects, and tools.
- **Arguments**:
  - `$title` – the raw page title.
  - `$type` – a string indicating the content type, such as `news`, `project`, or `tool`.
- **Description**: Applied to the page title before it is escaped and displayed.

### 5.7 `the_content`

- **Type**: Filter
- **Trigger location**: Detail templates for news, projects, and tools.
- **Arguments**:
  - `$content` – the rendered HTML content.
  - `$type` – the content type, such as `news`, `project`, or `tool`.
- **Description**: Applied to the rendered Markdown/HTML body before output. Plugins can modify the final content or insert additional elements.

## 6. List Hooks

List hooks are triggered before, after, and around each item in public list pages.

### 6.1 News List Hooks

- **`before_news_list`** – Action, triggered before the news items loop in `news.php`.
- **`after_news_list`** – Action, triggered after the news items loop.
- **`before_news_item`** – Action, triggered before each individual news item. Receives the news item array.
- **`after_news_item`** – Action, triggered after each individual news item. Receives the news item array.
- **`news_list_title`** – Filter, applied to each news title in the list. Receives the title and the full item array.
- **`news_list_summary`** – Filter, applied to each news summary in the list. Receives the summary and the full item array.

### 6.2 Project List Hooks

- **`before_projects_list`** – Action, triggered before the project list loop in `projects.php`.
- **`after_projects_list`** – Action, triggered after the project list loop.
- **`before_project_item`** – Action, triggered before each project item. Receives the project item array.
- **`after_project_item`** – Action, triggered after each project item. Receives the project item array.
- **`project_list_title`** – Filter, applied to each project title in the list. Receives the title and the full project array.
- **`project_list_summary`** – Filter, applied to each project summary in the list. Receives the summary and the full project array.

### 6.3 Publication List Hooks

- **`before_publications_list`** – Action, triggered before the publications list loop.
- **`after_publications_list`** – Action, triggered after the publications list loop.
- **`before_publication_item`** – Action, triggered before each publication item. Receives the publication array.
- **`after_publication_item`** – Action, triggered after each publication item. Receives the publication array.
- **`publication_list_title`** – Filter, applied to each publication title. Receives the title and the publication array.

### 6.4 Tools List Hooks

- **`before_tools_list`** – Action, triggered before the tools list in `tools.php`.
- **`after_tools_list`** – Action, triggered after the tools list.
- **`tool_list_item`** – Filter, applied to each tool data item before rendering. Receives the tool array.

## 7. Admin Hooks

Admin hooks are used in the admin panel and during data save operations.

### 7.1 Admin Head

- **`admin_before_head_end`** – Action, triggered in `admin/index.php` inside `<head>` before `</head>`.
- **`admin_after_page_content`** – Action, triggered after the main admin page content.
- **`admin_before_page_content`** – Action, triggered before the main admin page content. Receives the current `$view`.

### 7.2 Admin Dashboard

- **`admin_before_dashboard_stats`** – Action, triggered before the dashboard statistics grid.
- **`admin_after_dashboard_stats`** – Action, triggered after the dashboard statistics grid.
- **`admin_dashboard_counts`** – Filter, applied to the array of dashboard count values. Receives the counts array.

### 7.3 Admin Menu

- **`admin_menu`** – Filter, applied to the admin sidebar menu array before rendering. Receives the full menu array. Plugins can use this filter to add, remove, or reorganize menu items.

### 7.4 Admin Save Workflow

The save API `api/save.php` applies dynamic hooks based on the content type and action.

- **`admin_prepare_data`** – Filter, applied to the incoming data before the main save switch. Receives the data, content type, and action.
- **`admin_prepare_{type}`** – Filter, applied to the incoming data for a specific type. `{type}` is replaced by the content type, such as `publications`, `slides`, `tools`, `news`, `projects`, or `site-config`.
- **`admin_before_{action}`** – Action, triggered before a save action. `{action}` is either `save` or `delete`. Receives the content type and data.
- **`admin_before_{type}_{action}`** – Action, triggered before saving or deleting a specific type. Receives the data.
- **`admin_after_{action}`** – Action, triggered after a save action completes. Receives the content type, data, and a boolean success flag.
- **`admin_after_{type}_{action}`** – Action, triggered after a specific type save operation. Receives the data and success flag.

### 7.5 Plugin Management Hooks

- **`admin_before_plugins_{action}`** – Action, triggered before a plugin management action such as `activate`, `deactivate`, or `sync`. Receives the plugin slug.
- **`admin_after_plugins_{action}`** – Action, triggered after a plugin management action. Receives the plugin slug.

## 8. Plugin Lifecycle Hooks

- **`plugin_activation`** – Action, fired when a plugin is activated. Receives the plugin slug.
- **`plugin_deactivation`** – Action, fired when a plugin is deactivated. Receives the plugin slug.
- **`plugin_uninstalled`** – Action, fired before a plugin directory is removed during uninstallation. Receives the plugin slug.
- **`plugin_installed`** – Action, fired after a plugin’s `plugin_install()` function has been called during activation. Receives the plugin slug.

## 9. Login Hooks

- **`login_redirect_url`** – Filter, applied to the redirect URL after a successful login. Receives the default URL.
- **`login_error_message`** – Filter, applied to the error message shown on a failed login. Receives the error message.
- **`login_before_form`** – Action, triggered before the login form.
- **`login_after_form`** – Action, triggered after the login form.

## 10. Translation and Other Filters

- **`translate`** – Filter, applied to every string returned by the `__()` function. Receives the translation value, the original key, the locale, and the module.
- **`stat_value`** – Filter, applied to statistics values before output on the homepage. Receives the value and the full stat item.
- **`page_title_full`** – Filter, applied to the generated full page title. Receives the full title, page title, and suffix.
- **`slides_data`** – Filter, applied to slide data before the API returns it. Receives the slides array.

## 11. Notes on Hook Accuracy

This reference is based on the current LabPress source code. Some hooks, such as `comments_area`, `before_sidebar`, and `after_sidebar`, are currently reserved or placeholder hooks and may not produce visible output unless a plugin implements them.

The dynamic admin save hooks follow a naming pattern that may vary depending on the content type. Plugin developers should verify the exact type names by inspecting `api/save.php`.

If you are developing against a specific version of LabPress, always check the source files for the most accurate list, because new hooks may be added in future releases.

## 12. Next Steps

After reviewing the available hooks, you may want to continue with:

- [Developing Your First Plugin](develop.md) – build a plugin that uses these hooks.
- [Plugin Examples](examples.md) – study real-world hook usage.
- [Plugin System Introduction](intro.md) – review the hook architecture in detail.
- [Dynamic Multi-Language Plugin](../i18n/dynamic-multilang.md) – see a complete plugin that relies heavily on filters.
- [Core Language Packs](../i18n/core-lang.md) – understand the `translate` filter and language loading.