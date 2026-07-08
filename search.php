<?php
get_header();
?>
<main id="main-content" class="main-layout">
    <?php get_sidebar(); ?>

    <section class="posts">
        <header class="archive-header">
            <?php between_words_render_breadcrumbs(); ?>
            <div class="archive-kicker"><?php echo esc_html(between_words_label('search')); ?></div>
            <h1 class="archive-title"><?php echo esc_html(sprintf(between_words_label('results_for'), get_search_query())); ?></h1>
            <?php get_search_form(['between_words_context' => 'inline']); ?>
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
            <p class="empty-state"><?php echo esc_html(between_words_label('no_results')); ?></p>
            <?php
        endif;
        ?>
    </section>
</main>
<?php
get_footer();
