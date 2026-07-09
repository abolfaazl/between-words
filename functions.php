<?php
if (!defined('ABSPATH')) {
    exit;
}

function between_words_setup(): void
{
    load_theme_textdomain('between-words', get_template_directory() . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_image_size('between-words-card', 740, 356, true);
    add_image_size('between-words-hero', 1480, 740, true);
    add_image_size('between-words-sidebar', 360, 180, true);
    add_theme_support('automatic-feed-links');
    add_theme_support('custom-logo');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_theme_support('post-formats', ['audio', 'gallery', 'image', 'chat', 'quote']);
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);

    add_editor_style('editor-style.css');

    register_nav_menus([
        'primary' => esc_html__('Primary Menu', 'between-words'),
        'footer' => esc_html__('Footer Menu', 'between-words'),
    ]);
}
add_action('after_setup_theme', 'between_words_setup');

require_once get_template_directory() . '/inc/widgets.php';

function between_words_get_asset_version(string $relative_path): string
{
    $path = get_template_directory() . '/' . ltrim($relative_path, '/');
    if (file_exists($path)) {
        $mtime = filemtime($path);
        if ($mtime) {
            return (string) $mtime;
        }
    }

    return (string) wp_get_theme()->get('Version');
}

function between_words_enqueue_assets(): void
{
    $google_font_url = between_words_get_google_font_url();

    // Remote Google Fonts are opt-in only and disabled by default for privacy/readiness.
    if ($google_font_url) {
        wp_enqueue_style('between-words-google-fonts', esc_url($google_font_url), [], null);
    }

    wp_enqueue_style('between-words-style', get_stylesheet_uri(), [], between_words_get_asset_version('style.css'));
    $customizer_css = between_words_output_customizer_css();
    if ($customizer_css !== '') {
        wp_add_inline_style('between-words-style', $customizer_css);
    }

    global $wp_version;

    $script_args = true;
    if (version_compare((string) $wp_version, '6.3', '>=')) {
        $script_args = ['in_footer' => true];
        $script_args['strategy'] = 'defer';
    }

    wp_enqueue_script(
        'between-words-script',
        get_template_directory_uri() . '/assets/js/theme.js',
        [],
        between_words_get_asset_version('assets/js/theme.js'),
        $script_args
    );

    if (function_exists('wp_script_add_data')) {
        wp_script_add_data('between-words-script', 'defer', true);
    }

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'between_words_enqueue_assets');

function between_words_resource_hints(array $urls, string $relation_type): array
{
    if (between_words_get_google_font_url() === '') {
        return $urls;
    }

    if ($relation_type === 'preconnect') {
        $urls[] = 'https://fonts.googleapis.com';
        $urls[] = [
            'href' => 'https://fonts.gstatic.com',
            'crossorigin' => 'anonymous',
        ];
    }

    return $urls;
}
add_filter('wp_resource_hints', 'between_words_resource_hints', 10, 2);

function between_words_disable_emojis(): void
{
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
}
add_action('init', 'between_words_disable_emojis');

function between_words_customize_register(\WP_Customize_Manager $wp_customize): void
{
    $wp_customize->add_panel('between_words_options', [
        'title' => esc_html__('Between Words Options', 'between-words'),
        'priority' => 30,
    ]);

    $wp_customize->add_section('between_words_theme_options', [
        'title' => esc_html__('General Options', 'between-words'),
        'panel' => 'between_words_options',
        'priority' => 10,
    ]);

    $wp_customize->add_section('between_words_typography_options', [
        'title' => esc_html__('Typography', 'between-words'),
        'panel' => 'between_words_options',
        'priority' => 20,
    ]);

    $wp_customize->add_section('between_words_light_color_options', [
        'title' => esc_html__('Light Mode Colors', 'between-words'),
        'panel' => 'between_words_options',
        'priority' => 30,
    ]);

    $wp_customize->add_section('between_words_dark_color_options', [
        'title' => esc_html__('Dark Mode Colors', 'between-words'),
        'panel' => 'between_words_options',
        'priority' => 40,
    ]);

    $wp_customize->add_section('between_words_reader_color_options', [
        'title' => esc_html__('Reader Mode Colors', 'between-words'),
        'panel' => 'between_words_options',
        'priority' => 50,
    ]);

    $wp_customize->add_section('between_words_seo_options', [
        'title' => esc_html__('SEO', 'between-words'),
        'panel' => 'between_words_options',
        'priority' => 60,
    ]);

    $settings = [
        'between_words_about_text' => ['default' => between_words_get_default_about_text(), 'sanitize' => 'wp_kses_post'],
        'between_words_about_link' => ['default' => home_url('/about/'), 'sanitize' => 'esc_url_raw'],
        'between_words_newsletter_text' => ['default' => between_words_get_default_newsletter_text(), 'sanitize' => 'wp_kses_post'],
        'between_words_newsletter_shortcode' => ['default' => '', 'sanitize' => 'sanitize_text_field'],
        'between_words_social_telegram' => ['default' => '', 'sanitize' => 'esc_url_raw'],
        'between_words_social_instagram' => ['default' => '', 'sanitize' => 'esc_url_raw'],
        'between_words_social_email' => ['default' => '', 'sanitize' => 'sanitize_email'],
        'between_words_footer_privacy_label' => ['default' => between_words_label('privacy'), 'sanitize' => 'sanitize_text_field'],
        'between_words_footer_privacy_url' => ['default' => home_url('/privacy-policy/'), 'sanitize' => 'esc_url_raw'],
        'between_words_footer_contact_label' => ['default' => between_words_label('contact'), 'sanitize' => 'sanitize_text_field'],
        'between_words_footer_contact_url' => ['default' => home_url('/contact/'), 'sanitize' => 'esc_url_raw'],
    ];

    foreach ($settings as $id => $args) {
        $wp_customize->add_setting($id, [
            'default' => $args['default'],
            'sanitize_callback' => $args['sanitize'],
        ]);
    }

    $controls = [
        'between_words_about_text' => ['label' => esc_html__('About Text', 'between-words'), 'type' => 'textarea'],
        'between_words_about_link' => ['label' => esc_html__('About Link', 'between-words'), 'type' => 'url'],
        'between_words_newsletter_text' => ['label' => esc_html__('Newsletter Text', 'between-words'), 'type' => 'textarea'],
        'between_words_newsletter_shortcode' => ['label' => esc_html__('Newsletter Shortcode', 'between-words'), 'type' => 'text'],
        'between_words_social_telegram' => ['label' => esc_html__('Telegram URL', 'between-words'), 'type' => 'url'],
        'between_words_social_instagram' => ['label' => esc_html__('Instagram URL', 'between-words'), 'type' => 'url'],
        'between_words_social_email' => ['label' => esc_html__('Contact Email', 'between-words'), 'type' => 'email'],
        'between_words_footer_privacy_label' => ['label' => esc_html__('Privacy Label', 'between-words'), 'type' => 'text'],
        'between_words_footer_privacy_url' => ['label' => esc_html__('Privacy URL', 'between-words'), 'type' => 'url'],
        'between_words_footer_contact_label' => ['label' => esc_html__('Contact Label', 'between-words'), 'type' => 'text'],
        'between_words_footer_contact_url' => ['label' => esc_html__('Contact URL', 'between-words'), 'type' => 'url'],
    ];

    foreach ($controls as $id => $control) {
        $wp_customize->add_control($id, [
            'label' => $control['label'],
            'section' => 'between_words_theme_options',
            'type' => $control['type'],
        ]);
    }

    $font_choices = between_words_get_font_choices();

    $wp_customize->add_setting('between_words_font_source', [
        'default' => 'default',
        'sanitize_callback' => 'between_words_sanitize_font_source',
    ]);

    $wp_customize->add_control('between_words_font_source', [
        'label' => esc_html__('Font Source', 'between-words'),
        'description' => esc_html__('Remote Google Fonts are optional and disabled by default.', 'between-words'),
        'section' => 'between_words_typography_options',
        'type' => 'select',
        'choices' => [
            'default' => esc_html__('Theme default', 'between-words'),
            'system' => esc_html__('System / local stack', 'between-words'),
            'google_cdn_optional' => esc_html__('Optional Google Fonts CDN', 'between-words'),
        ],
    ]);

    $wp_customize->add_setting('between_words_body_font', [
        'default' => 'default',
        'sanitize_callback' => 'between_words_sanitize_body_font',
    ]);

    $wp_customize->add_control('between_words_body_font', [
        'label' => esc_html__('Body Font', 'between-words'),
        'section' => 'between_words_typography_options',
        'type' => 'select',
        'choices' => $font_choices,
    ]);

    $wp_customize->add_setting('between_words_heading_font', [
        'default' => 'inherit',
        'sanitize_callback' => 'between_words_sanitize_heading_font',
    ]);

    $wp_customize->add_control('between_words_heading_font', [
        'label' => esc_html__('Heading Font', 'between-words'),
        'section' => 'between_words_typography_options',
        'type' => 'select',
        'choices' => ['inherit' => esc_html__('Inherit body font', 'between-words')] + $font_choices,
    ]);

    $google_font_description = esc_html__('Enter a Google Fonts family name only, for example: Playfair Display, Roboto, Noto Serif, Noto Sans Arabic. No URL or CSS is allowed. Remote Google Fonts load only when Optional Google Fonts CDN is enabled.', 'between-words');

    $wp_customize->add_setting('between_words_google_body_font_family', [
        'default' => '',
        'sanitize_callback' => 'between_words_sanitize_google_font_family',
    ]);

    $wp_customize->add_control('between_words_google_body_font_family', [
        'label' => esc_html__('Google Body Font Family', 'between-words'),
        'description' => $google_font_description,
        'section' => 'between_words_typography_options',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('between_words_google_heading_font_family', [
        'default' => '',
        'sanitize_callback' => 'between_words_sanitize_google_font_family',
    ]);

    $wp_customize->add_control('between_words_google_heading_font_family', [
        'label' => esc_html__('Google Heading Font Family', 'between-words'),
        'description' => $google_font_description,
        'section' => 'between_words_typography_options',
        'type' => 'text',
    ]);

    foreach (between_words_get_color_settings() as $setting_id => $args) {
        $wp_customize->add_setting($setting_id, [
            'default' => $args['default'],
            'sanitize_callback' => 'sanitize_hex_color',
        ]);

        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, $setting_id, [
            'label' => $args['label'],
            'section' => $args['section'],
        ]));
    }

    $seo_settings = [
        'between_words_seo_enable_meta' => ['label' => esc_html__('Enable basic SEO meta when no SEO plugin is active', 'between-words'), 'default' => true],
        'between_words_seo_enable_social_meta' => ['label' => esc_html__('Enable basic Open Graph / Twitter meta when no SEO plugin is active', 'between-words'), 'default' => true],
        'between_words_seo_enable_schema' => ['label' => esc_html__('Enable JSON-LD structured data when no SEO plugin is active', 'between-words'), 'default' => true],
        'between_words_seo_enable_breadcrumbs' => ['label' => esc_html__('Enable breadcrumbs', 'between-words'), 'default' => true],
        'between_words_seo_noindex_search' => ['label' => esc_html__('Noindex search result pages', 'between-words'), 'default' => true],
        'between_words_seo_noindex_404' => ['label' => esc_html__('Noindex 404 pages', 'between-words'), 'default' => true],
        'between_words_seo_noindex_paged_archives' => ['label' => esc_html__('Noindex paginated archive pages', 'between-words'), 'default' => false],
    ];

    foreach ($seo_settings as $setting_id => $args) {
        $wp_customize->add_setting($setting_id, [
            'default' => $args['default'],
            'sanitize_callback' => 'between_words_sanitize_checkbox',
        ]);

        $wp_customize->add_control($setting_id, [
            'label' => $args['label'],
            'section' => 'between_words_seo_options',
            'type' => 'checkbox',
        ]);
    }
}
add_action('customize_register', 'between_words_customize_register');

