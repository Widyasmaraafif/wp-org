<?php

namespace WpOrg\Core;

class Installer
{
    public static function activate()
    {
        self::create_roles();
        self::seed_options();
    }

    private static function create_roles()
    {
        add_role('org_member', 'Org Member', [
            'read' => true,
        ]);
    }

    private static function seed_options()
    {
        if (!get_option('wp_org_registration_fields')) {
            update_option('wp_org_registration_fields', [
                ['key' => 'full_name', 'label' => 'Nama Lengkap', 'type' => 'text', 'required' => 1],
                ['key' => 'phone', 'label' => 'Nomor HP', 'type' => 'text', 'required' => 1],
                ['key' => 'province_code', 'label' => 'Provinsi', 'type' => 'region_province', 'required' => 1],
                ['key' => 'city_code', 'label' => 'Kota/Kabupaten', 'type' => 'region_city', 'required' => 1],
                ['key' => 'district_code', 'label' => 'Kecamatan', 'type' => 'region_district', 'required' => 1],
                ['key' => 'address_detail', 'label' => 'Alamat Detail', 'type' => 'textarea', 'required' => 1],
                ['key' => 'postal_code', 'label' => 'Kode Pos', 'type' => 'text', 'required' => 0],
            ]);
        }

        if (!get_option('wp_org_captcha_settings')) {
            update_option('wp_org_captcha_settings', [
                'enabled' => 0,
                'site_key' => '',
                'secret_key' => '',
            ]);
        }
    }
}
