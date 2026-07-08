<?php
if (!defined('ABSPATH')) {
    exit;
}

$about_text = get_theme_mod('between_words_about_text', between_words_get_default_about_text());
$about_link = get_theme_mod('between_words_about_link', home_url('/about/'));
$latest_podcast = between_words_get_latest_podcast_post();
?>
<aside id="site-sidebar" class="sidebar" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr(between_words_label('menu')); ?>">
    <div class="drawer-header">
        <button class="overlay-close" type="button" data-drawer-close aria-label="<?php echo esc_attr(between_words_label('close')); ?>"></button>
    </div>

    <nav class="drawer-nav" aria-label="<?php echo esc_attr(between_words_label('primary_navigation')); ?>">
        <?php between_words_render_primary_menu(); ?>
    </nav>

    <form class="drawer-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
        <input class="search-field" type="search" name="s" placeholder="<?php echo esc_attr(between_words_label('search_term')); ?>">
        <button class="search-submit" type="submit"><?php echo esc_html(between_words_label('search')); ?></button>
    </form>

    <?php between_words_render_mode_controls(); ?>

    <?php if (is_single() && get_post_type() === 'post') : ?>
        <?php get_template_part('template-parts/sidebar', 'latest-post'); ?>
    <?php endif; ?>

    <section class="side-section">
        <h2 class="side-title"><?php echo esc_html(between_words_label('about_section')); ?></h2>
        <p class="side-text"><?php echo wp_kses_post($about_text); ?></p>
        <a class="side-link" href="<?php echo esc_url($about_link); ?>">
            <span class="link-text"><?php echo esc_html(between_words_label('read_more')); ?></span>
            <?php echo between_words_directional_arrow('forward'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </a>
    </section>

    <?php get_template_part('template-parts/sidebar', 'podcast', null, ['podcast' => $latest_podcast]); ?>
    <?php get_template_part('template-parts/sidebar', 'archive'); ?>
    <?php get_template_part('template-parts/newsletter'); ?>
</aside>
