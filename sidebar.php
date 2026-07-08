<?php
if (!defined('ABSPATH')) {
    exit;
}

$has_primary_sidebar = is_active_sidebar('between-words-primary-sidebar');
$sidebar_classes = 'sidebar';

if (!$has_primary_sidebar) {
    $sidebar_classes .= ' bw-sidebar-empty';
}
?>
<aside id="site-sidebar" class="<?php echo esc_attr($sidebar_classes); ?>" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr(between_words_label('menu')); ?>">
    <div class="drawer-header">
        <button class="overlay-close" type="button" data-drawer-close aria-label="<?php echo esc_attr(between_words_label('close')); ?>"></button>
    </div>

    <nav class="drawer-nav" aria-label="<?php echo esc_attr(between_words_label('primary_navigation')); ?>">
        <?php between_words_render_primary_menu(); ?>
    </nav>

    <?php get_search_form(['between_words_context' => 'drawer']); ?>

    <?php between_words_render_mode_controls(); ?>

    <?php if ($has_primary_sidebar) : ?>
        <?php dynamic_sidebar('between-words-primary-sidebar'); ?>
    <?php endif; ?>
</aside>