function between_words_sanitize_checkbox($value): bool
{
    return !empty($value);
}

function between_words_seo_plugin_active(): bool
{
    static $active = null;

    if ($active !== null) {
        return $active;
    }

    $active = defined('WPSEO_VERSION')
        || defined('RANK_MATH_VERSION')
        || defined('AIOSEO_VERSION')
        || defined('SEOPRESS_VERSION')
        || defined('THE_SEO_FRAMEWORK_VERSION')
        || class_exists('WPSEO_Frontend')
        || class_exists('RankMath')
        || class_exists('AIOSEO\\Plugin\\Common\\Main')
        || class_exists('SEOPress\\Main')
        || class_exists('The_SEO_Framework\\Load');

    return $active;
}

function between_words_seo_plugin_breadcrumbs_active(): bool
{
    static $active = null;

    if ($active !== null) {
        return $active;
    }

    $active = function_exists('yoast_breadcrumb')
        || function_exists('rank_math_the_breadcrumbs')
        || function_exists('aioseo_breadcrumbs')
        || function_exists('seopress_display_breadcrumbs')
        || class_exists('The_SEO_Framework\\Breadcrumbs');

    return $active;
}

function between_words_is_theme_seo_meta_enabled(): bool
{
    $enabled = (bool) get_theme_mod('between_words_seo_enable_meta', true) && !between_words_seo_plugin_active();
    return (bool) apply_filters('between_words_enable_theme_seo_meta', $enabled);
}

function between_words_is_theme_schema_enabled(): bool
{
    $enabled = between_words_is_theme_seo_meta_enabled() && (bool) get_theme_mod('between_words_seo_enable_schema', true);
    return (bool) apply_filters('between_words_enable_theme_schema', $enabled);
}

function between_words_is_theme_breadcrumbs_enabled(): bool
{
    $enabled = (bool) get_theme_mod('between_words_seo_enable_breadcrumbs', true) && !between_words_seo_plugin_breadcrumbs_active();
    return (bool) apply_filters('between_words_enable_theme_breadcrumbs', $enabled);
}

function between_words_normalize_text(string $text): string
{
    $text = strip_shortcodes($text);
    $text = between_words_remove_audio_from_content($text);
    $text = wp_strip_all_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, get_bloginfo('charset'));
    $text = preg_replace('/\s+/u', ' ', $text);

    return trim((string) $text);
}

function between_words_trim_words_naturally(string $text, int $limit = 160): string
{
    $text = between_words_normalize_text($text);
    if ($text === '' || function_exists('mb_strlen') && mb_strlen($text) <= $limit) {
        return $text;
    }

    if (!function_exists('mb_substr') || !function_exists('mb_strrpos')) {
        $trimmed = substr($text, 0, $limit);
        $space = strrpos($trimmed, ' ');
        return trim(($space !== false ? substr($trimmed, 0, $space) : $trimmed)) . '…';
    }

    $trimmed = mb_substr($text, 0, $limit);
    $space = mb_strrpos($trimmed, ' ');

    return trim(($space !== false ? mb_substr($trimmed, 0, $space) : $trimmed)) . '…';
}

function between_words_get_meta_description(): string
{
    if (is_singular()) {
        $post = get_post();
        if ($post instanceof \WP_Post) {
            $source = has_excerpt($post) ? $post->post_excerpt : $post->post_content;
            return between_words_trim_words_naturally((string) $source, 160);
        }
    }

    if (is_home() || is_front_page()) {
        $description = trim((string) get_bloginfo('description'));
        return $description !== '' ? $description : (string) get_bloginfo('name');
    }

    if (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        if ($term instanceof \WP_Term) {
            $description = between_words_trim_words_naturally((string) $term->description, 160);
            if ($description !== '') {
                return $description;
            }

            return sprintf(between_words_label('posts_from'), single_term_title('', false));
        }
    }

    if (is_post_type_archive() || is_archive()) {
        $description = between_words_trim_words_naturally((string) get_the_archive_description(), 160);
        if ($description !== '') {
            return $description;
        }

        return sprintf(between_words_label('posts_from'), wp_strip_all_tags(get_the_archive_title()));
    }

    if (is_search()) {
        return sprintf(between_words_label('search_results_for'), get_search_query());
    }

    return '';
}

function between_words_get_canonical_url(): string
{
    if (is_404()) {
        return '';
    }

    if (is_singular()) {
        if (function_exists('wp_get_canonical_url')) {
            $canonical = (string) wp_get_canonical_url();
            if ($canonical !== '') {
                return $canonical;
            }
        }

        return (string) get_permalink();
    }

    if (is_front_page()) {
        return home_url('/');
    }

    if (is_home()) {
        $posts_page_id = (int) get_option('page_for_posts');
        if ($posts_page_id > 0) {
            return (string) get_permalink($posts_page_id);
        }

        return home_url('/');
    }

    if (is_search()) {
        return get_theme_mod('between_words_seo_noindex_search', true) ? '' : (string) get_search_link();
    }

    if (function_exists('get_pagenum_link')) {
        return (string) get_pagenum_link(max(1, get_query_var('paged'), get_query_var('page')));
    }

    return '';
}

function between_words_should_output_theme_canonical(): bool
{
    return !is_singular() && between_words_get_canonical_url() !== '';
}

function between_words_get_current_request_url(): string
{
    if (is_search()) {
        return (string) get_search_link();
    }

    if (is_singular()) {
        return (string) get_permalink();
    }

    if (is_front_page() || is_home()) {
        return between_words_get_canonical_url() ?: home_url('/');
    }

    return (string) get_pagenum_link(max(1, get_query_var('paged'), get_query_var('page')));
}

function between_words_get_seo_image_data(?int $post_id = null): array
{
    $post_id = (int) ($post_id ?: get_the_ID());
    if ($post_id <= 0) {
        return [];
    }

    $image_id = between_words_get_card_image_id($post_id);
    if ($image_id <= 0) {
        return [];
    }

    $image_src = wp_get_attachment_image_src($image_id, 'full');
    if (!$image_src) {
        return [];
    }

    $alt = trim((string) get_post_meta($image_id, '_wp_attachment_image_alt', true));
    if ($alt === '') {
        $alt = get_the_title($image_id) ?: get_the_title($post_id);
    }

    return [
        'id' => $image_id,
        'url' => $image_src[0],
        'width' => isset($image_src[1]) ? (int) $image_src[1] : 0,
        'height' => isset($image_src[2]) ? (int) $image_src[2] : 0,
        'alt' => $alt,
    ];
}

function between_words_get_image_attr_fallback(int $post_id, int $image_id): array
{
    $alt = trim((string) get_post_meta($image_id, '_wp_attachment_image_alt', true));
    if ($alt === '') {
        $alt = get_the_title($image_id) ?: get_the_title($post_id);
    }

    return ['alt' => $alt];
}

function between_words_get_schema_language(): string
{
    $language = (string) get_bloginfo('language');
    return $language !== '' ? $language : str_replace('_', '-', determine_locale());
}

function between_words_format_iso8601_duration(string $duration): string
{
    if (!preg_match('/^(?:(\d+):)?(\d{1,2}):(\d{2})$/', trim($duration), $matches)) {
        return '';
    }

    $hours = isset($matches[1]) ? (int) $matches[1] : 0;
    $minutes = (int) $matches[2];
    $seconds = (int) $matches[3];
    $iso = 'PT';

    if ($hours > 0) {
        $iso .= $hours . 'H';
    }
    if ($minutes > 0) {
        $iso .= $minutes . 'M';
    }
    if ($seconds > 0 || $iso === 'PT') {
        $iso .= $seconds . 'S';
    }

    return $iso;
}

function between_words_get_font_choices(): array
{
    return (array) apply_filters('between_words_font_choices', [
        'default' => esc_html__('Theme default', 'between-words'),
        'system_sans' => esc_html__('System sans', 'between-words'),
        'system_serif' => esc_html__('System serif', 'between-words'),
        'vazirmatn' => esc_html__('Vazirmatn', 'between-words'),
        'noto_naskh_arabic' => esc_html__('Noto Naskh Arabic', 'between-words'),
        'noto_sans_arabic' => esc_html__('Noto Sans Arabic', 'between-words'),
        'noto_kufi_arabic' => esc_html__('Noto Kufi Arabic', 'between-words'),
        'ibm_plex_sans_arabic' => esc_html__('IBM Plex Sans Arabic', 'between-words'),
        'cairo' => esc_html__('Cairo', 'between-words'),
        'tajawal' => esc_html__('Tajawal', 'between-words'),
        'almarai' => esc_html__('Almarai', 'between-words'),
        'readex_pro' => esc_html__('Readex Pro', 'between-words'),
        'alexandria' => esc_html__('Alexandria', 'between-words'),
        'rubik' => esc_html__('Rubik', 'between-words'),
        'inter' => esc_html__('Inter', 'between-words'),
        'roboto' => esc_html__('Roboto', 'between-words'),
        'open_sans' => esc_html__('Open Sans', 'between-words'),
        'lato' => esc_html__('Lato', 'between-words'),
        'montserrat' => esc_html__('Montserrat', 'between-words'),
        'poppins' => esc_html__('Poppins', 'between-words'),
        'source_sans_3' => esc_html__('Source Sans 3', 'between-words'),
        'lora' => esc_html__('Lora', 'between-words'),
        'merriweather' => esc_html__('Merriweather', 'between-words'),
        'playfair_display' => esc_html__('Playfair Display', 'between-words'),
        'libre_baskerville' => esc_html__('Libre Baskerville', 'between-words'),
        'crimson_text' => esc_html__('Crimson Text', 'between-words'),
        'cormorant_garamond' => esc_html__('Cormorant Garamond', 'between-words'),
        'spectral' => esc_html__('Spectral', 'between-words'),
        'newsreader' => esc_html__('Newsreader', 'between-words'),
        'noto_serif' => esc_html__('Noto Serif', 'between-words'),
        'noto_sans' => esc_html__('Noto Sans', 'between-words'),
        'pt_serif' => esc_html__('PT Serif', 'between-words'),
        'pt_sans' => esc_html__('PT Sans', 'between-words'),
    ]);
}

function between_words_get_font_stack(string $key): string
{
    $stacks = (array) apply_filters('between_words_font_stacks', [
        'default' => '"Vazirmatn", "IRANSans", "Segoe UI", Tahoma, Arial, sans-serif',
        'system_sans' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
        'system_serif' => 'Georgia, "Times New Roman", serif',
        'vazirmatn' => '"Vazirmatn", Tahoma, Arial, sans-serif',
        'noto_naskh_arabic' => '"Noto Naskh Arabic", Georgia, serif',
        'noto_sans_arabic' => '"Noto Sans Arabic", Tahoma, Arial, sans-serif',
        'noto_kufi_arabic' => '"Noto Kufi Arabic", Tahoma, Arial, sans-serif',
        'ibm_plex_sans_arabic' => '"IBM Plex Sans Arabic", Tahoma, Arial, sans-serif',
        'cairo' => '"Cairo", Tahoma, Arial, sans-serif',
        'tajawal' => '"Tajawal", Tahoma, Arial, sans-serif',
        'almarai' => '"Almarai", Tahoma, Arial, sans-serif',
        'readex_pro' => '"Readex Pro", Tahoma, Arial, sans-serif',
        'alexandria' => '"Alexandria", Tahoma, Arial, sans-serif',
        'rubik' => '"Rubik", Tahoma, Arial, sans-serif',
        'inter' => '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        'roboto' => '"Roboto", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        'open_sans' => '"Open Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        'lato' => '"Lato", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        'montserrat' => '"Montserrat", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        'poppins' => '"Poppins", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        'source_sans_3' => '"Source Sans 3", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        'lora' => '"Lora", Georgia, serif',
        'merriweather' => '"Merriweather", Georgia, serif',
        'playfair_display' => '"Playfair Display", Georgia, serif',
        'libre_baskerville' => '"Libre Baskerville", Georgia, serif',
        'crimson_text' => '"Crimson Text", Georgia, serif',
        'cormorant_garamond' => '"Cormorant Garamond", Georgia, serif',
        'spectral' => '"Spectral", Georgia, serif',
        'newsreader' => '"Newsreader", Georgia, serif',
        'noto_serif' => '"Noto Serif", Georgia, serif',
        'noto_sans' => '"Noto Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        'pt_serif' => '"PT Serif", Georgia, serif',
        'pt_sans' => '"PT Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
    ]);

    return $stacks[$key] ?? $stacks['default'];
}

