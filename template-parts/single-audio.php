<?php
if (!defined('ABSPATH')) {
    exit;
}

$previous_episode = between_words_get_previous_episode(get_the_ID());
$next_episode = between_words_get_next_episode(get_the_ID());
$audio_safe_content = between_words_remove_audio_from_content((string) get_post_field('post_content', get_the_ID()));
?>
<article <?php post_class('single-entry'); ?>>
    <header class="single-header">
        <?php between_words_render_breadcrumbs(); ?>
        <?php between_words_render_post_date(get_the_ID()); ?>
        <h1 class="single-title"><?php the_title(); ?></h1>
        <div class="single-divider"></div>
    </header>

    <section class="podcast-card">
        <h2 class="podcast-name"><?php the_title(); ?></h2>
        <time class="podcast-date" datetime="<?php echo esc_attr(between_words_get_post_datetime_iso(get_the_ID())); ?>"><?php echo esc_html(between_words_get_display_date(get_the_ID())); ?></time>
        <?php between_words_render_podcast_player(get_the_ID(), ['variant' => 'single']); ?>
    </section>

    <div class="single-featured">
        <div class="single-featured-image">
            <?php between_words_render_card_image(get_the_ID(), 'between-words-hero', ['context' => 'hero']); ?>
        </div>
    </div>

    <div class="single-content">
        <?php echo apply_filters('the_content', $audio_safe_content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>

    <div class="single-meta">
        <div class="single-meta-right">
            <?php if ($previous_episode) : ?>
                <a class="read-more" href="<?php echo esc_url(get_permalink($previous_episode)); ?>">
                    <span class="link-text"><?php echo esc_html(get_the_title($previous_episode)); ?></span>
                    <?php echo between_words_directional_arrow('backward'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </a>
            <?php endif; ?>
            <?php if ($next_episode) : ?>
                <a class="read-more" href="<?php echo esc_url(get_permalink($next_episode)); ?>">
                    <span class="link-text"><?php echo esc_html(get_the_title($next_episode)); ?></span>
                    <?php echo between_words_directional_arrow('forward'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </a>
            <?php endif; ?>
        </div>
        <div class="single-meta-left">
            <button class="focus-mode-link" type="button" data-theme-toggle="focus"><?php echo esc_html(between_words_label('focus_mode')); ?></button>
            <button class="meta-icon-link" type="button" data-share-button data-share-title="<?php echo esc_attr(get_the_title()); ?>" data-share-url="<?php echo esc_url(get_permalink()); ?>" aria-label="<?php echo esc_attr(between_words_label('share')); ?>">
                <svg viewBox="0 0 24 24">
                    <path d="M22 2 11 13"></path>
                    <path d="M22 2 15 22 11 13 2 9 22 2Z"></path>
                </svg>
            </button>
            <span class="share-feedback" data-share-feedback><?php echo esc_html(between_words_label('link_copied')); ?></span>
        </div>
    </div>
</article>
