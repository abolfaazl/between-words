<?php
get_header();
?>
<main id="main-content" class="single-layout">
    <?php get_sidebar(); ?>

    <section class="single-main">
        <?php between_words_render_reading_tools(); ?>
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class('single-entry'); ?>>
                <header class="single-header">
                    <?php between_words_render_breadcrumbs(); ?>
                    <?php between_words_render_post_date(get_the_ID()); ?>
                    <h1 class="single-title"><?php the_title(); ?></h1>
                    <div class="single-divider"></div>
                </header>

                <?php if (has_post_thumbnail()) : ?>
                    <div class="single-featured">
                        <div class="single-featured-image">
                            <?php between_words_render_card_image(get_the_ID(), 'between-words-hero', ['context' => 'hero']); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="page-content">
                    <?php the_content(); ?>
                    <?php between_words_render_post_page_links(); ?>
                </div>

                <?php between_words_render_comments_section(); ?>
            </article>
        <?php endwhile; ?>
    </section>
</main>
<?php
get_footer();