function between_words_sanitize_font_source(string $value): string
{
    $value = sanitize_key($value);
    $allowed = ['default', 'system', 'google_cdn_optional'];

    return in_array($value, $allowed, true) ? $value : 'default';
}

function between_words_sanitize_body_font(string $value): string
{
    $value = sanitize_key($value);

    return array_key_exists($value, between_words_get_font_choices()) ? $value : 'default';
}

function between_words_sanitize_heading_font(string $value): string
{
    $value = sanitize_key($value);

    if ('inherit' === $value) {
        return $value;
    }

    return between_words_sanitize_body_font($value);
}

function between_words_sanitize_google_font_family(string $value): string
{
    $value = trim(wp_strip_all_tags($value));
    $value = str_replace(['"', "'"], '', $value);
    $value = preg_replace('/\s+/', ' ', $value);

    if (!$value || strlen($value) > 80) {
        return '';
    }

    if (preg_match('/(:|;|\/|\\\\|\(|\)|\{|\}|@|<|>)/', $value)) {
        return '';
    }

    if (preg_match('/\b(http|https|url|import)\b/i', $value)) {
        return '';
    }

    if (!preg_match('/^[A-Za-z0-9 _-]+$/', $value)) {
        return '';
    }

    return trim($value);
}

function between_words_should_load_google_fonts(): bool
{
    return 'google_cdn_optional' === get_theme_mod('between_words_font_source', 'default');
}

function between_words_get_google_font_url(?string $font_key = null): string
{
    if (!between_words_should_load_google_fonts()) {
        return '';
    }

    $font_families = [];
    if ($font_key) {
        $font_family = between_words_get_google_font_family_for_key(between_words_sanitize_body_font($font_key));
        if ($font_family) {
            $font_families[] = $font_family;
        }
    } else {
        $manual_body_font = between_words_sanitize_google_font_family((string) get_theme_mod('between_words_google_body_font_family', ''));
        $manual_heading_font = between_words_sanitize_google_font_family((string) get_theme_mod('between_words_google_heading_font_family', ''));

        if ($manual_body_font) {
            $font_families[] = $manual_body_font;
        } else {
            $body_font_family = between_words_get_google_font_family_for_key(between_words_sanitize_body_font((string) get_theme_mod('between_words_body_font', 'default')));
            if ($body_font_family) {
                $font_families[] = $body_font_family;
            }
        }

        $heading_font = between_words_sanitize_heading_font((string) get_theme_mod('between_words_heading_font', 'inherit'));
        if ($manual_heading_font) {
            $font_families[] = $manual_heading_font;
        } elseif ('inherit' !== $heading_font) {
            $heading_font_family = between_words_get_google_font_family_for_key($heading_font);
            if ($heading_font_family) {
                $font_families[] = $heading_font_family;
            }
        }
    }

    return between_words_build_google_fonts_url($font_families);
}

function between_words_get_google_font_family_for_key(string $font_key): string
{
    $google_families = (array) apply_filters('between_words_google_font_families', [
        'vazirmatn' => 'Vazirmatn',
        'noto_naskh_arabic' => 'Noto Naskh Arabic',
        'noto_sans_arabic' => 'Noto Sans Arabic',
        'noto_kufi_arabic' => 'Noto Kufi Arabic',
        'ibm_plex_sans_arabic' => 'IBM Plex Sans Arabic',
        'cairo' => 'Cairo',
        'tajawal' => 'Tajawal',
        'almarai' => 'Almarai',
        'readex_pro' => 'Readex Pro',
        'alexandria' => 'Alexandria',
        'rubik' => 'Rubik',
        'inter' => 'Inter',
        'roboto' => 'Roboto',
        'open_sans' => 'Open Sans',
        'lato' => 'Lato',
        'montserrat' => 'Montserrat',
        'poppins' => 'Poppins',
        'source_sans_3' => 'Source Sans 3',
        'merriweather' => 'Merriweather',
        'lora' => 'Lora',
        'playfair_display' => 'Playfair Display',
        'libre_baskerville' => 'Libre Baskerville',
        'crimson_text' => 'Crimson Text',
        'cormorant_garamond' => 'Cormorant Garamond',
        'spectral' => 'Spectral',
        'newsreader' => 'Newsreader',
        'noto_serif' => 'Noto Serif',
        'noto_sans' => 'Noto Sans',
        'pt_serif' => 'PT Serif',
        'pt_sans' => 'PT Sans',
    ]);

    return isset($google_families[$font_key]) ? between_words_sanitize_google_font_family((string) $google_families[$font_key]) : '';
}

function between_words_build_google_fonts_url(array $families): string
{
    $family_args = [];

    foreach (array_unique(array_filter($families)) as $family) {
        $family = between_words_sanitize_google_font_family((string) $family);
        if (!$family) {
            continue;
        }

        $family_args[] = 'family=' . str_replace('%20', '+', rawurlencode($family)) . ':wght@300;400;500;600;700;800;900';
    }

    if (!$family_args) {
        return '';
    }

    return 'https://fonts.googleapis.com/css2?' . implode('&', $family_args) . '&display=swap';
}

function between_words_get_color_settings(): array
{
    return [
        'between_words_light_bg' => ['label' => esc_html__('Light background', 'between-words'), 'default' => '#ffffff', 'section' => 'between_words_light_color_options'],
        'between_words_light_surface' => ['label' => esc_html__('Light surface', 'between-words'), 'default' => '#ffffff', 'section' => 'between_words_light_color_options'],
        'between_words_light_card' => ['label' => esc_html__('Light card', 'between-words'), 'default' => '#ffffff', 'section' => 'between_words_light_color_options'],
        'between_words_light_text' => ['label' => esc_html__('Light text', 'between-words'), 'default' => '#111111', 'section' => 'between_words_light_color_options'],
        'between_words_light_muted' => ['label' => esc_html__('Light muted text', 'between-words'), 'default' => '#5f5f5f', 'section' => 'between_words_light_color_options'],
        'between_words_light_border' => ['label' => esc_html__('Light border', 'between-words'), 'default' => '#d8d8d8', 'section' => 'between_words_light_color_options'],
        'between_words_light_accent' => ['label' => esc_html__('Light accent', 'between-words'), 'default' => '#111111', 'section' => 'between_words_light_color_options'],
        'between_words_dark_bg' => ['label' => esc_html__('Dark background', 'between-words'), 'default' => '#111111', 'section' => 'between_words_dark_color_options'],
        'between_words_dark_surface' => ['label' => esc_html__('Dark surface', 'between-words'), 'default' => '#151515', 'section' => 'between_words_dark_color_options'],
        'between_words_dark_card' => ['label' => esc_html__('Dark card', 'between-words'), 'default' => '#181818', 'section' => 'between_words_dark_color_options'],
        'between_words_dark_text' => ['label' => esc_html__('Dark text', 'between-words'), 'default' => '#f1f1f1', 'section' => 'between_words_dark_color_options'],
        'between_words_dark_muted' => ['label' => esc_html__('Dark muted text', 'between-words'), 'default' => '#b9b9b9', 'section' => 'between_words_dark_color_options'],
        'between_words_dark_border' => ['label' => esc_html__('Dark border', 'between-words'), 'default' => '#383838', 'section' => 'between_words_dark_color_options'],
        'between_words_dark_accent' => ['label' => esc_html__('Dark accent', 'between-words'), 'default' => '#f3f3f3', 'section' => 'between_words_dark_color_options'],
        'between_words_reader_bg' => ['label' => esc_html__('Reader background', 'between-words'), 'default' => '#efe4c8', 'section' => 'between_words_reader_color_options'],
        'between_words_reader_surface' => ['label' => esc_html__('Reader surface', 'between-words'), 'default' => '#f4ead2', 'section' => 'between_words_reader_color_options'],
        'between_words_reader_card' => ['label' => esc_html__('Reader card', 'between-words'), 'default' => '#f2e7cf', 'section' => 'between_words_reader_color_options'],
        'between_words_reader_text' => ['label' => esc_html__('Reader text', 'between-words'), 'default' => '#2d2418', 'section' => 'between_words_reader_color_options'],
        'between_words_reader_muted' => ['label' => esc_html__('Reader muted text', 'between-words'), 'default' => '#6e5d49', 'section' => 'between_words_reader_color_options'],
        'between_words_reader_border' => ['label' => esc_html__('Reader border', 'between-words'), 'default' => '#cdbd9f', 'section' => 'between_words_reader_color_options'],
        'between_words_reader_accent' => ['label' => esc_html__('Reader accent', 'between-words'), 'default' => '#3a2d1b', 'section' => 'between_words_reader_color_options'],
    ];
}

function between_words_get_color_value(string $setting_id): string
{
    $settings = between_words_get_color_settings();
    $default = $settings[$setting_id]['default'] ?? '#ffffff';
    $value = sanitize_hex_color((string) get_theme_mod($setting_id, $default));

    return $value ?: $default;
}

function between_words_get_manual_google_font_stack(string $font_family, string $fallback = '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'): string
{
    $font_family = between_words_sanitize_google_font_family($font_family);

    if (!$font_family) {
        return '';
    }

    return '"' . $font_family . '", ' . $fallback;
}

