<?php
get_header();
?>
<main id="main-content" class="single-layout">
    <?php get_sidebar(); ?>

    <section class="single-main">
        <div class="not-found-wrap">
            <header class="archive-header">
                <?php between_words_render_breadcrumbs(); ?>
                <div class="archive-kicker">404</div>
                <h1 class="archive-title"><?php echo esc_html(between_words_label('page_not_found')); ?></h1>
                <p class="archive-description"><?php echo esc_html(between_words_label('page_not_found_text')); ?></p>
            </header>

            <a class="side-link" href="<?php echo esc_url(home_url('/')); ?>">
                <?php echo between_words_directional_arrow('backward'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <span class="link-text"><?php echo esc_html(between_words_label('back_to_home')); ?></span>
            </a>
        </div>
    </section>
</main>
<?php
get_footer();
