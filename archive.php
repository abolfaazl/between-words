<?php
get_header();
?>
<main id="main-content" class="main-layout">
    <?php get_sidebar(); ?>

    <section class="posts">
        <header class="archive-header">
            <?php between_words_render_breadcrumbs(); ?>
            <div class="archive-kicker"><?php echo esc_html(post_type_archive_title('', false) ?: single_term_title('', false)); ?></div>
            <h1 class="archive-title"><?php the_archive_title(); ?></h1>
            <?php the_archive_description('<p class="archive-description">', '</p>'); ?>
        </header>

        <?php
        if (have_posts()) :
            while (have_posts()) :
                the_post();
                get_template_part('template-parts/content', between_words_get_card_template());
            endwhile;
            between_words_render_pagination();
        else :
            ?>
            <p class="empty-state"><?php echo esc_html(between_words_label('nothing_found')); ?></p>
            <?php
        endif;
        ?>
    </section>
</main>
<?php
get_footer();