function between_words_output_customizer_css(): string
{
    $body_font = between_words_sanitize_body_font((string) get_theme_mod('between_words_body_font', 'default'));
    $heading_font = between_words_sanitize_heading_font((string) get_theme_mod('between_words_heading_font', 'inherit'));
    $body_stack = wp_strip_all_tags(between_words_get_font_stack($body_font));
    $heading_stack = 'inherit' === $heading_font ? 'var(--bw-font-body)' : wp_strip_all_tags(between_words_get_font_stack($heading_font));

    if (between_words_should_load_google_fonts()) {
        $manual_body_stack = between_words_get_manual_google_font_stack((string) get_theme_mod('between_words_google_body_font_family', ''));
        $manual_heading_stack = between_words_get_manual_google_font_stack((string) get_theme_mod('between_words_google_heading_font_family', ''));

        if ($manual_body_stack) {
            $body_stack = $manual_body_stack;
        }

        if ($manual_heading_stack) {
            $heading_stack = $manual_heading_stack;
        }
    }

    $css = ':root, html.bw-theme-light {';
    $css .= '--bw-font-body:' . $body_stack . ';';
    $css .= '--bw-font-heading:' . $heading_stack . ';';
    $css .= '--bw-bg:' . between_words_get_color_value('between_words_light_bg') . ';';
    $css .= '--bw-surface:' . between_words_get_color_value('between_words_light_surface') . ';';
    $css .= '--bw-card:' . between_words_get_color_value('between_words_light_card') . ';';
    $css .= '--bw-text:' . between_words_get_color_value('between_words_light_text') . ';';
    $css .= '--bw-muted:' . between_words_get_color_value('between_words_light_muted') . ';';
    $css .= '--bw-border:' . between_words_get_color_value('between_words_light_border') . ';';
    $css .= '--bw-accent:' . between_words_get_color_value('between_words_light_accent') . ';';
    $css .= '--bw-soft:#f1f1f1;';
    $css .= '}';
    $css .= 'html.bw-theme-dark {';
    $css .= '--bw-bg:' . between_words_get_color_value('between_words_dark_bg') . ';';
    $css .= '--bw-surface:' . between_words_get_color_value('between_words_dark_surface') . ';';
    $css .= '--bw-card:' . between_words_get_color_value('between_words_dark_card') . ';';
    $css .= '--bw-text:' . between_words_get_color_value('between_words_dark_text') . ';';
    $css .= '--bw-muted:' . between_words_get_color_value('between_words_dark_muted') . ';';
    $css .= '--bw-border:' . between_words_get_color_value('between_words_dark_border') . ';';
    $css .= '--bw-accent:' . between_words_get_color_value('between_words_dark_accent') . ';';
    $css .= '--bw-soft:#222222;';
    $css .= '}';
    $css .= 'html.bw-reader-mode {';
    $css .= '--bw-bg:' . between_words_get_color_value('between_words_reader_bg') . ';';
    $css .= '--bw-surface:' . between_words_get_color_value('between_words_reader_surface') . ';';
    $css .= '--bw-card:' . between_words_get_color_value('between_words_reader_card') . ';';
    $css .= '--bw-text:' . between_words_get_color_value('between_words_reader_text') . ';';
    $css .= '--bw-muted:' . between_words_get_color_value('between_words_reader_muted') . ';';
    $css .= '--bw-border:' . between_words_get_color_value('between_words_reader_border') . ';';
    $css .= '--bw-accent:' . between_words_get_color_value('between_words_reader_accent') . ';';
    $css .= '--bw-soft:#e7dbc0;';
    $css .= '}';

    return $css;
}

function between_words_get_posts_query_args(int $paged = 1): array
{
    return [
        'post_type' => 'post',
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
        'paged' => max(1, $paged),
    ];
}

function between_words_filter_frontend_queries(\WP_Query $query): void
{
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->is_home() || $query->is_search()) {
        $query->set('post_type', 'post');
    }
}
add_action('pre_get_posts', 'between_words_filter_frontend_queries');

function between_words_is_persian_locale(): bool
{
    $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
    return is_rtl() || strpos((string) $locale, 'fa') === 0;
}

function between_words_label(string $key): string
{
    $labels = [
        'home' => ['en' => 'Home', 'fa' => 'خانه'],
        'notes' => ['en' => 'Notes', 'fa' => 'یادداشت‌ها'],
        'podcast' => ['en' => 'Podcast', 'fa' => 'پادکست'],
        'about' => ['en' => 'About', 'fa' => 'درباره'],
        'about_section' => ['en' => 'About Between Words', 'fa' => 'درباره بین کلمات'],
        'read_more' => ['en' => 'Read more', 'fa' => 'بیشتر بخوانید'],
        'continue_reading' => ['en' => 'Continue reading', 'fa' => 'ادامه مطلب'],
        'latest_podcast' => ['en' => 'Latest podcast', 'fa' => 'آخرین پادکست'],
        'no_podcast' => ['en' => 'No podcast has been published yet.', 'fa' => 'هنوز پادکستی منتشر نشده است'],
        'all_podcast_episodes' => ['en' => 'All podcast episodes', 'fa' => 'همه پادکست‌ها'],
        'latest_notes' => ['en' => 'Latest notes', 'fa' => 'آخرین یادداشت‌ها'],
        'notes_archive' => ['en' => 'Notes archive', 'fa' => 'آرشیو یادداشت‌ها'],
        'view_full_archive' => ['en' => 'View full archive', 'fa' => 'مشاهده همه آرشیو'],
        'newsletter' => ['en' => 'Newsletter', 'fa' => 'خبرنامه'],
        'subscribe' => ['en' => 'Subscribe', 'fa' => 'عضویت'],
        'enter_email' => ['en' => 'Enter your email', 'fa' => 'ایمیل خود را وارد کنید'],
        'search' => ['en' => 'Search', 'fa' => 'جستجو'],
        'search_term' => ['en' => 'Search term', 'fa' => 'عبارت جستجو'],
        'close' => ['en' => 'Close', 'fa' => 'بستن'],
        'privacy' => ['en' => 'Privacy', 'fa' => 'حریم خصوصی'],
        'contact' => ['en' => 'Contact', 'fa' => 'تماس'],
        'menu' => ['en' => 'Menu', 'fa' => 'منو'],
        'primary_navigation' => ['en' => 'Primary navigation', 'fa' => 'ناوبری اصلی'],
        'skip_to_content' => ['en' => 'Skip to content', 'fa' => 'پرش به محتوا'],
        'page_not_found' => ['en' => 'Page not found', 'fa' => 'صفحه پیدا نشد'],
        'page_not_found_text' => ['en' => 'The page you are looking for may have moved or is no longer available.', 'fa' => 'صفحه‌ای که به دنبالش بودید جابه‌جا شده یا دیگر در دسترس نیست.'],
        'back_to_home' => ['en' => 'Back to home', 'fa' => 'بازگشت به صفحه اصلی'],
        'no_results' => ['en' => 'No results found.', 'fa' => 'نتیجه‌ای پیدا نشد.'],
        'results_for' => ['en' => 'Results for: %s', 'fa' => 'نتایج برای: %s'],
        'nothing_found' => ['en' => 'Nothing was found to display here.', 'fa' => 'موردی برای نمایش پیدا نشد.'],
        'no_posts' => ['en' => 'No posts have been published yet.', 'fa' => 'هنوز یادداشتی منتشر نشده است.'],
        'pagination' => ['en' => 'Pagination', 'fa' => 'صفحه‌بندی'],
        'previous' => ['en' => 'Previous', 'fa' => 'قبلی'],
        'next' => ['en' => 'Next', 'fa' => 'بعدی'],
        'more_notes' => ['en' => 'More notes', 'fa' => 'یادداشت‌های بیشتر'],
        'share' => ['en' => 'Share', 'fa' => 'اشتراک‌گذاری'],
        'link_copied' => ['en' => 'Link copied', 'fa' => 'لینک کپی شد'],
        'play_podcast' => ['en' => 'Play podcast', 'fa' => 'پخش پادکست'],
        'download_episode' => ['en' => 'Download episode', 'fa' => 'دانلود اپیزود'],
        'gallery' => ['en' => 'Gallery', 'fa' => 'گالری'],
        'images' => ['en' => 'Images', 'fa' => 'تصاویر'],
        'conversation' => ['en' => 'Conversation', 'fa' => 'گفت‌وگو'],
        'theme_modes' => ['en' => 'Theme modes', 'fa' => 'حالت‌های نمایش'],
        'change_display_mode' => ['en' => 'Change display mode', 'fa' => 'تغییر حالت نمایش'],
        'light_mode' => ['en' => 'Light', 'fa' => 'روشن'],
        'dark_mode' => ['en' => 'Dark', 'fa' => 'تاریک'],
        'reader_mode' => ['en' => 'Reader mode', 'fa' => 'حالت مطالعه'],
        'focus_mode' => ['en' => 'Focus mode', 'fa' => 'حالت تمرکز'],
        'exit_focus_mode' => ['en' => 'Exit focus mode', 'fa' => 'خروج از حالت تمرکز'],
        'breadcrumbs' => ['en' => 'Breadcrumbs', 'fa' => 'مسیر صفحه'],
        'posts_from' => ['en' => 'Posts from %s', 'fa' => 'نوشته‌هایی از %s'],
        'search_results_for' => ['en' => 'Search results for %s', 'fa' => 'نتایج جستجو برای %s'],
    ];

    $set = $labels[$key] ?? ['en' => $key, 'fa' => $key];
    return between_words_is_persian_locale() ? $set['fa'] : translate($set['en'], 'between-words');
}

function between_words_get_default_about_text(): string
{
    if (between_words_is_persian_locale()) {
        return 'اینجا جایی‌ست برای فکر کردن با صدای بلند. نوشته‌ها، روزمره‌ها، سوال‌ها، خاطره‌ها و چیزهایی که بین کلمات جا می‌مانند.';
    }

    return translate('A quiet place for thinking out loud through essays, notes, questions, memories, and the things that stay between words.', 'between-words');
}

function between_words_get_default_newsletter_text(): string
{
    if (between_words_is_persian_locale()) {
        return 'اگر دوست داری هر بار که یادداشتی تازه منتشر می‌شود باخبر شوی، ایمیل خودت را بگذار تا در تماس بمانیم.';
    }

    return translate('If you want to hear about new posts when they are published, leave your email and stay in touch.', 'between-words');
}

function between_words_get_posts_url(): string
{
    $posts_page_id = (int) get_option('page_for_posts');
    if ($posts_page_id) {
        $posts_page_url = get_permalink($posts_page_id);
        if ($posts_page_url) {
            return $posts_page_url;
        }
    }

    return home_url('/');
}

function between_words_get_category_archive_link(string $slug, string $fallback = ''): string
{
    $category = get_category_by_slug($slug);
    if ($category instanceof \WP_Term) {
        $link = get_category_link($category);
        if (!is_wp_error($link)) {
            return $link;
        }
    }

    return $fallback ?: between_words_get_posts_url();
}

function between_words_get_podcast_category_term(): ?\WP_Term
{
    static $term = false;

    if ($term !== false) {
        return $term instanceof \WP_Term ? $term : null;
    }

    $candidates = ['podcast', 'Podcast', 'پادکست'];

    foreach ($candidates as $candidate) {
        $category = get_category_by_slug($candidate);
        if ($category instanceof \WP_Term) {
            $term = $category;
            return $term;
        }
    }

    $terms = get_categories([
        'hide_empty' => false,
        'taxonomy' => 'category',
    ]);

    foreach ($terms as $category) {
        if (!$category instanceof \WP_Term) {
            continue;
        }

        $slug = function_exists('mb_strtolower') ? mb_strtolower($category->slug, 'UTF-8') : strtolower($category->slug);
        $name = function_exists('mb_strtolower') ? mb_strtolower($category->name, 'UTF-8') : strtolower($category->name);

        if (in_array($slug, ['podcast', 'پادکست'], true) || in_array($name, ['podcast', 'پادکست'], true)) {
            $term = $category;
            return $term;
        }
    }

    $term = null;

    return null;
}

function between_words_get_podcast_archive_link(string $fallback = ''): string
{
    $category = between_words_get_podcast_category_term();

    if ($category instanceof \WP_Term) {
        $link = get_category_link($category);
        if (!is_wp_error($link)) {
            return $link;
        }
    }

    return $fallback ?: between_words_get_posts_url();
}

function between_words_get_primary_menu_fallback(): array
{
    return [
        ['label' => between_words_label('notes'), 'url' => between_words_get_posts_url()],
        ['label' => between_words_label('podcast'), 'url' => between_words_get_podcast_archive_link(between_words_get_posts_url())],
        ['label' => between_words_label('about'), 'url' => get_theme_mod('between_words_about_link', home_url('/about/'))],
    ];
}

function between_words_render_primary_menu(): void
{
    $locations = get_nav_menu_locations();

    if (isset($locations['primary'])) {
        $items = wp_get_nav_menu_items($locations['primary']);
        if ($items) {
            foreach ($items as $item) {
                echo '<a href="' . esc_url($item->url) . '">' . esc_html($item->title) . '</a>';
            }
            return;
        }
    }

    foreach (between_words_get_primary_menu_fallback() as $item) {
        echo '<a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a>';
    }
}

