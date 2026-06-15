<?php

namespace WpOrg\Support;

class Captcha
{
    public function get_settings()
    {
        $settings = get_option('captcha_velocity', []);

        if (!is_array($settings)) {
            $settings = [];
        }

        return [
            'enabled' => !empty($settings['aktif']),
            'provider' => sanitize_key($settings['provider'] ?? 'google'),
            'site_key' => sanitize_text_field($settings['sitekey'] ?? ''),
            'secret_key' => sanitize_text_field($settings['secretkey'] ?? ''),
        ];
    }

    public function register()
    {
    }

    public function is_enabled()
    {
        $settings = $this->get_settings();

        if (empty($settings['enabled'])) {
            return false;
        }

        if ($settings['provider'] === 'google') {
            return !empty($settings['site_key']) && !empty($settings['secret_key']);
        }

        return $settings['provider'] === 'image';
    }

    public function render($form_id)
    {
        if (!$this->is_enabled()) {
            return '';
        }

        $captcha = $this->get_velocity_captcha();
        if ($captcha && method_exists($captcha, 'display_login_form')) {
            return (string) $captcha->display_login_form();
        }

        return '';
    }

    public function verify_submission()
    {
        if (!$this->is_enabled()) {
            return true;
        }

        $captcha = $this->get_velocity_captcha();
        if (!$captcha || !method_exists($captcha, 'verify')) {
            return new \WP_Error('captcha_unavailable', 'Captcha Velocity Addons tidak tersedia.');
        }

        $response = isset($_POST['g-recaptcha-response']) ? wp_unslash($_POST['g-recaptcha-response']) : null;
        $result = $captcha->verify($response);

        if (!is_array($result) || empty($result['success'])) {
            $message = is_array($result) && !empty($result['message'])
                ? (string) $result['message']
                : 'Captcha tidak valid.';

            return new \WP_Error('captcha_invalid', $message);
        }

        return true;
    }

    public function get_velocity_captcha()
    {
        global $shortcode_tags;

        if (empty($shortcode_tags['velocity_captcha']) || !is_array($shortcode_tags['velocity_captcha'])) {
            return null;
        }

        $callback = $shortcode_tags['velocity_captcha'];

        return isset($callback[0]) && is_object($callback[0]) ? $callback[0] : null;
    }
}
