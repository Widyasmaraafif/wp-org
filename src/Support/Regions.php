<?php

namespace WpOrg\Support;

class Regions
{
    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    private $regions = [
        'provinces' => [
            ['code' => '31', 'name' => 'DKI Jakarta'],
            ['code' => '32', 'name' => 'Jawa Barat'],
            ['code' => '35', 'name' => 'Jawa Timur'],
        ],
        'cities' => [
            ['code' => '31.01', 'province_code' => '31', 'name' => 'Kota Jakarta Pusat'],
            ['code' => '31.02', 'province_code' => '31', 'name' => 'Kota Jakarta Utara'],
            ['code' => '32.01', 'province_code' => '32', 'name' => 'Kota Bandung'],
            ['code' => '32.02', 'province_code' => '32', 'name' => 'Kabupaten Bekasi'],
            ['code' => '35.01', 'province_code' => '35', 'name' => 'Kota Surabaya'],
            ['code' => '35.02', 'province_code' => '35', 'name' => 'Kabupaten Sidoarjo'],
        ],
        'districts' => [
            ['code' => '31.01.01', 'city_code' => '31.01', 'name' => 'Gambir'],
            ['code' => '31.01.02', 'city_code' => '31.01', 'name' => 'Tanah Abang'],
            ['code' => '31.02.01', 'city_code' => '31.02', 'name' => 'Koja'],
            ['code' => '31.02.02', 'city_code' => '31.02', 'name' => 'Cilincing'],
            ['code' => '32.01.01', 'city_code' => '32.01', 'name' => 'Coblong'],
            ['code' => '32.01.02', 'city_code' => '32.01', 'name' => 'Lengkong'],
            ['code' => '32.02.01', 'city_code' => '32.02', 'name' => 'Cikarang Barat'],
            ['code' => '32.02.02', 'city_code' => '32.02', 'name' => 'Tambun Selatan'],
            ['code' => '35.01.01', 'city_code' => '35.01', 'name' => 'Tegalsari'],
            ['code' => '35.01.02', 'city_code' => '35.01', 'name' => 'Wonokromo'],
            ['code' => '35.02.01', 'city_code' => '35.02', 'name' => 'Buduran'],
            ['code' => '35.02.02', 'city_code' => '35.02', 'name' => 'Waru'],
        ],
    ];

    public function register()
    {
        add_action('wp_ajax_wp_org_regions', [$this, 'handle_ajax']);
        add_action('wp_ajax_nopriv_wp_org_regions', [$this, 'handle_ajax']);
    }

    public function handle_ajax()
    {
        $type = isset($_GET['type']) ? sanitize_key(wp_unslash($_GET['type'])) : '';
        $parent = isset($_GET['parent']) ? sanitize_text_field(wp_unslash($_GET['parent'])) : '';

        if ($type === 'cities') {
            wp_send_json_success($this->get_cities($parent));
        }

        if ($type === 'districts') {
            wp_send_json_success($this->get_districts($parent));
        }

        wp_send_json_success($this->get_provinces());
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function get_provinces()
    {
        return $this->regions['provinces'];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function get_cities($province_code)
    {
        return array_values(array_filter($this->regions['cities'], static function ($city) use ($province_code) {
            return $city['province_code'] === $province_code;
        }));
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function get_districts($city_code)
    {
        return array_values(array_filter($this->regions['districts'], static function ($district) use ($city_code) {
            return $district['city_code'] === $city_code;
        }));
    }

    public function get_province_name($code)
    {
        return $this->find_name('provinces', 'code', $code);
    }

    public function get_city_name($code)
    {
        return $this->find_name('cities', 'code', $code);
    }

    public function get_district_name($code)
    {
        return $this->find_name('districts', 'code', $code);
    }

    private function find_name($bucket, $key, $value)
    {
        foreach ($this->regions[$bucket] as $item) {
            if ($item[$key] === $value) {
                return $item['name'];
            }
        }

        return '';
    }
}