function between_words_render_footer_links(): void
{
    $locations = get_nav_menu_locations();

    if (isset($locations['footer'])) {
        $items = wp_get_nav_menu_items($locations['footer']);
        if ($items) {
            $count = count($items);
            foreach ($items as $index => $item) {
                echo '<a href="' . esc_url($item->url) . '">' . esc_html($item->title) . '</a>';
                if ($index < ($count - 1)) {
                    echo '<span>&middot;</span>';
                }
            }
            return;
        }
    }

    $fallback_links = [
        ['label' => get_theme_mod('between_words_footer_privacy_label', between_words_label('privacy')), 'url' => get_theme_mod('between_words_footer_privacy_url', home_url('/privacy-policy/'))],
        ['label' => get_theme_mod('between_words_footer_contact_label', between_words_label('contact')), 'url' => get_theme_mod('between_words_footer_contact_url', home_url('/contact/'))],
    ];

    foreach ($fallback_links as $index => $item) {
        echo '<a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a>';
        if ($index === 0) {
            echo '<span>&middot;</span>';
        }
    }
}

function between_words_get_social_links(): array
{
    $email = get_theme_mod('between_words_social_email', '');

    return [
        'telegram' => get_theme_mod('between_words_social_telegram', ''),
        'instagram' => get_theme_mod('between_words_social_instagram', ''),
        'email' => $email ? 'mailto:' . antispambot($email) : '',
    ];
}

function between_words_get_date_format(string $context = 'post'): string
{
    if ($context === 'current') {
        return between_words_is_persian_locale() ? 'l j F Y' : get_option('date_format');
    }

    return between_words_is_persian_locale() ? 'j F Y' : get_option('date_format');
}

function between_words_get_display_date(?int $post_id = null): string
{
    $post_id = (int) ($post_id ?: get_the_ID());
    return $post_id > 0 ? get_the_date(between_words_get_date_format('post'), $post_id) : wp_date(between_words_get_date_format('post'));
}

function between_words_get_current_date(): string
{
    return wp_date(between_words_get_date_format('current'));
}

function between_words_get_post_datetime_iso(?int $post_id = null, string $modified = 'published'): string
{
    $post_id = (int) ($post_id ?: get_the_ID());
    if ($post_id <= 0) {
        return '';
    }

    return $modified === 'modified' ? get_the_modified_date(DATE_W3C, $post_id) : get_the_date(DATE_W3C, $post_id);
}

function between_words_render_post_date(?int $post_id = null, string $class = 'post-date'): void
{
    $post_id = (int) ($post_id ?: get_the_ID());
    if ($post_id <= 0) {
        return;
    }

    echo '<time class="' . esc_attr($class) . '" datetime="' . esc_attr(between_words_get_post_datetime_iso($post_id)) . '">' . esc_html(between_words_get_display_date($post_id)) . '</time>';
}

function between_words_format_year(): string
{
    return wp_date('Y');
}

function between_words_get_reading_time(?int $post_id = null): string
{
    static $cache = [];

    $post = get_post($post_id);
    if (!$post instanceof \WP_Post) {
        return '';
    }

    if (isset($cache[$post->ID])) {
        return $cache[$post->ID];
    }

    $content = wp_strip_all_tags($post->post_content . ' ' . $post->post_excerpt);
    $word_count = str_word_count(wp_specialchars_decode($content), 0);

    if ($word_count === 0 && function_exists('mb_strlen')) {
        $word_count = (int) ceil(mb_strlen($content) / 5);
    }

    $minutes = max(1, (int) ceil($word_count / 180));
    $formatted = number_format_i18n($minutes);

    if (between_words_is_persian_locale()) {
        return sprintf('%s دقیقه مطالعه', $formatted);
    }

    $cache[$post->ID] = sprintf(_n('%s minute read', '%s minutes read', $minutes, 'between-words'), $formatted);

    return $cache[$post->ID];
}

function between_words_post_has_any_category($categories, int $post_id): bool
{
    return has_category($categories, $post_id);
}

function between_words_content_has_audio(?int $post_id = null): bool
{
    $post = get_post($post_id);
    if (!$post instanceof \WP_Post) {
        return false;
    }

    if (has_block('core/audio', $post)) {
        return true;
    }

    if (stripos($post->post_content, '<audio') !== false || stripos($post->post_content, '[audio') !== false) {
        return true;
    }

    return between_words_get_post_audio_url($post->ID) !== '';
}

function between_words_post_has_podcast_category(?int $post_id = null): bool
{
    $post_id = (int) ($post_id ?: get_the_ID());
    if ($post_id <= 0) {
        return false;
    }

    $terms = get_the_terms($post_id, 'category');
    if (empty($terms) || is_wp_error($terms)) {
        return false;
    }

    foreach ($terms as $term) {
        if (!$term instanceof \WP_Term) {
            continue;
        }

        $slug = function_exists('mb_strtolower') ? mb_strtolower($term->slug, 'UTF-8') : strtolower($term->slug);
        $name = function_exists('mb_strtolower') ? mb_strtolower($term->name, 'UTF-8') : strtolower($term->name);

        if (in_array($slug, ['podcast', 'پادکست'], true) || in_array($name, ['podcast', 'پادکست'], true)) {
            return true;
        }
    }

    return false;
}

function between_words_is_podcast_post(?int $post_id = null): bool
{
    $post_id = (int) ($post_id ?: get_the_ID());
    if ($post_id <= 0) {
        return false;
    }

    return get_post_format($post_id) === 'audio'
        || between_words_post_has_podcast_category($post_id)
        || between_words_content_has_audio($post_id);
}

function between_words_is_gallery_post(?int $post_id = null): bool
{
    $post_id = (int) ($post_id ?: get_the_ID());
    return get_post_format($post_id) === 'gallery' || between_words_post_has_any_category(['gallery'], $post_id);
}

function between_words_is_image_post(?int $post_id = null): bool
{
    $post_id = (int) ($post_id ?: get_the_ID());
    return get_post_format($post_id) === 'image' || between_words_post_has_any_category(['image'], $post_id);
}

function between_words_is_conversation_post(?int $post_id = null): bool
{
    $post_id = (int) ($post_id ?: get_the_ID());
    return get_post_format($post_id) === 'chat' || between_words_post_has_any_category(['conversation', 'گفت‌وگو'], $post_id);
}

function between_words_get_content_kind(?int $post_id = null): string
{
    static $cache = [];

    $post_id = (int) ($post_id ?: get_the_ID());
    if ($post_id <= 0) {
        return 'standard';
    }

    if (isset($cache[$post_id])) {
        return $cache[$post_id];
    }

    if (between_words_is_podcast_post($post_id)) {
        $cache[$post_id] = 'audio';
        return $cache[$post_id];
    }

    if (between_words_is_gallery_post($post_id)) {
        $cache[$post_id] = 'gallery';
        return $cache[$post_id];
    }

    if (between_words_is_image_post($post_id)) {
        $cache[$post_id] = 'image';
        return $cache[$post_id];
    }

    if (between_words_is_conversation_post($post_id)) {
        $cache[$post_id] = 'chat';
        return $cache[$post_id];
    }

    $cache[$post_id] = 'standard';

    return $cache[$post_id];
}

function between_words_get_card_template(?int $post_id = null): string
{
    $kind = between_words_get_content_kind($post_id);

    if ($kind === 'audio') {
        return 'podcast-card';
    }

    if ($kind === 'gallery') {
        return 'gallery-card';
    }

    if ($kind === 'image') {
        return 'image-card';
    }

    return 'post-card';
}

function between_words_parse_blocks_recursive(array $blocks, array &$state): void
{
    foreach ($blocks as $block) {
        $name = $block['blockName'] ?? '';
        $attrs = $block['attrs'] ?? [];
        $inner_blocks = $block['innerBlocks'] ?? [];
        $inner_html = $block['innerHTML'] ?? '';

        if ($name === 'core/audio') {
            if (!empty($attrs['src'])) {
                $state['audio_urls'][] = (string) $attrs['src'];
            }
            if (!empty($attrs['id'])) {
                $attachment_url = wp_get_attachment_url((int) $attrs['id']);
                if ($attachment_url) {
                    $state['audio_urls'][] = $attachment_url;
                }
            }
        }

        if ($name === 'core/gallery' && !empty($attrs['ids']) && is_array($attrs['ids'])) {
            foreach ($attrs['ids'] as $image_id) {
                $state['image_ids'][] = absint($image_id);
            }
        }

        if ($name === 'core/image') {
            if (!empty($attrs['id'])) {
                $state['image_ids'][] = absint($attrs['id']);
            } elseif (!empty($attrs['url'])) {
                $image_id = attachment_url_to_postid((string) $attrs['url']);
                if ($image_id) {
                    $state['image_ids'][] = $image_id;
                }
            }
        }

        if ($inner_html) {
            if (preg_match_all('/<audio[^>]+src=["\']([^"\']+)["\']/i', $inner_html, $matches)) {
                foreach ($matches[1] as $audio_url) {
                    $state['audio_urls'][] = $audio_url;
                }
            }
            if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $inner_html, $image_matches)) {
                foreach ($image_matches[1] as $image_url) {
                    $image_id = attachment_url_to_postid($image_url);
                    if ($image_id) {
                        $state['image_ids'][] = $image_id;
                    }
                }
            }
        }

        if ($inner_blocks) {
            between_words_parse_blocks_recursive($inner_blocks, $state);
        }
    }
}

