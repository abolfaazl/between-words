<?php
if (!defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();
$display_date = between_words_get_display_date($post_id);
$meta_text = between_words_get_card_meta_text($post_id);
$permalink = get_permalink($post_id);
$title = get_the_title($post_id);
?>
<article <?php post_class('post-card'); ?>>
    <a class="post-image" href="<?php echo esc_url($permalink); ?>" aria-label="<?php echo esc_attr($title); ?>">
        <?php between_words_render_card_image($post_id, 'between-words-card'); ?>
    </a>

    <div class="post-content">
        <?php between_words_render_post_date($post_id); ?>
        <h2 class="post-title"><a href="<?php echo esc_url($permalink); ?>" rel="bookmark"><?php echo esc_html($title); ?></a></h2>
        <p class="post-excerpt"><?php echo esc_html(get_the_excerpt() ?: wp_trim_words(wp_strip_all_tags(get_the_content()), 30, '...')); ?></p>
        <div class="post-footer">
            <a class="read-more" href="<?php echo esc_url($permalink); ?>">
                <span class="link-text"><?php echo esc_html(between_words_label('continue_reading')); ?></span>
                <?php echo between_words_directional_arrow('forward'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </a>
            <span class="read-time"><?php echo esc_html($meta_text); ?></span>
        </div>
    </div>
</article>
