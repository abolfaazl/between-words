<?php
get_header();
?>
<main id="main-content" class="single-layout">
    <?php get_sidebar(); ?>

    <section class="single-main">
        <?php between_words_render_reading_tools(); ?>
        <?php
        while (have_posts()) :
            the_post();
            get_template_part('template-parts/' . between_words_get_single_template_slug(get_the_ID()));
        endwhile;
        ?>
    </section>
</main>
<?php
get_footer();
