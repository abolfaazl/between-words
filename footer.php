<?php
if (!defined('ABSPATH')) {
    exit;
}

$social_links = between_words_get_social_links();
?>
    <footer class="site-footer">
        <div class="footer-icons">
            <a href="<?php echo esc_url($social_links['telegram'] ?: '#'); ?>"<?php echo $social_links['telegram'] ? '' : ' aria-disabled="true"'; ?> aria-label="<?php echo esc_attr__('Telegram', 'between-words'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M22 2 11 13"></path>
                    <path d="M22 2 15 22 11 13 2 9 22 2Z"></path>
                </svg>
            </a>

            <a href="<?php echo esc_url($social_links['instagram'] ?: '#'); ?>"<?php echo $social_links['instagram'] ? '' : ' aria-disabled="true"'; ?> aria-label="<?php echo esc_attr__('Instagram', 'between-words'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="4" y="4" width="16" height="16" rx="4"></rect>
                    <circle cx="12" cy="12" r="3.5"></circle>
                    <circle cx="17" cy="7" r="1"></circle>
                </svg>
            </a>

            <a href="<?php echo esc_url($social_links['email'] ?: '#'); ?>"<?php echo $social_links['email'] ? '' : ' aria-disabled="true"'; ?> aria-label="<?php echo esc_attr__('Email', 'between-words'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 6h16v12H4z"></path>
                    <path d="m4 7 8 6 8-6"></path>
                </svg>
            </a>
        </div>

        <div class="footer-copy"><?php echo esc_html(get_bloginfo('name') . ' ' . between_words_format_year()); ?></div>

        <div class="footer-links">
            <?php between_words_render_footer_links(); ?>
        </div>
    </footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
