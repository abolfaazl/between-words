<?php
get_header();
?>
<main id="main-content" class="main-layout">
    <?php get_sidebar(); ?>

    <section class="posts">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : ?>
                <?php the_post(); ?>
                <?php get_template_part('template-parts/content', between_words_get_card_template()); ?>
            <?php endwhile; ?>
            <?php between_words_render_pagination(); ?>
        <?php else : ?>
            <p class="empty-state"><?php echo esc_html(between_words_label('no_posts')); ?></p>
        <?php endif; ?>
    </section>
</main>
<?php
get_footer();
