<?php
if (!defined('ABSPATH')) {
    exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text skip-link" href="#main-content"><?php echo esc_html(between_words_label('skip_to_content')); ?></a>
<div class="page">
    <header class="site-header">
        <div class="header-left">
            <button class="search-icon" type="button" data-search-open aria-label="<?php echo esc_attr(between_words_label('search')); ?>"></button>

            <nav class="main-nav" aria-label="<?php echo esc_attr(between_words_label('primary_navigation')); ?>">
                <?php between_words_render_primary_menu(); ?>
            </nav>
        </div>

        <div class="brand">
            <?php if (is_front_page() || is_home()) : ?>
                <h1><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a></h1>
            <?php else : ?>
                <p class="brand-title"><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a></p>
            <?php endif; ?>
            <p><?php bloginfo('description'); ?></p>
        </div>

        <div class="header-right">
            <div class="current-date"><?php echo esc_html(between_words_get_current_date()); ?></div>

            <div class="header-controls">
                <button class="header-icon-button bw-theme-cycle" type="button" data-theme-cycle aria-label="<?php echo esc_attr(between_words_label('change_display_mode')); ?>">
                    <svg class="theme-icon theme-icon-light" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="4"></circle>
                        <path d="M12 2v3"></path>
                        <path d="M12 19v3"></path>
                        <path d="M4.2 4.2l2.1 2.1"></path>
                        <path d="M17.7 17.7l2.1 2.1"></path>
                        <path d="M2 12h3"></path>
                        <path d="M19 12h3"></path>
                        <path d="M4.2 19.8l2.1-2.1"></path>
                        <path d="M17.7 6.3l2.1-2.1"></path>
                    </svg>
                    <svg class="theme-icon theme-icon-dark" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20 14.5A8 8 0 0 1 9.5 4a7 7 0 1 0 10.5 10.5Z"></path>
                    </svg>
                    <svg class="theme-icon theme-icon-reader" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 4h10a4 4 0 0 1 4 4v12H9a4 4 0 0 0-4-4Z"></path>
                        <path d="M9 8h6"></path>
                        <path d="M9 12h5"></path>
                    </svg>
                </button>
                <button class="header-icon-button bw-focus-toggle" type="button" data-focus-toggle data-focus-label="<?php echo esc_attr(between_words_label('focus_mode')); ?>" data-exit-label="<?php echo esc_attr(between_words_label('exit_focus_mode')); ?>" aria-label="<?php echo esc_attr(between_words_label('focus_mode')); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M8 4H5a1 1 0 0 0-1 1v3"></path>
                        <path d="M16 4h3a1 1 0 0 1 1 1v3"></path>
                        <path d="M8 20H5a1 1 0 0 1-1-1v-3"></path>
                        <path d="M16 20h3a1 1 0 0 0 1-1v-3"></path>
                        <circle cx="12" cy="12" r="2.5"></circle>
                    </svg>
                </button>
            </div>

            <button class="hamburger" type="button" data-drawer-open aria-controls="site-sidebar" aria-expanded="false" aria-label="<?php echo esc_attr(between_words_label('menu')); ?>">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <div class="search-overlay" data-search-overlay role="dialog" aria-modal="true" aria-hidden="true" aria-label="<?php echo esc_attr(between_words_label('search')); ?>">
        <div class="search-panel" data-search-panel>
            <button class="overlay-close" type="button" data-search-close aria-label="<?php echo esc_attr(between_words_label('close')); ?>"></button>
            <form class="search-overlay-form" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <input class="search-field" type="search" name="s" placeholder="<?php echo esc_attr(between_words_label('search_term')); ?>" data-search-input>
                <button class="search-submit" type="submit"><?php echo esc_html(between_words_label('search')); ?></button>
            </form>
        </div>
    </div>
    <div class="drawer-backdrop" data-drawer-backdrop></div>
