<?php

namespace WpOrg\Admin;

use WpOrg\Support\MemberData;

class AdminMenu
{
    public function register()
    {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_post_wp_org_update_member_status', [$this, 'handle_member_status']);
        add_action('admin_post_wp_org_save_fields', [$this, 'handle_save_fields']);
        add_action('admin_post_wp_org_save_settings', [$this, 'handle_save_settings']);
    }

    public function add_menu()
    {
        add_menu_page('WP Org', 'WP Org', 'wp_org_manage_members', 'wp-org', [$this, 'render_members_page'], 'dashicons-groups');
        add_submenu_page('wp-org', 'Anggota', 'Anggota', 'wp_org_manage_members', 'wp-org', [$this, 'render_members_page']);
        add_submenu_page('wp-org', 'Field Form', 'Field Form', 'wp_org_manage_settings', 'wp-org-fields', [$this, 'render_fields_page']);
        add_submenu_page('wp-org', 'Pengaturan', 'Pengaturan', 'wp_org_manage_settings', 'wp-org-settings', [$this, 'render_settings_page']);
    }

    public function render_members_page()
    {
        if (!current_user_can('wp_org_manage_members')) {
            wp_die('Akses ditolak.');
        }

        $users = get_users([
            'role__in' => ['org_member', 'org_admin'],
            'number' => 100,
            'orderby' => 'registered',
            'order' => 'DESC',
        ]);
        $statuses = MemberData::get_all_statuses();

        echo '<div class="wrap"><h1>Data Anggota</h1><table class="widefat striped"><thead><tr><th>Nama</th><th>Email</th><th>Status</th><th>Tanggal Daftar</th><th>Catatan Admin</th><th>Aksi</th></tr></thead><tbody>';

        if (!$users) {
            echo '<tr><td colspan="6">Belum ada anggota.</td></tr>';
        }

        foreach ($users as $user) {
            $status = MemberData::get_status($user->ID);
            $note = get_user_meta($user->ID, 'wp_org_admin_note', true);
            echo '<tr><td>' . esc_html($user->display_name) . '</td><td>' . esc_html($user->user_email) . '</td><td>' . esc_html($statuses[$status] ?? $status) . '</td><td>' . esc_html(get_user_meta($user->ID, 'wp_org_registered_at', true) ?: $user->user_registered) . '</td><td>' . esc_html($note) . '</td><td>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('wp_org_update_member_status_' . $user->ID);
            echo '<input type="hidden" name="action" value="wp_org_update_member_status">';
            echo '<input type="hidden" name="user_id" value="' . esc_attr((string) $user->ID) . '">';
            echo '<select name="status">';
            foreach ($statuses as $key => $label) {
                echo '<option value="' . esc_attr($key) . '"' . selected($status, $key, false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select> ';
            echo '<input type="text" name="admin_note" value="' . esc_attr($note) . '" placeholder="Catatan internal"> ';
            submit_button('Simpan', 'secondary', 'submit', false);
            echo '</form></td></tr>';
        }

        echo '</tbody></table></div>';
    }

    public function render_fields_page()
    {
        if (!current_user_can('wp_org_manage_settings')) {
            wp_die('Akses ditolak.');
        }

        $fields = MemberData::get_all_registration_fields();
        echo '<div class="wrap"><h1>Field Formulir</h1><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('wp_org_save_fields');
        echo '<input type="hidden" name="action" value="wp_org_save_fields">';
        echo '<p>Tambah, hapus, aktifkan, atau nonaktifkan field pendaftaran. Field nonaktif tetap tersimpan tetapi tidak ditampilkan di frontend.</p>';
        echo '<table class="widefat striped wp-org-fields-table"><thead><tr><th>Key</th><th>Label</th><th>Tipe</th><th>Opsi</th><th>Wajib</th><th>Aktif</th><th>Aksi</th></tr></thead><tbody data-next-index="' . esc_attr((string) count($fields)) . '">';

        foreach ($fields as $index => $field) {
            echo $this->render_field_row($index, $field);
        }

        echo '</tbody></table>';
        echo '<p><button type="button" class="button" id="wp-org-add-field">Tambah Field</button></p>';
        echo '<script type="text/html" id="tmpl-wp-org-field-row">' . wp_kses_post($this->render_field_row('__index__', [
            'key' => '',
            'label' => '',
            'type' => 'text',
            'required' => 0,
            'enabled' => 1,
            'options' => '',
        ])) . '</script>';
        submit_button('Simpan Field');
        echo '</form></div>';
    }

    public function render_settings_page()
    {
        if (!current_user_can('wp_org_manage_settings')) {
            wp_die('Akses ditolak.');
        }

        $general = get_option('wp_org_general_settings', []);
        $captcha = get_option('wp_org_captcha_settings', []);

        echo '<div class="wrap"><h1>Pengaturan WP Org</h1><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('wp_org_save_settings');
        echo '<input type="hidden" name="action" value="wp_org_save_settings">';
        echo '<table class="form-table"><tbody>';
        echo '<tr><th scope="row">Butuh Approval Admin</th><td><input type="checkbox" name="general[require_approval]" value="1"' . checked(!empty($general['require_approval']), true, false) . '></td></tr>';
        echo '<tr><th scope="row">Daftar Anggota Publik</th><td><input type="checkbox" name="general[members_page_public]" value="1"' . checked(!empty($general['members_page_public']), true, false) . '></td></tr>';
        echo '<tr><th scope="row">Login Redirect URL</th><td><input class="regular-text" type="url" name="general[login_redirect]" value="' . esc_attr($general['login_redirect'] ?? '') . '"></td></tr>';
        echo '<tr><th scope="row">Captcha Aktif</th><td><input type="checkbox" name="captcha[enabled]" value="1"' . checked(!empty($captcha['enabled']), true, false) . '></td></tr>';
        echo '<tr><th scope="row">Captcha Site Key</th><td><input class="regular-text" type="text" name="captcha[site_key]" value="' . esc_attr($captcha['site_key'] ?? '') . '"></td></tr>';
        echo '<tr><th scope="row">Captcha Secret Key</th><td><input class="regular-text" type="text" name="captcha[secret_key]" value="' . esc_attr($captcha['secret_key'] ?? '') . '"></td></tr>';
        echo '</tbody></table>';
        submit_button('Simpan Pengaturan');
        echo '</form></div>';
    }

    public function handle_member_status()
    {
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;

        if (!$user_id || !current_user_can('wp_org_approve_members') || !check_admin_referer('wp_org_update_member_status_' . $user_id)) {
            wp_die('Permintaan tidak valid.');
        }

        $status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : 'pending';
        $note = isset($_POST['admin_note']) ? sanitize_text_field(wp_unslash($_POST['admin_note'])) : '';
        MemberData::update_status($user_id, $status);
        update_user_meta($user_id, 'wp_org_admin_note', $note);

        wp_safe_redirect(admin_url('admin.php?page=wp-org'));
        exit;
    }

    public function handle_save_fields()
    {
        if (!current_user_can('wp_org_manage_settings') || !check_admin_referer('wp_org_save_fields')) {
            wp_die('Permintaan tidak valid.');
        }

        $fields = isset($_POST['fields']) ? (array) wp_unslash($_POST['fields']) : [];
        $sanitized = [];

        foreach ($fields as $field) {
            if (!empty($field['_delete'])) {
                continue;
            }

            $prepared = MemberData::normalize_field($field);

            if ($prepared) {
                $sanitized[] = $prepared;
            }
        }

        update_option('wp_org_registration_fields', $sanitized);
        wp_safe_redirect(admin_url('admin.php?page=wp-org-fields'));
        exit;
    }

    public function handle_save_settings()
    {
        if (!current_user_can('wp_org_manage_settings') || !check_admin_referer('wp_org_save_settings')) {
            wp_die('Permintaan tidak valid.');
        }

        $general = isset($_POST['general']) ? (array) wp_unslash($_POST['general']) : [];
        $captcha = isset($_POST['captcha']) ? (array) wp_unslash($_POST['captcha']) : [];

        update_option('wp_org_general_settings', [
            'require_approval' => !empty($general['require_approval']) ? 1 : 0,
            'members_page_public' => !empty($general['members_page_public']) ? 1 : 0,
            'login_redirect' => esc_url_raw($general['login_redirect'] ?? ''),
        ]);

        update_option('wp_org_captcha_settings', [
            'enabled' => !empty($captcha['enabled']) ? 1 : 0,
            'site_key' => sanitize_text_field($captcha['site_key'] ?? ''),
            'secret_key' => sanitize_text_field($captcha['secret_key'] ?? ''),
        ]);

        wp_safe_redirect(admin_url('admin.php?page=wp-org-settings'));
        exit;
    }

    public function enqueue_admin_assets($hook_suffix)
    {
        if ($hook_suffix !== 'wp-org_page_wp-org-fields') {
            return;
        }

        wp_add_inline_style('wp-admin', '.wp-org-fields-table input[type="text"],.wp-org-fields-table select,.wp-org-fields-table textarea{width:100%}.wp-org-fields-table textarea{min-height:64px}.wp-org-field-row-disabled{opacity:.6}');
        wp_add_inline_script('jquery-core', <<<'JS'
jQuery(function($){
    function syncRowState($row) {
        var isEnabled = $row.find('.wp-org-field-enabled').is(':checked');
        $row.toggleClass('wp-org-field-row-disabled', !isEnabled);
    }

    $(document).on('click', '#wp-org-add-field', function() {
        var $tbody = $('.wp-org-fields-table tbody');
        var nextIndex = parseInt($tbody.attr('data-next-index'), 10) || 0;
        var template = $('#tmpl-wp-org-field-row').html().replace(/__index__/g, nextIndex);
        $tbody.append(template);
        $tbody.attr('data-next-index', nextIndex + 1);
    });

    $(document).on('click', '.wp-org-remove-field', function() {
        var $row = $(this).closest('tr');
        $row.find('.wp-org-field-delete').val('1');
        $row.remove();
    });

    $(document).on('change', '.wp-org-field-enabled', function() {
        syncRowState($(this).closest('tr'));
    });

    $('.wp-org-fields-table tbody tr').each(function() {
        syncRowState($(this));
    });
});
JS
);
    }

    private function render_field_row($index, $field)
    {
        $types = [
            'text' => 'Text',
            'textarea' => 'Textarea',
            'email' => 'Email',
            'number' => 'Number',
            'date' => 'Date',
            'select' => 'Select',
            'radio' => 'Radio',
            'checkbox' => 'Checkbox',
            'region_province' => 'Provinsi',
            'region_city' => 'Kota/Kabupaten',
            'region_district' => 'Kecamatan',
        ];

        $type_options = '';
        foreach ($types as $value => $label) {
            $type_options .= '<option value="' . esc_attr($value) . '"' . selected($field['type'], $value, false) . '>' . esc_html($label) . '</option>';
        }

        $row_class = !empty($field['enabled']) ? '' : ' class="wp-org-field-row-disabled"';

        return '<tr' . $row_class . '>'
            . '<td><input type="hidden" class="wp-org-field-delete" name="fields[' . esc_attr((string) $index) . '][_delete]" value="0"><input type="text" name="fields[' . esc_attr((string) $index) . '][key]" value="' . esc_attr($field['key']) . '" placeholder="mis. full_name"></td>'
            . '<td><input type="text" name="fields[' . esc_attr((string) $index) . '][label]" value="' . esc_attr($field['label']) . '" placeholder="Label field"></td>'
            . '<td><select name="fields[' . esc_attr((string) $index) . '][type]">' . $type_options . '</select></td>'
            . '<td><textarea name="fields[' . esc_attr((string) $index) . '][options]" placeholder="Satu opsi per baris">' . esc_textarea($field['options'] ?? '') . '</textarea></td>'
            . '<td><input type="checkbox" name="fields[' . esc_attr((string) $index) . '][required]" value="1"' . checked(!empty($field['required']), true, false) . '></td>'
            . '<td><input type="checkbox" class="wp-org-field-enabled" name="fields[' . esc_attr((string) $index) . '][enabled]" value="1"' . checked(!empty($field['enabled']), true, false) . '></td>'
            . '<td><button type="button" class="button-link-delete wp-org-remove-field">Hapus</button></td>'
            . '</tr>';
    }
}
