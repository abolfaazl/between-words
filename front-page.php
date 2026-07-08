<?php
get_header();
?>
<main id="main-content" class="main-layout">
    <?php get_sidebar(); ?>

    <section class="posts">
        <?php
        $front_query = new WP_Query(between_words_get_posts_query_args(max(1, get_query_var('paged'), get_query_var('page'))));

        if ($front_query->have_posts()) :
            while ($front_query->have_posts()) :
                $front_query->the_post();
                get_template_part('template-parts/content', between_words_get_card_template());
            endwhile;
            between_words_render_pagination($front_query);
            wp_reset_postdata();
        else :
            ?>
            <p class="empty-state"><?php echo esc_html(between_words_label('no_posts')); ?></p>
            <?php
        endif;
        ?>
    </section>
</main>
<?php
get_footer();
