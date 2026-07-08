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
            <form class="search-form-inline" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <input class="search-field" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php echo esc_attr(between_words_label('search_term')); ?>">
                <button class="search-submit" type="submit"><?php echo esc_html(between_words_label('search')); ?></button>
            </form>
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
