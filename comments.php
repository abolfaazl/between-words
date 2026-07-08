<?php
if (!defined('ABSPATH')) {
    exit;
}

if (post_password_required()) {
    return;
}
?>
<section id="comments" class="comments-area">
    <?php if (have_comments()) : ?>
        <h2 class="side-title comments-title">
            <?php
            $comment_count = get_comments_number();
            printf(
                esc_html(
                    _n('%s comment', '%s comments', $comment_count, 'between-words')
                ),
                esc_html(number_format_i18n($comment_count))
            );
            ?>
        </h2>

        <ol class="comment-list">
            <?php
            wp_list_comments([
                'style' => 'ol',
                'short_ping' => true,
                'avatar_size' => 48,
            ]);
            ?>
        </ol>

        <?php the_comments_navigation(); ?>
    <?php endif; ?>

    <?php
    comment_form([
        'title_reply' => esc_html__('Leave a comment', 'between-words'),
        'title_reply_before' => '<h2 id="reply-title" class="side-title comment-reply-title">',
        'title_reply_after' => '</h2>',
        'class_submit' => 'search-submit',
    ]);
    ?>
</section>
