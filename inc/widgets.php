<?php
if (!defined('ABSPATH')) {
    exit;
}

function between_words_register_sidebars(): void
{
    register_sidebar([
        'name' => __('Primary Sidebar', 'between-words'),
        'id' => 'between-words-primary-sidebar',
        'description' => __('Widgets shown in the main sidebar and mobile drawer.', 'between-words'),
        'before_widget' => '<section class="side-section sidebar-section widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h2 class="side-title sidebar-title">',
        'after_title' => '</h2>',
    ]);
}
add_action('widgets_init', 'between_words_register_sidebars');

function between_words_render_latest_podcast_widget_content(): void
{
    $podcast = between_words_get_latest_podcast_post();
    ?>
    <div class="podcast-card">
        <?php if ($podcast instanceof \WP_Post) : ?>
            <h3 class="podcast-name"><a href="<?php echo esc_url(get_permalink($podcast)); ?>"><?php echo esc_html(get_the_title($podcast)); ?></a></h3>
            <time class="podcast-date" datetime="<?php echo esc_attr(between_words_get_post_datetime_iso($podcast->ID)); ?>"><?php echo esc_html(between_words_get_display_date($podcast->ID)); ?></time>
            <?php between_words_render_podcast_player($podcast->ID); ?>
        <?php else : ?>
            <h3 class="podcast-name"><?php echo esc_html(between_words_label('no_podcast')); ?></h3>
            <div class="podcast-date"><?php echo esc_html(between_words_get_current_date()); ?></div>
            <?php between_words_render_podcast_player(0); ?>
        <?php endif; ?>
    </div>

    <a class="side-link" href="<?php echo esc_url(between_words_get_podcast_archive_link(between_words_get_posts_url())); ?>">
        <span class="link-text"><?php echo esc_html(between_words_label('all_podcast_episodes')); ?></span>
        <?php echo between_words_directional_arrow('forward'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </a>
    <?php
}

class Between_Words_Latest_Podcast_Widget extends \WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'between_words_latest_podcast',
            __('Between Words: Latest Podcast', 'between-words'),
            ['description' => __('Displays the latest detected podcast post.', 'between-words')]
        );
    }

    public function widget($args, $instance): void
    {
        echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        $title = isset($instance['title']) ? sanitize_text_field((string) $instance['title']) : '';
        if ($title === '') {
            $title = between_words_label('latest_podcast');
        }

        echo $args['before_title'] . esc_html($title) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        between_words_render_latest_podcast_widget_content();
        echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function form($instance): void
    {
        $title = isset($instance['title']) ? sanitize_text_field((string) $instance['title']) : between_words_label('latest_podcast');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title', 'between-words'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance): array
    {
        $instance = [];
        $instance['title'] = isset($new_instance['title']) ? sanitize_text_field((string) $new_instance['title']) : '';

        return $instance;
    }
}

function between_words_register_widgets(): void
{
    register_widget('Between_Words_Latest_Podcast_Widget');
}
add_action('widgets_init', 'between_words_register_widgets');
