<?php
if (! defined('ABSPATH')) {
    exit;
}

final class Church_Core_Contact
{
    public static function boot(): void
    {
        add_action('init', [__CLASS__, 'register_content']);
        add_shortcode('church_contact_form', [__CLASS__, 'render_form']);
        add_action('admin_post_nopriv_church_contact_submit', [__CLASS__, 'handle_submission']);
        add_action('admin_post_church_contact_submit', [__CLASS__, 'handle_submission']);
        add_filter('manage_contact_submission_posts_columns', [__CLASS__, 'submission_columns']);
        add_action('manage_contact_submission_posts_custom_column', [__CLASS__, 'render_submission_column'], 10, 2);
    }

    public static function register_content(): void
    {
        register_post_type('contact_submission', [
            'labels' => [
                'name' => __('Contact Messages', 'church-core'),
                'singular_name' => __('Contact Message', 'church-core'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-email-alt',
            'supports' => ['title', 'editor'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);
    }

    public static function render_form(): string
    {
        $status = isset($_GET['church_contact_status']) ? sanitize_key(wp_unslash((string) $_GET['church_contact_status'])) : '';
        ob_start();
        ?>
        <div class="contact-form-shell">
            <?php if ($status === 'success') : ?>
                <p class="contact-form__notice"><?php esc_html_e('Thanks for reaching out. Your message has been received.', 'church-core'); ?></p>
            <?php elseif ($status === 'invalid') : ?>
                <p class="contact-form__notice is-error"><?php esc_html_e('Please complete the required fields and try again.', 'church-core'); ?></p>
            <?php elseif ($status === 'error') : ?>
                <p class="contact-form__notice is-error"><?php esc_html_e('Something went wrong while saving your message. Please try again.', 'church-core'); ?></p>
            <?php endif; ?>

            <form class="contact-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="church_contact_submit">
                <?php wp_nonce_field('church_core_contact_submit', 'church_core_contact_nonce'); ?>

                <div class="contact-form__row">
                    <label>
                        <span><?php esc_html_e('Name', 'church-core'); ?></span>
                        <input type="text" name="contact_name" required>
                    </label>

                    <label>
                        <span><?php esc_html_e('Email', 'church-core'); ?></span>
                        <input type="email" name="contact_email" required>
                    </label>
                </div>

                <div class="contact-form__row">
                    <label>
                        <span><?php esc_html_e('Phone', 'church-core'); ?></span>
                        <input type="text" name="contact_phone">
                    </label>

                    <label class="screen-reader-text" aria-hidden="true">
                        <span><?php esc_html_e('Leave this field empty', 'church-core'); ?></span>
                        <input type="text" name="contact_website" tabindex="-1" autocomplete="off">
                    </label>
                </div>

                <label>
                    <span><?php esc_html_e('Message', 'church-core'); ?></span>
                    <textarea name="contact_message" required></textarea>
                </label>

                <button type="submit"><?php esc_html_e('Send Message', 'church-core'); ?></button>
            </form>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    public static function handle_submission(): void
    {
        $redirect = wp_get_referer() ?: home_url('/contact/');

        if (! isset($_POST['church_core_contact_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['church_core_contact_nonce'])), 'church_core_contact_submit')) {
            self::redirect_with_status($redirect, 'invalid');
        }

        if (! empty($_POST['contact_website'])) {
            self::redirect_with_status($redirect, 'success');
        }

        $name = isset($_POST['contact_name']) ? sanitize_text_field(wp_unslash($_POST['contact_name'])) : '';
        $email = isset($_POST['contact_email']) ? sanitize_email(wp_unslash($_POST['contact_email'])) : '';
        $phone = isset($_POST['contact_phone']) ? sanitize_text_field(wp_unslash($_POST['contact_phone'])) : '';
        $message = isset($_POST['contact_message']) ? sanitize_textarea_field(wp_unslash($_POST['contact_message'])) : '';

        if ($name === '' || $message === '' || ! is_email($email)) {
            self::redirect_with_status($redirect, 'invalid');
        }

        $post_id = wp_insert_post([
            'post_type' => 'contact_submission',
            'post_status' => 'publish',
            'post_title' => sprintf('%s - %s', $name, current_time('mysql')),
            'post_content' => $message,
            'meta_input' => [
                'contact_name' => $name,
                'contact_email' => $email,
                'contact_phone' => $phone,
            ],
        ], true);

        if (is_wp_error($post_id)) {
            self::redirect_with_status($redirect, 'error');
        }

        $recipient = apply_filters('church_core_contact_recipient', get_option('admin_email'));
        $subject = sprintf(__('New contact message from %s', 'church-core'), $name);
        $body = implode("\n\n", [
            'Name: ' . $name,
            'Email: ' . $email,
            'Phone: ' . ($phone ?: 'Not provided'),
            'Message:',
            $message,
        ]);

        wp_mail($recipient, $subject, $body, ['Reply-To: ' . $name . ' <' . $email . '>']);

        self::redirect_with_status($redirect, 'success');
    }

    private static function redirect_with_status(string $redirect, string $status): void
    {
        wp_safe_redirect(add_query_arg('church_contact_status', $status, $redirect));
        exit;
    }

    public static function submission_columns(array $columns): array
    {
        $columns['contact_email'] = __('Email', 'church-core');
        $columns['contact_phone'] = __('Phone', 'church-core');

        return $columns;
    }

    public static function render_submission_column(string $column, int $post_id): void
    {
        if ($column === 'contact_email') {
            echo esc_html((string) get_post_meta($post_id, 'contact_email', true) ?: '—');
        }

        if ($column === 'contact_phone') {
            echo esc_html((string) get_post_meta($post_id, 'contact_phone', true) ?: '—');
        }
    }
}