function between_words_get_content_media_state(?int $post_id = null): array
{
    static $cache = [];

    $post = get_post($post_id);
    if (!$post instanceof \WP_Post) {
        return ['audio_urls' => [], 'image_ids' => []];
    }

    if (isset($cache[$post->ID])) {
        return $cache[$post->ID];
    }

    $state = ['audio_urls' => [], 'image_ids' => []];
    between_words_parse_blocks_recursive(parse_blocks($post->post_content), $state);

    if (preg_match_all('/\[audio[^\]]*(?:src|mp3|m4a|ogg|wav)=["\']([^"\']+)["\']/i', $post->post_content, $shortcode_matches)) {
        foreach ($shortcode_matches[1] as $audio_url) {
            $state['audio_urls'][] = $audio_url;
        }
    }

    if (preg_match_all('/<audio[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $html_matches)) {
        foreach ($html_matches[1] as $audio_url) {
            $state['audio_urls'][] = $audio_url;
        }
    }

    $state['audio_urls'] = array_values(array_unique(array_filter(array_map('esc_url_raw', $state['audio_urls']))));
    $state['image_ids'] = array_values(array_unique(array_filter(array_map('absint', $state['image_ids']))));

    $cache[$post->ID] = $state;

    return $cache[$post->ID];
}

function between_words_get_audio_url(?int $post_id = null): string
{
    $post_id = (int) ($post_id ?: get_the_ID());
    if ($post_id <= 0) {
        return '';
    }

    $candidates = [
        (string) get_post_meta($post_id, '_between_words_audio_file_url', true),
        (string) get_post_meta($post_id, '_between_words_audio_url', true),
        (string) get_post_meta($post_id, 'audio_file_url', true),
        (string) get_post_meta($post_id, 'audio_url', true),
    ];

    foreach ($candidates as $candidate) {
        $candidate = esc_url_raw($candidate);
        if ($candidate !== '') {
            return $candidate;
        }
    }

    return '';
}

function between_words_get_post_audio_url(?int $post_id = null): string
{
    $post_id = (int) ($post_id ?: get_the_ID());
    if ($post_id <= 0) {
        return '';
    }

    $state = between_words_get_content_media_state($post_id);
    if (!empty($state['audio_urls'][0])) {
        return $state['audio_urls'][0];
    }

    return between_words_get_audio_url($post_id);
}

function between_words_get_gallery_image_ids_from_content(?int $post_id = null): array
{
    $post_id = (int) ($post_id ?: get_the_ID());
    if ($post_id <= 0) {
        return [];
    }

    $state = between_words_get_content_media_state($post_id);
    return $state['image_ids'];
}

function between_words_get_first_image_id_from_content(?int $post_id = null): int
{
    $image_ids = between_words_get_gallery_image_ids_from_content($post_id);
    return $image_ids ? (int) $image_ids[0] : 0;
}

function between_words_get_podcast_duration(?int $post_id = null): string
{
    return (string) get_post_meta((int) $post_id, '_between_words_duration', true);
}

function between_words_get_duration_minutes(string $duration): int
{
    $duration = trim($duration);
    if ($duration === '') {
        return 0;
    }

    if (preg_match('/^(?:(\d+):)?(\d{1,2}):(\d{2})$/', $duration, $matches)) {
        $hours = isset($matches[3]) && $matches[1] !== '' ? (int) $matches[1] : 0;
        $minutes = (int) $matches[count($matches) === 4 ? 2 : 1];
        $seconds = (int) $matches[count($matches) === 4 ? 3 : 2];

        return max(1, (int) ceil((($hours * 3600) + ($minutes * 60) + $seconds) / 60));
    }

    if (preg_match('/(\d+)/u', $duration, $matches)) {
        return max(1, (int) $matches[1]);
    }

    return 0;
}

function between_words_get_post_time_label(?int $post_id = null): string
{
    static $cache = [];

    $post_id = (int) ($post_id ?: get_the_ID());
    if ($post_id <= 0) {
        return '';
    }

    if (isset($cache[$post_id])) {
        return $cache[$post_id];
    }

    if (between_words_is_podcast_post($post_id)) {
        $duration = trim(between_words_get_podcast_duration($post_id));

        if ($duration !== '') {
            $minutes = between_words_get_duration_minutes($duration);
            if ($minutes > 0) {
                $formatted = number_format_i18n($minutes);

                if (between_words_is_persian_locale()) {
                    $cache[$post_id] = sprintf('%s دقیقه شنیدن', $formatted);
                    return $cache[$post_id];
                }

                $cache[$post_id] = sprintf('%s min listen', $formatted);
                return $cache[$post_id];
            }

            $cache[$post_id] = $duration;
            return $cache[$post_id];
        }

        $reading_label = between_words_get_reading_time($post_id);

        if (between_words_is_persian_locale()) {
            $cache[$post_id] = preg_replace('/مطالعه/u', 'شنیدن', $reading_label, 1) ?: $reading_label;
            return $cache[$post_id];
        }

        $cache[$post_id] = str_replace(['minutes read', 'minute read'], 'min listen', $reading_label);
        return $cache[$post_id];
    }

    $cache[$post_id] = between_words_get_reading_time($post_id);

    return $cache[$post_id];
}

function between_words_get_card_meta_text(?int $post_id = null): string
{
    static $cache = [];

    $post_id = (int) ($post_id ?: get_the_ID());
    if ($post_id <= 0) {
        return '';
    }

    if (isset($cache[$post_id])) {
        return $cache[$post_id];
    }

    $kind = between_words_get_content_kind($post_id);

    if ($kind === 'audio') {
        $cache[$post_id] = between_words_get_post_time_label($post_id);
        return $cache[$post_id];
    }

    if ($kind === 'gallery') {
        $image_count = count(between_words_get_gallery_image_ids_from_content($post_id));
        if ($image_count > 0) {
            $formatted = number_format_i18n($image_count);
            return between_words_is_persian_locale() ? sprintf('%s تصویر', $formatted) : sprintf(_n('%s image', '%s images', $image_count, 'between-words'), $formatted);
        }
    }

    $cache[$post_id] = between_words_get_post_time_label($post_id);

    return $cache[$post_id];
}

function between_words_get_placeholder_variant(?int $post_id = null): int
{
    $seed = (int) ($post_id ?: get_the_ID() ?: 1);
    return (($seed - 1) % 4) + 1;
}

function between_words_get_placeholder_markup(?int $post_id = null): string
{
    $variant = between_words_get_placeholder_variant($post_id);
    return '<div class="placeholder placeholder-' . (int) $variant . '" aria-hidden="true"></div>';
}

function between_words_the_placeholder(?int $post_id = null): void
{
    echo between_words_get_placeholder_markup($post_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function between_words_get_card_image_id(?int $post_id = null): int
{
    static $cache = [];

    $post_id = (int) ($post_id ?: get_the_ID());
    if ($post_id <= 0) {
        return 0;
    }

    if (isset($cache[$post_id])) {
        return $cache[$post_id];
    }

    $thumbnail_id = get_post_thumbnail_id($post_id);
    if ($thumbnail_id) {
        $cache[$post_id] = (int) $thumbnail_id;
        return $cache[$post_id];
    }

    $kind = between_words_get_content_kind($post_id);
    if ($kind === 'gallery' || $kind === 'image') {
        $cache[$post_id] = between_words_get_first_image_id_from_content($post_id);
        return $cache[$post_id];
    }

    $cache[$post_id] = 0;

    return $cache[$post_id];
}

function between_words_get_image_aspect_type(int $attachment_id): string
{
    $attachment_id = absint($attachment_id);
    if ($attachment_id <= 0) {
        return 'crop-square';
    }

    $metadata = wp_get_attachment_metadata($attachment_id);
    $width = isset($metadata['width']) ? (int) $metadata['width'] : 0;
    $height = isset($metadata['height']) ? (int) $metadata['height'] : 0;

    if ($width <= 0 || $height <= 0) {
        return 'crop-square';
    }

    $ratio = $width / $height;

    if ($ratio >= 0.95 && $ratio <= 1.05) {
        return 'square';
    }

    if ($ratio >= 0.70 && $ratio <= 0.80) {
        return 'portrait-3-4';
    }

    return 'crop-square';
}

function between_words_get_theme_image_args(int $attachment_id, string $context = 'card'): array
{
    $aspect_type = $context === 'hero' ? 'hero' : between_words_get_image_aspect_type($attachment_id);
    $class_map = [
        'square' => 'bw-image-aspect-square',
        'portrait-3-4' => 'bw-image-aspect-portrait',
        'crop-square' => 'bw-image-aspect-crop-square',
        'hero' => 'bw-image-aspect-hero',
    ];

    return [
        'size' => $context === 'hero' ? 'between-words-hero' : 'large',
        'aspect_type' => $aspect_type,
        'aspect_class' => $class_map[$aspect_type] ?? $class_map['crop-square'],
    ];
}

function between_words_get_card_image_aspect_class(?int $post_id = null, string $context = 'card'): string
{
    $post_id = (int) ($post_id ?: get_the_ID());
    $image_id = between_words_get_card_image_id($post_id);

    if ($image_id <= 0) {
        return 'bw-image-aspect-crop-square';
    }

    $image_args = between_words_get_theme_image_args($image_id, $context);

    return $image_args['aspect_class'];
}

function between_words_get_image_attributes(int $post_id, int $image_id, string $size = 'between-words-card', array $args = []): array
{
    static $card_lcp_claimed = false;

    $context = $args['context'] ?? 'card';
    $attrs = [
        'decoding' => 'async',
    ];

    if ($context === 'hero') {
        $attrs['loading'] = 'eager';
        $attrs['fetchpriority'] = 'high';
        $attrs['sizes'] = '(max-width: 860px) calc(100vw - 32px), 740px';
    } elseif ($context === 'gallery-strip') {
        $attrs['loading'] = 'lazy';
        $attrs['sizes'] = '(max-width: 860px) calc((100vw - 52px) / 3), 230px';
    } else {
        $attrs['sizes'] = '(max-width: 860px) calc(100vw - 32px), (max-width: 1050px) 330px, 370px';
        $is_first_card = !$card_lcp_claimed && !is_admin() && !is_single() && !is_paged() && in_the_loop();
        if ($is_first_card) {
            $attrs['loading'] = 'eager';
            $attrs['fetchpriority'] = 'high';
            $card_lcp_claimed = true;
        } else {
            $attrs['loading'] = 'lazy';
        }
    }

    if (isset($args['attr']) && is_array($args['attr'])) {
        $attrs = array_merge($attrs, $args['attr']);
    }

    $theme_image_args = between_words_get_theme_image_args($image_id, $context);
    $attrs['class'] = trim(($attrs['class'] ?? '') . ' ' . $theme_image_args['aspect_class']);

    if (empty($attrs['alt'])) {
        $attrs = array_merge($attrs, between_words_get_image_attr_fallback($post_id, $image_id));
    }

    return $attrs;
}

function between_words_render_attachment_image(int $image_id, string $size = 'between-words-card', array $args = []): void
{
    if ($image_id <= 0) {
        return;
    }

    $post_id = isset($args['post_id']) ? (int) $args['post_id'] : 0;
    echo wp_get_attachment_image($image_id, $size, false, between_words_get_image_attributes($post_id, $image_id, $size, $args)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function between_words_render_card_image(?int $post_id = null, string $size = 'between-words-card', array $args = []): void
{
    $post_id = (int) ($post_id ?: get_the_ID());
    $image_id = between_words_get_card_image_id($post_id);

    if ($image_id) {
        $context = isset($args['context']) ? (string) $args['context'] : 'card';
        $theme_image_args = between_words_get_theme_image_args($image_id, $context);
        between_words_render_attachment_image($image_id, $theme_image_args['size'], $args + ['post_id' => $post_id, 'context' => $context]);
        return;
    }

    between_words_the_placeholder($post_id);
}

function between_words_get_latest_podcast_post(): ?\WP_Post
{
    static $cached_post = false;

    if ($cached_post !== false) {
        return $cached_post instanceof \WP_Post ? $cached_post : null;
    }

    $transient_key = 'between_words_latest_podcast_post_id';
    $use_cache = !(defined('WP_DEBUG') && WP_DEBUG);

    if ($use_cache) {
        $cached_post_id = (int) get_transient($transient_key);
        if ($cached_post_id > 0) {
            $cached_post = get_post($cached_post_id);
            if ($cached_post instanceof \WP_Post && $cached_post->post_status === 'publish' && between_words_is_podcast_post($cached_post->ID)) {
                return $cached_post;
            }
        }
    }

    $podcast_category = between_words_get_podcast_category_term();
    $tax_query = [
        'relation' => 'OR',
        ['taxonomy' => 'post_format', 'field' => 'slug', 'terms' => ['post-format-audio']],
        ['taxonomy' => 'category', 'field' => 'slug', 'terms' => ['podcast']],
    ];

    if ($podcast_category instanceof \WP_Term) {
        $tax_query[] = ['taxonomy' => 'category', 'field' => 'term_id', 'terms' => [$podcast_category->term_id]];
    }

    $query = new WP_Query([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 3,
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'tax_query' => $tax_query,
        'update_post_meta_cache' => false,
    ]);

    if ($query->have_posts()) {
        foreach ($query->posts as $post) {
            if ($post instanceof \WP_Post && between_words_is_podcast_post($post->ID)) {
                $cached_post = $post;
                if ($use_cache) {
                    set_transient($transient_key, (int) $cached_post->ID, HOUR_IN_SECONDS);
                }
                return $cached_post;
            }
        }
    }

    $fallback_posts = get_posts([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 30,
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'update_post_term_cache' => false,
    ]);

    foreach ($fallback_posts as $post) {
        if ($post instanceof \WP_Post && between_words_is_podcast_post($post->ID)) {
            if ($use_cache) {
                set_transient($transient_key, (int) $post->ID, HOUR_IN_SECONDS);
            }
            $cached_post = $post;
            return $cached_post;
        }
    }

    delete_transient($transient_key);
    $cached_post = null;

    return null;
}

function between_words_get_latest_podcast(): ?\WP_Post
{
    return between_words_get_latest_podcast_post();
}

function between_words_flush_latest_podcast_cache(): void
{
    delete_transient('between_words_latest_podcast_post_id');
}

function between_words_clear_latest_podcast_cache(...$args): void
{
    between_words_flush_latest_podcast_cache();
}
add_action('save_post_post', 'between_words_clear_latest_podcast_cache');
add_action('deleted_post', 'between_words_clear_latest_podcast_cache');
add_action('trashed_post', 'between_words_clear_latest_podcast_cache');
add_action('untrashed_post', 'between_words_clear_latest_podcast_cache');
add_action('transition_post_status', 'between_words_clear_latest_podcast_cache');

function between_words_clear_latest_podcast_cache_on_terms(int $object_id, array $terms, array $tt_ids, string $taxonomy): void
{
    if (!in_array($taxonomy, ['category', 'post_format'], true)) {
        return;
    }

    if (get_post_type($object_id) !== 'post') {
        return;
    }

    between_words_clear_latest_podcast_cache();
}
add_action('set_object_terms', 'between_words_clear_latest_podcast_cache_on_terms', 10, 4);

function between_words_clear_latest_podcast_cache_on_post_format(int $post_id, string $format): void
{
    if (get_post_type($post_id) !== 'post') {
        return;
    }

    between_words_clear_latest_podcast_cache();
}
add_action('set_post_format', 'between_words_clear_latest_podcast_cache_on_post_format', 10, 2);
add_action('created_category', 'between_words_clear_latest_podcast_cache');
add_action('edited_category', 'between_words_clear_latest_podcast_cache');
add_action('delete_category', 'between_words_clear_latest_podcast_cache');

function between_words_get_previous_episode(?int $post_id = null): ?\WP_Post
{
    return between_words_get_adjacent_podcast_post($post_id, 'previous');
}

function between_words_get_next_episode(?int $post_id = null): ?\WP_Post
{
    return between_words_get_adjacent_podcast_post($post_id, 'next');
}

function between_words_get_gallery_image_ids(?int $post_id = null): array
{
    return between_words_get_gallery_image_ids_from_content($post_id);
}

function between_words_get_single_template_slug(?int $post_id = null): string
{
    $kind = between_words_get_content_kind($post_id);

    if ($kind === 'audio') {
        return 'single-audio';
    }

    if ($kind === 'gallery') {
        return 'single-gallery';
    }

    if ($kind === 'image') {
        return 'single-image';
    }

    if ($kind === 'chat') {
        return 'single-chat';
    }

    return 'single-standard';
}

function between_words_directional_arrow(string $context = 'forward'): string
{
    $is_rtl = is_rtl();
    $points_left = $context === 'backward' ? !$is_rtl : $is_rtl;
    $transform = $points_left ? '' : ' style="transform: scaleX(-1);"';

    return '<span class="link-arrow" aria-hidden="true"><svg viewBox="0 0 16 16"' . $transform . '><path d="M12.5 8H3.5"></path><path d="M7.5 4 3.5 8l4 4"></path></svg></span>';
}

function between_words_strip_audio_blocks(array $blocks): array
{
    $filtered_blocks = [];

    foreach ($blocks as $block) {
        if (($block['blockName'] ?? '') === 'core/audio') {
            continue;
        }

        if (!empty($block['innerBlocks']) && is_array($block['innerBlocks'])) {
            $block['innerBlocks'] = between_words_strip_audio_blocks($block['innerBlocks']);
        }

        $filtered_blocks[] = $block;
    }

    return $filtered_blocks;
}

function between_words_remove_audio_from_content(string $content): string
{
    if (function_exists('has_blocks') && has_blocks($content)) {
        $content = serialize_blocks(between_words_strip_audio_blocks(parse_blocks($content)));
    }

    $content = preg_replace('/\[audio[^\]]*\](?:\[\/audio\])?/i', '', $content);
    $content = preg_replace('/<audio\b[^>]*>.*?<\/audio>/is', '', $content);
    $content = preg_replace('/<audio\b[^>]*\/?>/is', '', $content);

    return trim((string) $content);
}

function between_words_render_waveform(): string
{
    return '<div class="waveform" data-audio-progress role="slider" tabindex="0" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><canvas data-audio-waveform aria-hidden="true"></canvas><div class="waveform-progress" data-audio-progress-fill></div></div>';
}

function between_words_render_podcast_player(?int $post_id = null, array $args = []): void
{
    $post_id = $post_id === null ? (int) get_the_ID() : (int) $post_id;
    $audio_url = $post_id ? between_words_get_post_audio_url($post_id) : '';
    $duration = $post_id ? between_words_get_podcast_duration($post_id) : '';
    $has_audio = $audio_url !== '';
    $resume_label = between_words_is_persian_locale() ? 'ادامه از %s' : 'Resume from %s';
    $permalink = $post_id ? get_permalink($post_id) : '';
    $thumbnail_id = $post_id ? get_post_thumbnail_id($post_id) : 0;
    $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'between-words-sidebar') : '';
    $thumbnail_alt = $thumbnail_id ? get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) : '';
    if ($thumbnail_alt === '' && $post_id) {
        $thumbnail_alt = get_the_title($post_id);
    }
    $player_classes = ['podcast-player'];

    if (!$has_audio) {
        $player_classes[] = 'is-disabled';
    }

    if (!empty($args['variant'])) {
        $player_classes[] = 'podcast-player--' . sanitize_html_class((string) $args['variant']);
    }

    if (!empty($args['class'])) {
        $player_classes[] = sanitize_html_class((string) $args['class']);
    }

    $player_class = implode(' ', array_filter(array_map('sanitize_html_class', $player_classes)));
    ?>
    <div class="<?php echo esc_attr($player_class); ?>" data-audio-player data-post-id="<?php echo esc_attr($post_id); ?>" data-audio-title="<?php echo esc_attr($post_id ? get_the_title($post_id) : ''); ?>" data-audio-permalink="<?php echo esc_url($permalink); ?>" data-thumbnail-url="<?php echo esc_url($thumbnail_url ?: ''); ?>" data-thumbnail-alt="<?php echo esc_attr((string) $thumbnail_alt); ?>" data-resume-template="<?php echo esc_attr($resume_label); ?>" data-initial-time="<?php echo esc_attr($duration ?: '00:00'); ?>">
        <div class="player-row">
            <button class="play-button<?php echo $has_audio ? '' : ' is-disabled'; ?>" type="button" data-audio-toggle aria-label="<?php echo esc_attr(between_words_label('play_podcast')); ?>"<?php echo $has_audio ? '' : ' aria-disabled="true"'; ?>></button>
            <?php echo between_words_render_waveform(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php if ($has_audio) : ?>
                <a class="download-button" href="<?php echo esc_url($audio_url); ?>" download aria-label="<?php echo esc_attr(between_words_label('download_episode')); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M5 21h14"></path></svg>
                </a>
                <?php if ($thumbnail_url && $permalink) : ?>
                    <a class="persistent-player-thumb" data-audio-thumbnail href="<?php echo esc_url($permalink); ?>" aria-hidden="true" tabindex="-1">
                        <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr((string) $thumbnail_alt); ?>">
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="podcast-duration" data-audio-time><?php echo esc_html($duration ?: '00:00'); ?></div>
        <div class="podcast-resume" data-audio-resume hidden></div>
        <?php if ($permalink && $post_id) : ?>
            <a class="persistent-player-title" data-audio-title-link href="<?php echo esc_url($permalink); ?>"><?php echo esc_html(get_the_title($post_id)); ?></a>
        <?php endif; ?>
        <?php if ($has_audio) : ?>
            <audio class="podcast-audio" preload="metadata" src="<?php echo esc_url($audio_url); ?>"></audio>
        <?php endif; ?>
    </div>
    <?php
}

function between_words_get_adjacent_podcast_post(?int $post_id = null, string $direction = 'previous'): ?\WP_Post
{
    static $cache = [];

    $post_id = (int) ($post_id ?: get_the_ID());
    $current_post = get_post($post_id);
    if (!$current_post instanceof \WP_Post) {
        return null;
    }

    $cache_key = $post_id . ':' . $direction;
    if (array_key_exists($cache_key, $cache)) {
        return $cache[$cache_key];
    }

    $date_query = $direction === 'previous'
        ? [['before' => $current_post->post_date, 'inclusive' => false]]
        : [['after' => $current_post->post_date, 'inclusive' => false]];

    $posts = get_posts([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 12,
        'post__not_in' => [$post_id],
        'orderby' => 'date',
        'order' => $direction === 'previous' ? 'DESC' : 'ASC',
        'date_query' => $date_query,
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
    ]);

    $cache[$cache_key] = null;

    foreach ($posts as $candidate) {
        if ($candidate instanceof \WP_Post && between_words_is_podcast_post($candidate->ID)) {
            $cache[$cache_key] = $candidate;
            break;
        }
    }

    return $cache[$cache_key];
}

function between_words_render_archive_items(int $limit = 6): void
{
    global $wpdb;

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT YEAR(post_date) AS y, MONTH(post_date) AS m, COUNT(ID) AS posts
            FROM {$wpdb->posts}
            WHERE post_type = %s AND post_status = 'publish'
            GROUP BY YEAR(post_date), MONTH(post_date)
            ORDER BY post_date DESC
            LIMIT %d",
            'post',
            $limit
        )
    );

    if (!$results) {
        return;
    }

    foreach ($results as $result) {
        $timestamp = mktime(0, 0, 0, (int) $result->m, 1, (int) $result->y);
        $label = wp_date('F Y', $timestamp);
        $url = get_month_link((int) $result->y, (int) $result->m);
        printf('<li><a href="%1$s">%2$s</a></li>', esc_url($url), esc_html(sprintf('%s (%s)', $label, number_format_i18n((int) $result->posts))));
    }
}

function between_words_get_breadcrumb_items(): array
{
    $items = [
        [
            'label' => between_words_label('home'),
            'url' => home_url('/'),
        ],
    ];

    if (is_home()) {
        $items[] = ['label' => get_the_title((int) get_option('page_for_posts')) ?: between_words_label('notes'), 'url' => ''];
        return $items;
    }

    if (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        if ($term instanceof \WP_Term) {
            $items[] = ['label' => single_term_title('', false), 'url' => ''];
        }
        return $items;
    }

    if (is_archive()) {
        $items[] = ['label' => wp_strip_all_tags(get_the_archive_title()), 'url' => ''];
        return $items;
    }

    if (is_search()) {
        $items[] = ['label' => sprintf(between_words_label('results_for'), get_search_query()), 'url' => ''];
        return $items;
    }

    if (is_page()) {
        $items[] = ['label' => get_the_title(), 'url' => ''];
        return $items;
    }

    if (is_single()) {
        $primary_category = get_the_category();
        if (!empty($primary_category[0])) {
            $items[] = [
                'label' => $primary_category[0]->name,
                'url' => get_category_link($primary_category[0]),
            ];
        }

        $items[] = ['label' => get_the_title(), 'url' => ''];
    }

    return $items;
}

function between_words_render_breadcrumbs(): void
{
    if (!between_words_is_theme_breadcrumbs_enabled() || is_front_page()) {
        return;
    }

    $items = between_words_get_breadcrumb_items();
    if (count($items) < 2) {
        return;
    }

    echo '<nav class="bw-breadcrumbs" aria-label="' . esc_attr(between_words_label('breadcrumbs')) . '"><ol class="bw-breadcrumbs-list">';

    foreach ($items as $item) {
        echo '<li class="bw-breadcrumbs-item">';
        if (!empty($item['url'])) {
            echo '<a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a>';
        } else {
            echo '<span aria-current="page">' . esc_html($item['label']) . '</span>';
        }
        echo '</li>';
    }

    echo '</ol></nav>';
}

function between_words_render_pagination(?\WP_Query $query = null): void
{
    global $wp_query;

    $query = $query ?: $wp_query;
    $links = paginate_links([
        'type' => 'array',
        'total' => max(1, (int) $query->max_num_pages),
        'current' => max(1, get_query_var('paged'), get_query_var('page')),
        'prev_text' => between_words_label('previous'),
        'next_text' => between_words_label('next'),
    ]);

    if (!$links) {
        if ((is_home() || is_front_page()) && !is_paged()) {
            echo '<div class="load-more-wrap"><a class="load-more" href="' . esc_url(between_words_get_posts_url()) . '">' . esc_html(between_words_label('more_notes')) . '</a></div>';
        }
        return;
    }

    echo '<nav class="pagination" aria-label="' . esc_attr(between_words_label('pagination')) . '">';
    foreach ($links as $link) {
        echo wp_kses_post($link);
    }
    echo '</nav>';
}

function between_words_filter_body_classes(array $classes): array
{
    if (!is_active_sidebar('between-words-primary-sidebar')) {
        $classes[] = 'bw-no-sidebar';
    }

    return $classes;
}
add_filter('body_class', 'between_words_filter_body_classes');

function between_words_render_mode_controls(): void
{
    $decrease_text_label = between_words_is_persian_locale() ? 'کوچک‌تر کردن متن' : 'Decrease text size';
    $reset_text_label = between_words_is_persian_locale() ? 'اندازه پیش‌فرض متن' : 'Reset text size';
    $increase_text_label = between_words_is_persian_locale() ? 'بزرگ‌تر کردن متن' : 'Increase text size';
    $font_controls_label = between_words_is_persian_locale() ? 'کنترل اندازه متن' : 'Text size controls';
    ?>
    <div class="theme-controls" aria-label="<?php echo esc_attr(between_words_label('theme_modes')); ?>">
        <button type="button" data-theme-mode="light"><?php echo esc_html(between_words_label('light_mode')); ?></button>
        <button type="button" data-theme-mode="dark"><?php echo esc_html(between_words_label('dark_mode')); ?></button>
        <button type="button" data-theme-toggle="reader"><?php echo esc_html(between_words_label('reader_mode')); ?></button>
        <button type="button" data-theme-toggle="focus"><?php echo esc_html(between_words_label('focus_mode')); ?></button>
    </div>
    <?php
}

function between_words_render_reading_tools(): void
{
    if (!is_singular(['post', 'page'])) {
        return;
    }

    $decrease_text_label = between_words_is_persian_locale() ? 'کوچک‌تر کردن متن' : 'Decrease text size';
    $reset_text_label = between_words_is_persian_locale() ? 'اندازه پیش‌فرض متن' : 'Reset text size';
    $increase_text_label = between_words_is_persian_locale() ? 'بزرگ‌تر کردن متن' : 'Increase text size';
    $font_controls_label = between_words_is_persian_locale() ? 'کنترل اندازه متن' : 'Text size controls';
    ?>
    <div class="single-reading-tools" data-reading-tools aria-label="<?php echo esc_attr($font_controls_label); ?>">
        <button type="button" class="reading-tool-button" data-font-scale="decrease" aria-label="<?php echo esc_attr($decrease_text_label); ?>">A-</button>
        <button type="button" class="reading-tool-button" data-font-scale="reset" aria-label="<?php echo esc_attr($reset_text_label); ?>">A</button>
        <button type="button" class="reading-tool-button" data-font-scale="increase" aria-label="<?php echo esc_attr($increase_text_label); ?>">A+</button>
    </div>
    <?php
}

function between_words_render_post_page_links(): void
{
    $links = wp_link_pages([
            'echo' => 0,
            'before' => '<nav class="post-page-links" aria-label="' . esc_attr__('Post pages', 'between-words') . '">',
            'after' => '</nav>',
            'link_before' => '<span>',
            'link_after' => '</span>',
        ]);

    if ($links === '') {
        return;
    }

    echo '<div class="page-links">' . $links . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function between_words_render_comments_section(): void
{
    if (comments_open() || get_comments_number()) {
        comments_template();
    }
}

function between_words_filter_wp_robots(array $robots): array
{
    if (is_search() && get_theme_mod('between_words_seo_noindex_search', true)) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }

    if (is_404() && get_theme_mod('between_words_seo_noindex_404', true)) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }

    if (get_theme_mod('between_words_seo_noindex_paged_archives', false) && is_archive() && is_paged()) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }

    return $robots;
}
add_filter('wp_robots', 'between_words_filter_wp_robots');

