<?php
if (!defined('ABSPATH')) {
    exit;
}

$context = '';
if (isset($args) && is_array($args) && isset($args['between_words_context'])) {
    $context = sanitize_key((string) $args['between_words_context']);
}

$form_class = 'search-form-inline';
$input_data_attr = '';
$submit_aria_label = between_words_label('search');

if ($context === 'overlay') {
    $form_class = 'search-overlay-form';
    $input_data_attr = ' data-search-input';
} elseif ($context === 'drawer') {
    $form_class = 'drawer-search';
}
?>
<form class="<?php echo esc_attr($form_class); ?>" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
    <label class="screen-reader-text" for="bw-search-field-<?php echo esc_attr($context ?: 'default'); ?>">
        <?php echo esc_html(between_words_label('search_term')); ?>
    </label>
    <input id="bw-search-field-<?php echo esc_attr($context ?: 'default'); ?>" class="search-field" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php echo esc_attr(between_words_label('search_term')); ?>"<?php echo $input_data_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <button class="search-submit" type="submit" aria-label="<?php echo esc_attr($submit_aria_label); ?>"><?php echo esc_html(between_words_label('search')); ?></button>
</form>
