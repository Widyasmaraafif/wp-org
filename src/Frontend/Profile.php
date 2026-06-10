<?php

namespace WpOrg\Frontend;

use WpOrg\Support\MemberData;
use WpOrg\Support\Regions;

class Profile
{
    public function register()
    {
        add_shortcode('org_profile', [$this, 'render_shortcode']);
        add_action('init', [$this, 'handle_update']);
    }

    public function handle_update()
    {
        if (!isset($_POST['wp_org_profile_submit']) || !is_user_logged_in()) {
            return;
        }

        if (!isset($_POST['wp_org_profile_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wp_org_profile_nonce'])), 'wp_org_profile_action')) {
            return;
        }

        $errors = MemberData::validate_submission($_POST, true);
        if ($errors->has_errors()) {
            return;
        }

        MemberData::save_profile_fields(get_current_user_id(), $_POST);
        wp_safe_redirect(wp_get_referer() ? wp_get_referer() : home_url('/'));
        exit;
    }

    public function render_shortcode()
    {
        if (!is_user_logged_in()) {
            return '<div class="wp-org-card"><p>Silakan login untuk melihat profil anggota.</p></div>';
        }

        $user_id = get_current_user_id();
        $fields = MemberData::get_registration_fields();
        $regions = new Regions();
        $statuses = MemberData::get_all_statuses();
        $status = MemberData::get_status($user_id);

        ob_start();
        echo '<div class="wp-org-card"><h2>Profil Anggota</h2>';
        echo '<p class="wp-org-muted">Status pendaftaran: <span class="wp-org-status wp-org-status-' . esc_attr($status) . '">' . esc_html($statuses[$status] ?? $status) . '</span></p>';
        echo '<form class="wp-org-grid wp-org-region-form" method="post">';
        wp_nonce_field('wp_org_profile_action', 'wp_org_profile_nonce');

        foreach ($fields as $field) {
            $value = get_user_meta($user_id, 'wp_org_' . $field['key'], true);
            echo $this->render_field($field, $value, $regions);
        }

        echo '<div class="wp-org-actions"><button class="wp-org-button" type="submit" name="wp_org_profile_submit" value="1">Simpan Profil</button></div>';
        echo '</form></div>';

        return (string) ob_get_clean();
    }

    private function render_field($field, $value, Regions $regions)
    {
        $key = $field['key'];
        $required = !empty($field['required']) ? ' required' : '';
        $html = '<div class="wp-org-field"><label for="' . esc_attr($key) . '">' . esc_html($field['label']) . '</label>';
        $options = MemberData::get_field_options($field);

        if ($field['type'] === 'textarea') {
            $html .= '<textarea id="' . esc_attr($key) . '" name="' . esc_attr($key) . '"' . $required . '>' . esc_textarea((string) $value) . '</textarea>';
        } elseif ($field['type'] === 'select') {
            $html .= '<select id="' . esc_attr($key) . '" name="' . esc_attr($key) . '"' . $required . '><option value="">Pilih opsi</option>';
            foreach ($options as $option) {
                $html .= '<option value="' . esc_attr($option) . '"' . selected($value, $option, false) . '>' . esc_html($option) . '</option>';
            }
            $html .= '</select>';
        } elseif ($field['type'] === 'radio') {
            foreach ($options as $option) {
                $html .= '<label><input type="radio" name="' . esc_attr($key) . '" value="' . esc_attr($option) . '"' . checked($value, $option, false) . $required . '> ' . esc_html($option) . '</label> ';
            }
        } elseif ($field['type'] === 'checkbox') {
            $selected_values = is_array($value) ? $value : (array) $value;
            foreach ($options as $option) {
                $html .= '<label><input type="checkbox" name="' . esc_attr($key) . '[]" value="' . esc_attr($option) . '"' . checked(in_array($option, $selected_values, true), true, false) . '> ' . esc_html($option) . '</label> ';
            }
        } elseif ($field['type'] === 'region_province') {
            $html .= '<select id="' . esc_attr($key) . '" class="wp-org-province" name="' . esc_attr($key) . '"' . $required . '><option value="">Pilih provinsi</option>';
            foreach ($regions->get_provinces() as $province) {
                $html .= '<option value="' . esc_attr($province['code']) . '"' . selected($value, $province['code'], false) . '>' . esc_html($province['name']) . '</option>';
            }
            $html .= '</select>';
        } elseif ($field['type'] === 'region_city') {
            $html .= '<select id="' . esc_attr($key) . '" class="wp-org-city" name="' . esc_attr($key) . '" data-selected="' . esc_attr((string) $value) . '"' . $required . '><option value="">Pilih kota/kabupaten</option></select>';
        } elseif ($field['type'] === 'region_district') {
            $html .= '<select id="' . esc_attr($key) . '" class="wp-org-district" name="' . esc_attr($key) . '" data-selected="' . esc_attr((string) $value) . '"' . $required . ' disabled><option value="">Pilih kecamatan</option></select>';
        } else {
            $input_type = in_array($field['type'], ['email', 'number', 'date'], true) ? $field['type'] : 'text';
            $html .= '<input id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" type="' . esc_attr($input_type) . '" value="' . esc_attr((string) $value) . '"' . $required . '>';
        }

        $html .= '</div>';

        return $html;
    }
}
