<?php

namespace WpOrg\Support;

class Captcha
{
    public function register()
    {
        add_action('wp_enqueue_scripts', [$this, 'register_google_script']);
    }

    public function register_google_script()
    {
        $settings = get_option('wp_org_captcha_settings', []);
        if (empty($settings['enabled']) || empty($settings['site_key'])) {
            return;
        }

        wp_register_script('wp-org-recaptcha', 'https://www.google.com/recaptcha/api.js?render=explicit', [], null, true);
    }

    public function is_enabled()
    {
        $settings = get_option('wp_org_captcha_settings', []);
        return !empty($settings['enabled']) && !empty($settings['site_key']) && !empty($settings['secret_key']);
    }

    public function render($form_id)
    {
        $settings = get_option('wp_org_captcha_settings', []);
        if (!$this->is_enabled()) {
            return '';
        }

        wp_enqueue_script('wp-org-recaptcha');

        return '<div class="wp-org-recaptcha" data-form-id="' . esc_attr($form_id) . '" data-site-key="' . esc_attr($settings['site_key']) . '"></div>';
    }

    public function verify_submission()
    {
        if (!$this->is_enabled()) {
            return true;
        }

        $response = isset($_POST['g-recaptcha-response']) ? sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'])) : '';
        if ($response === '') {
            return new \WP_Error('captcha_required', 'Captcha wajib divalidasi.');
        }

        $settings = get_option('wp_org_captcha_settings', []);
        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $result = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'timeout' => 15,
            'body' => [
                'secret' => $settings['secret_key'],
                'response' => $response,
                'remoteip' => $remote_ip,
            ],
        ]);

        if (is_wp_error($result)) {
            return new \WP_Error('captcha_verify_failed', 'Verifikasi captcha gagal.');
        }

        $body = json_decode(wp_remote_retrieve_body($result), true);
        if (empty($body['success'])) {
            return new \WP_Error('captcha_invalid', 'Captcha tidak valid.');
        }

        return true;
    }
}
