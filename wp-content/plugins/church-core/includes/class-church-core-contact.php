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
        $form_state = self::get_form_state();
        $has_notice = in_array($status, ['success', 'invalid', 'error'], true);
        $notice_id = 'church-contact-form-notice';
        $notice_attr = $has_notice ? ' aria-describedby="' . esc_attr($notice_id) . '"' : '';
        $inquiry_type_options = self::get_inquiry_type_options();
        $preferred_contact_method_options = self::get_preferred_contact_method_options();
        $selected_inquiry_type = self::sanitize_choice((string) ($form_state['contact_inquiry_type'] ?? ''), $inquiry_type_options);
        $selected_preferred_contact_method = self::sanitize_choice((string) ($form_state['contact_preferred_contact_method'] ?? ''), $preferred_contact_method_options);
        ob_start();
        ?>
        <div class="contact-form-shell">
            <?php if ($status === 'success') : ?>
                <p id="<?php echo esc_attr($notice_id); ?>" class="contact-form__notice is-success" role="status" aria-live="polite"><?php esc_html_e('Thanks for reaching out. Your message has been received.', 'church-core'); ?></p>
            <?php elseif ($status === 'invalid') : ?>
                <p id="<?php echo esc_attr($notice_id); ?>" class="contact-form__notice is-error" role="alert" aria-live="assertive"><?php esc_html_e('Please complete the required fields and try again.', 'church-core'); ?></p>
            <?php elseif ($status === 'error') : ?>
                <p id="<?php echo esc_attr($notice_id); ?>" class="contact-form__notice is-error" role="alert" aria-live="assertive"><?php esc_html_e('Something went wrong while saving your message. Please try again.', 'church-core'); ?></p>
            <?php endif; ?>

            <form class="contact-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="church_contact_submit">
                <?php wp_nonce_field('church_core_contact_submit', 'church_core_contact_nonce'); ?>

                <div class="contact-form__row">
                    <label class="contact-form__field" for="contact_name">
                        <span><?php esc_html_e('Name', 'church-core'); ?></span>
                        <input id="contact_name" type="text" name="contact_name" value="<?php echo esc_attr((string) ($form_state['contact_name'] ?? '')); ?>" autocomplete="name" required<?php echo $notice_attr; ?>>
                    </label>

                    <label class="contact-form__field" for="contact_email">
                        <span><?php esc_html_e('Email', 'church-core'); ?></span>
                        <input id="contact_email" type="email" name="contact_email" value="<?php echo esc_attr((string) ($form_state['contact_email'] ?? '')); ?>" autocomplete="email" required<?php echo $notice_attr; ?>>
                    </label>
                </div>

                <div class="contact-form__row">
                    <label class="contact-form__field" for="contact_inquiry_type">
                        <span><?php esc_html_e('What can we help with?', 'church-core'); ?></span>
                        <select id="contact_inquiry_type" name="contact_inquiry_type" required<?php echo $notice_attr; ?>>
                            <option value=""><?php esc_html_e('Select an option', 'church-core'); ?></option>
                            <?php foreach ($inquiry_type_options as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($selected_inquiry_type, $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <fieldset class="contact-form__field-group"<?php echo $notice_attr; ?>>
                        <legend class="contact-form__legend"><?php esc_html_e('Preferred Contact Method', 'church-core'); ?></legend>
                        <div class="contact-form__choice-group">
                            <?php $is_first_option = true; ?>
                            <?php foreach ($preferred_contact_method_options as $value => $label) : ?>
                                <?php $field_id = 'contact_preferred_contact_method_' . $value; ?>
                                <label class="contact-form__choice" for="<?php echo esc_attr($field_id); ?>">
                                    <input
                                        id="<?php echo esc_attr($field_id); ?>"
                                        type="radio"
                                        name="contact_preferred_contact_method"
                                        value="<?php echo esc_attr($value); ?>"
                                        <?php checked($selected_preferred_contact_method, $value); ?>
                                        <?php echo $is_first_option ? 'required' : ''; ?>
                                    >
                                    <span><?php echo esc_html($label); ?></span>
                                </label>
                                <?php $is_first_option = false; ?>
                            <?php endforeach; ?>
                        </div>
                        <p class="contact-form__helper"><?php esc_html_e('Choose how you would like us to follow up. If you prefer a phone call, include a phone number below.', 'church-core'); ?></p>
                    </fieldset>
                </div>

                <label class="contact-form__field contact-form__field--full" for="contact_phone">
                    <span><?php esc_html_e('Phone', 'church-core'); ?></span>
                    <input id="contact_phone" type="tel" name="contact_phone" value="<?php echo esc_attr((string) ($form_state['contact_phone'] ?? '')); ?>" autocomplete="tel"<?php echo $notice_attr; ?>>
                    <small class="contact-form__helper"><?php esc_html_e('Required only if you want us to call you back.', 'church-core'); ?></small>
                </label>

                <label class="contact-form__field contact-form__field--full" for="contact_message">
                    <span><?php esc_html_e('Message', 'church-core'); ?></span>
                    <textarea id="contact_message" name="contact_message" required<?php echo $notice_attr; ?>><?php echo esc_textarea((string) ($form_state['contact_message'] ?? '')); ?></textarea>
                </label>

                <div class="contact-form__honeypot" aria-hidden="true">
                    <label for="contact_website"><?php esc_html_e('Leave this field empty', 'church-core'); ?></label>
                    <input id="contact_website" type="text" name="contact_website" tabindex="-1" autocomplete="off">
                </div>

                <button type="submit"><?php esc_html_e('Send Message', 'church-core'); ?></button>
            </form>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    public static function handle_submission(): void
    {
        $redirect = wp_get_referer() ?: self::get_contact_page_url();

        if (! isset($_POST['church_core_contact_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['church_core_contact_nonce'])), 'church_core_contact_submit')) {
            self::redirect_with_status($redirect, 'invalid');
        }

        if (! empty($_POST['contact_website'])) {
            self::redirect_with_status($redirect, 'success');
        }

        $name = isset($_POST['contact_name']) ? sanitize_text_field(wp_unslash($_POST['contact_name'])) : '';
        $email_input = isset($_POST['contact_email']) ? sanitize_text_field(wp_unslash($_POST['contact_email'])) : '';
        $email = sanitize_email($email_input);
        $phone = isset($_POST['contact_phone']) ? sanitize_text_field(wp_unslash($_POST['contact_phone'])) : '';
        $inquiry_type = isset($_POST['contact_inquiry_type']) ? self::sanitize_choice(wp_unslash((string) $_POST['contact_inquiry_type']), self::get_inquiry_type_options()) : '';
        $preferred_contact_method = isset($_POST['contact_preferred_contact_method']) ? self::sanitize_choice(wp_unslash((string) $_POST['contact_preferred_contact_method']), self::get_preferred_contact_method_options()) : '';
        $message = isset($_POST['contact_message']) ? sanitize_textarea_field(wp_unslash($_POST['contact_message'])) : '';
        $state = [
            'contact_name' => $name,
            'contact_email' => $email_input,
            'contact_phone' => $phone,
            'contact_inquiry_type' => $inquiry_type,
            'contact_preferred_contact_method' => $preferred_contact_method,
            'contact_message' => $message,
        ];

        $requires_phone = $preferred_contact_method === 'phone';

        if (
            $name === ''
            || $message === ''
            || $inquiry_type === ''
            || $preferred_contact_method === ''
            || ! is_email($email_input)
            || ($requires_phone && $phone === '')
        ) {
            self::redirect_with_status($redirect, 'invalid', $state);
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
                'contact_inquiry_type' => $inquiry_type,
                'contact_preferred_contact_method' => $preferred_contact_method,
            ],
        ], true);

        if (is_wp_error($post_id)) {
            self::redirect_with_status($redirect, 'error', $state);
        }

        $recipient = apply_filters('church_core_contact_recipient', get_option('admin_email'));
        $subject = sprintf(__('New contact message from %s', 'church-core'), $name);
        $body = implode("\n\n", [
            'Name: ' . $name,
            'Email: ' . $email,
            'Inquiry Type: ' . self::get_choice_label($inquiry_type, self::get_inquiry_type_options(), __('Not provided', 'church-core')),
            'Preferred Contact Method: ' . self::get_choice_label($preferred_contact_method, self::get_preferred_contact_method_options(), __('Not provided', 'church-core')),
            'Phone: ' . ($phone ?: 'Not provided'),
            'Message:',
            $message,
        ]);

        $sent = wp_mail($recipient, $subject, $body, ['Reply-To: ' . $name . ' <' . $email . '>']);

        if (! $sent) {
            error_log('church-core: wp_mail failed for contact submission ID ' . $post_id);
        }

        self::redirect_with_status($redirect, 'success');
    }

    private static function redirect_with_status(string $redirect, string $status, array $state = []): void
    {
        $args = [
            'church_contact_status' => $status,
        ];

        if ($state !== [] && in_array($status, ['invalid', 'error'], true)) {
            $args['church_contact_state'] = self::store_form_state($state);
        }

        wp_safe_redirect(add_query_arg($args, $redirect));
        exit;
    }

    private static function store_form_state(array $state): string
    {
        $token = wp_generate_password(20, false, false);

        set_transient('church_contact_state_' . $token, $state, 10 * MINUTE_IN_SECONDS);

        return $token;
    }

    private static function get_form_state(): array
    {
        $token = isset($_GET['church_contact_state']) ? preg_replace('/[^a-zA-Z0-9]/', '', (string) wp_unslash($_GET['church_contact_state'])) : '';

        if ($token === '') {
            return [];
        }

        $state = get_transient('church_contact_state_' . $token);

        if (! is_array($state)) {
            return [];
        }

        delete_transient('church_contact_state_' . $token);

        return $state;
    }

    private static function get_contact_page_url(): string
    {
        $contact_page = get_page_by_path('contact-us');

        if (! $contact_page instanceof WP_Post) {
            $contact_page = get_page_by_path('contact');
        }

        if ($contact_page instanceof WP_Post) {
            $permalink = get_permalink($contact_page);

            if (is_string($permalink) && $permalink !== '') {
                return $permalink;
            }
        }

        return home_url('/contact-us/');
    }

    public static function submission_columns(array $columns): array
    {
        $updated_columns = [];

        foreach ($columns as $key => $label) {
            $updated_columns[$key] = $label;

            if ($key === 'title') {
                $updated_columns['contact_inquiry_type'] = __('Inquiry Type', 'church-core');
                $updated_columns['contact_preferred_contact_method'] = __('Preferred Contact', 'church-core');
                $updated_columns['contact_email'] = __('Email', 'church-core');
                $updated_columns['contact_phone'] = __('Phone', 'church-core');
            }
        }

        return $updated_columns;
    }

    public static function render_submission_column(string $column, int $post_id): void
    {
        if ($column === 'contact_inquiry_type') {
            $inquiry_type = self::sanitize_choice((string) get_post_meta($post_id, 'contact_inquiry_type', true), self::get_inquiry_type_options());
            echo esc_html(self::get_choice_label($inquiry_type, self::get_inquiry_type_options(), '—'));
        }

        if ($column === 'contact_preferred_contact_method') {
            $preferred_contact_method = self::sanitize_choice((string) get_post_meta($post_id, 'contact_preferred_contact_method', true), self::get_preferred_contact_method_options());
            echo esc_html(self::get_choice_label($preferred_contact_method, self::get_preferred_contact_method_options(), '—'));
        }

        if ($column === 'contact_email') {
            echo esc_html((string) get_post_meta($post_id, 'contact_email', true) ?: '—');
        }

        if ($column === 'contact_phone') {
            echo esc_html((string) get_post_meta($post_id, 'contact_phone', true) ?: '—');
        }
    }

    private static function get_inquiry_type_options(): array
    {
        return [
            'first_visit' => __('Planning a First Visit', 'church-core'),
            'directions_location' => __('Directions or Location Help', 'church-core'),
            'children_ministry' => __('Children’s Ministry', 'church-core'),
            'service_times_worship' => __('Service Times or Worship Questions', 'church-core'),
            'general_question' => __('General Question', 'church-core'),
            'other' => __('Other', 'church-core'),
        ];
    }

    private static function get_preferred_contact_method_options(): array
    {
        return [
            'email' => __('Email', 'church-core'),
            'phone' => __('Phone Call', 'church-core'),
        ];
    }

    private static function sanitize_choice(string $value, array $options): string
    {
        $sanitized_value = sanitize_key($value);

        return array_key_exists($sanitized_value, $options) ? $sanitized_value : '';
    }

    private static function get_choice_label(string $value, array $options, string $fallback): string
    {
        return $options[$value] ?? $fallback;
    }
}
