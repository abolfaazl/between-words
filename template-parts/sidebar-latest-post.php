<?php
if (!defined('ABSPATH')) {
    exit;
}

$recent_posts = get_posts([
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 3,
    'post__not_in' => [get_queried_object_id()],
    'no_found_rows' => true,
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
]);

if (!$recent_posts) {
    return;
}
?>
<section class="side-section">
    <h2 class="side-title"><?php echo esc_html(between_words_label('latest_notes')); ?></h2>
    <div class="sidebar-latest-list">
        <?php foreach ($recent_posts as $recent_post) : ?>
            <a class="sidebar-latest-link" href="<?php echo esc_url(get_permalink($recent_post)); ?>">
                <?php between_words_render_post_date($recent_post->ID); ?>
                <h3><?php echo esc_html(get_the_title($recent_post)); ?></h3>
            </a>
        <?php endforeach; ?>
    </div>
</section>
