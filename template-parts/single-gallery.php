<?php
if (!defined('ABSPATH')) {
    exit;
}

$gallery_image_ids = between_words_get_gallery_image_ids(get_the_ID());
$primary_image_id = between_words_get_card_image_id(get_the_ID());

if ($primary_image_id && $gallery_image_ids && (int) $gallery_image_ids[0] === $primary_image_id) {
    $gallery_image_ids = array_slice($gallery_image_ids, 1);
}
?>
<article <?php post_class('single-entry'); ?>>
    <header class="single-header">
        <?php between_words_render_breadcrumbs(); ?>
        <?php between_words_render_post_date(get_the_ID()); ?>
        <h1 class="single-title"><?php the_title(); ?></h1>
        <div class="single-divider"></div>
    </header>

    <div class="single-featured">
        <div class="single-featured-image">
            <?php between_words_render_card_image(get_the_ID(), 'between-words-hero', ['context' => 'hero']); ?>
        </div>
    </div>

    <?php if ($gallery_image_ids) : ?>
        <div class="gallery-strip">
            <?php foreach ($gallery_image_ids as $gallery_image_id) : ?>
                <?php between_words_render_attachment_image((int) $gallery_image_id, 'between-words-card', ['context' => 'gallery-strip', 'post_id' => get_the_ID()]); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="single-content">
        <?php the_content(); ?>
    </div>

    <div class="single-meta">
        <div class="single-meta-right"></div>
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
