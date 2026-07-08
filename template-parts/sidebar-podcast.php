<?php
if (!defined('ABSPATH')) {
    exit;
}

$podcast = $args['podcast'] ?? null;
?>
<section class="side-section">
    <h2 class="side-title"><?php echo esc_html(between_words_label('latest_podcast')); ?></h2>

    <div class="podcast-card">
        <?php if ($podcast instanceof \WP_Post) : ?>
            <h3 class="podcast-name"><a href="<?php echo esc_url(get_permalink($podcast)); ?>"><?php echo esc_html(get_the_title($podcast)); ?></a></h3>
            <time class="podcast-date" datetime="<?php echo esc_attr(between_words_get_post_datetime_iso($podcast->ID)); ?>"><?php echo esc_html(between_words_get_display_date($podcast->ID)); ?></time>
            <?php between_words_render_podcast_player($podcast->ID); ?>
        <?php else : ?>
            <h3 class="podcast-name"><?php echo esc_html(between_words_label('no_podcast')); ?></h3>
            <div class="podcast-date"><?php echo esc_html(between_words_get_current_date()); ?></div>
            <?php between_words_render_podcast_player(0); ?>
        <?php endif; ?>
    </div>

    <a class="side-link" href="<?php echo esc_url(between_words_get_category_archive_link('podcast', between_words_get_posts_url())); ?>">
        <span class="link-text"><?php echo esc_html(between_words_label('all_podcast_episodes')); ?></span>
        <?php echo between_words_directional_arrow('forward'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </a>
</section>
