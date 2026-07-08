<?php
if (!defined('ABSPATH')) {
    exit;
}

$newsletter_text = get_theme_mod('between_words_newsletter_text', between_words_get_default_newsletter_text());
$newsletter_shortcode = trim((string) get_theme_mod('between_words_newsletter_shortcode', ''));
?>
<section class="side-section">
    <h2 class="side-title"><?php echo esc_html(between_words_label('newsletter')); ?></h2>

    <p class="newsletter-text"><?php echo wp_kses_post($newsletter_text); ?></p>

    <?php if ($newsletter_shortcode) : ?>
        <?php echo do_shortcode($newsletter_shortcode); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php else : ?>
        <form class="newsletter-form">
            <input type="email" placeholder="<?php echo esc_attr(between_words_label('enter_email')); ?>" aria-label="<?php echo esc_attr(between_words_label('enter_email')); ?>">
            <button type="button"><?php echo esc_html(between_words_label('subscribe')); ?></button>
        </form>
    <?php endif; ?>
</section>
