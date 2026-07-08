<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="side-section">
    <h2 class="side-title"><?php echo esc_html(between_words_label('notes_archive')); ?></h2>

    <ul class="archive-list">
        <?php between_words_render_archive_items(5); ?>
    </ul>

    <a class="side-link" href="<?php echo esc_url(between_words_get_posts_url()); ?>">
        <span class="link-text"><?php echo esc_html(between_words_label('view_full_archive')); ?></span>
        <?php echo between_words_directional_arrow('forward'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </a>
</section>
