=== Between Words ===
Contributors: abolfaazl
Tags: blog, one-column, two-columns, custom-menu, featured-images, rtl-language-support, translation-ready
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Copyright: Copyright (c) 2026 abolfaazl

A minimal writing-focused classic WordPress theme for personal essays, podcasts, galleries, and image posts.

== Description ==

Between Words is a classic WordPress theme designed for personal writing, archives, and quiet editorial layouts. The theme is fully translation-ready, supports RTL and LTR languages, and keeps its front-end presentation focused on essays, notes, and media-rich posts.

The base theme works as a regular blog theme on its own.

Podcast, gallery, image, and conversation publishing are handled through native WordPress posts using post formats and categories. No custom post type plugin is required for the main workflow.

The theme provides basic technical SEO foundations such as semantic markup, optional fallback meta/schema when no SEO plugin is active, WordPress core feed links, and compatibility with WordPress core XML sitemaps.

Typography and color controls are available in the Customizer under `Between Words Options`. The default theme does not load external fonts. Google Fonts loading is optional, disabled by default, and only used when the site owner explicitly selects the optional Google Fonts CDN source.

When optional Google Fonts CDN loading is enabled, site owners may either choose from the curated font list or enter a Google Fonts family name manually. Enter only the family name, not a URL, CSS, or `@import` rule. Full Google Fonts browsing/search is intentionally not built into the theme; local or system fonts are recommended when privacy requirements prohibit remote font requests.

== Installation ==

1. Upload the `between-words` theme folder to `/wp-content/themes/`.
2. Activate the theme in `Appearance > Themes`.
3. Assign a menu to the `Primary Menu` and optionally to the `Footer Menu`.
4. Adjust the About text, newsletter text, and footer links in the Customizer.
5. Use native WordPress posts with formats such as `Audio`, `Gallery`, `Image`, or `Chat` plus matching categories like `podcast`, `gallery`, `image`, and `conversation` when desired.

== Frequently Asked Questions ==

= Does the theme require a plugin? =

No. The theme works as a standard blog theme without any plugin.

= How do I publish podcasts or galleries? =

Create a normal WordPress post, choose the matching post format, and optionally assign the matching category slug such as `podcast` or `gallery`.

= Does the theme support RTL? =

Yes. Persian/RTL remains the primary visual baseline, and LTR overrides are included for English and other left-to-right languages.

== Changelog ==

= 1.0.0 =

* Initial public-ready theme packaging pass.
* Added translation-ready strings and `languages` folder.
* Added RTL/LTR support refinements.
* Switched the content workflow back to native WordPress posts with post formats and categories.

== Credits ==

* Theme design and development: abolfaazl
* Inline SVG social icons are bundled as simple original theme markup.
* No remote fonts, trackers, or third-party analytics are bundled.

== License ==

This theme is licensed under the GNU General Public License v2 or later.
Copyright (c) 2026 abolfaazl