function between_words_output_meta_tags(): void
{
    if (!between_words_is_theme_seo_meta_enabled()) {
        return;
    }

    $description = between_words_get_meta_description();
    if ($description !== '' && !is_404()) {
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }

    $canonical = between_words_get_canonical_url();
    if (between_words_should_output_theme_canonical()) {
        echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    }
}
add_action('wp_head', 'between_words_output_meta_tags', 1);

function between_words_output_social_meta(): void
{
    if (is_404() || !between_words_is_theme_seo_meta_enabled() || !(bool) get_theme_mod('between_words_seo_enable_social_meta', true)) {
        return;
    }

    $title = wp_get_document_title();
    $description = between_words_get_meta_description();
    $url = between_words_get_canonical_url() ?: between_words_get_current_request_url();
    $image = is_singular() ? between_words_get_seo_image_data(get_the_ID()) : [];
    $type = is_singular() ? 'article' : 'website';
    $locale = str_replace('-', '_', between_words_get_schema_language());

    echo '<meta property="og:type" content="' . esc_attr($type) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    if ($description !== '') {
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    }
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    echo '<meta property="og:locale" content="' . esc_attr($locale) . '">' . "\n";

    if (!empty($image['url'])) {
        echo '<meta property="og:image" content="' . esc_url($image['url']) . '">' . "\n";
        if (!empty($image['width'])) {
            echo '<meta property="og:image:width" content="' . (int) $image['width'] . '">' . "\n";
        }
        if (!empty($image['height'])) {
            echo '<meta property="og:image:height" content="' . (int) $image['height'] . '">' . "\n";
        }
    }

    if (is_singular('post')) {
        echo '<meta property="article:published_time" content="' . esc_attr(between_words_get_post_datetime_iso(get_the_ID())) . '">' . "\n";
        echo '<meta property="article:modified_time" content="' . esc_attr(between_words_get_post_datetime_iso(get_the_ID(), 'modified')) . '">' . "\n";
        echo '<meta property="article:author" content="' . esc_attr(get_the_author_meta('display_name', (int) get_post_field('post_author', get_the_ID()))) . '">' . "\n";
    }

    echo '<meta name="twitter:card" content="' . esc_attr(!empty($image['url']) ? 'summary_large_image' : 'summary') . '">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    if ($description !== '') {
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
    }
    if (!empty($image['url'])) {
        echo '<meta name="twitter:image" content="' . esc_url($image['url']) . '">' . "\n";
    }
}
add_action('wp_head', 'between_words_output_social_meta', 2);

function between_words_output_schema(): void
{
    if (!between_words_is_theme_schema_enabled() || is_404()) {
        return;
    }

    $canonical = between_words_get_canonical_url() ?: between_words_get_current_request_url();
    $description = between_words_get_meta_description();
    $language = between_words_get_schema_language();
    $graph = [];

    $website_id = home_url('/#website');
    $graph[] = [
        '@type' => 'WebSite',
        '@id' => $website_id,
        'url' => home_url('/'),
        'name' => get_bloginfo('name'),
        'description' => get_bloginfo('description'),
        'inLanguage' => $language,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => home_url('/?s={search_term_string}'),
            'query-input' => 'required name=search_term_string',
        ],
    ];

    $page_type = 'WebPage';
    if (is_archive()) {
        $page_type = 'CollectionPage';
    } elseif (is_search()) {
        $page_type = 'SearchResultsPage';
    }

    $webpage = [
        '@type' => $page_type,
        '@id' => $canonical . '#webpage',
        'url' => $canonical,
        'name' => wp_get_document_title(),
        'description' => $description,
        'isPartOf' => ['@id' => $website_id],
        'inLanguage' => $language,
    ];

    if (is_singular()) {
        $image = between_words_get_seo_image_data(get_the_ID());
        $webpage['datePublished'] = between_words_get_post_datetime_iso(get_the_ID());
        $webpage['dateModified'] = between_words_get_post_datetime_iso(get_the_ID(), 'modified');
        if (!empty($image['url'])) {
            $webpage['primaryImageOfPage'] = [
                '@type' => 'ImageObject',
                'url' => $image['url'],
            ];
        }
    }

    $graph[] = array_filter($webpage);

    if (is_single() && get_post_type() === 'post') {
        $post_id = get_the_ID();
        $image = between_words_get_seo_image_data($post_id);
        $article = [
            '@type' => 'BlogPosting',
            '@id' => get_permalink($post_id) . '#article',
            'mainEntityOfPage' => ['@id' => $canonical . '#webpage'],
            'headline' => get_the_title($post_id),
            'description' => $description,
            'datePublished' => between_words_get_post_datetime_iso($post_id),
            'dateModified' => between_words_get_post_datetime_iso($post_id, 'modified'),
            'author' => [
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', (int) get_post_field('post_author', $post_id)),
            ],
            'inLanguage' => $language,
        ];

        $categories = get_the_category($post_id);
        if (!empty($categories[0])) {
            $article['articleSection'] = $categories[0]->name;
        }

        $tags = wp_get_post_tags($post_id, ['fields' => 'names']);
        if (!empty($tags)) {
            $article['keywords'] = implode(', ', $tags);
        }

        if (!empty($image['url'])) {
            $article['image'] = [
                '@type' => 'ImageObject',
                'url' => $image['url'],
            ];
        }

        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            $logo_data = wp_get_attachment_image_src((int) $logo_id, 'full');
            if ($logo_data) {
                $article['publisher'] = [
                    '@type' => 'Organization',
                    'name' => get_bloginfo('name'),
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => $logo_data[0],
                    ],
                ];
            }
        }

        if (between_words_is_podcast_post($post_id)) {
            $audio_url = between_words_get_post_audio_url($post_id);
            if ($audio_url !== '') {
                $audio = [
                    '@type' => 'AudioObject',
                    'contentUrl' => $audio_url,
                    'name' => get_the_title($post_id),
                ];
                $duration = between_words_format_iso8601_duration(between_words_get_podcast_duration($post_id));
                if ($duration !== '') {
                    $audio['duration'] = $duration;
                }
                $article['associatedMedia'] = $audio;
            }
        }

        if (between_words_is_gallery_post($post_id)) {
            $gallery_images = [];
            foreach (between_words_get_gallery_image_ids($post_id) as $gallery_image_id) {
                $gallery_src = wp_get_attachment_image_src((int) $gallery_image_id, 'full');
                if ($gallery_src) {
                    $gallery_images[] = $gallery_src[0];
                }
            }
            if ($gallery_images) {
                $article['image'] = $gallery_images;
            }
        }

        $graph[] = array_filter($article);
    }

    if (between_words_is_theme_breadcrumbs_enabled() && !is_front_page()) {
        $items = between_words_get_breadcrumb_items();
        if (count($items) > 1) {
            $crumbs = [];
            foreach ($items as $index => $item) {
                $crumb = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['label'],
                ];
                if (!empty($item['url'])) {
                    $crumb['item'] = $item['url'];
                }
                $crumbs[] = $crumb;
            }

            $graph[] = [
                '@type' => 'BreadcrumbList',
                '@id' => $canonical . '#breadcrumb',
                'itemListElement' => $crumbs,
            ];
        }
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => array_values(array_filter($graph)),
    ];

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}
add_action('wp_head', 'between_words_output_schema', 3);
