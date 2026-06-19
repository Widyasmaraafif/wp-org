<?php

namespace WpOrg\Core;

class Plugin
{
    public function run()
    {
        $this->load_core();
        $this->load_admin();
        $this->load_frontend();
    }

    private function load_core()
    {
        $roles = new Roles();
        $roles->register();

        $captcha = new \WpOrg\Support\Captcha();
        $captcha->register();

        $regions = new \WpOrg\Support\Regions();
        $regions->register();

        $menu_visibility = new \WpOrg\Support\MenuVisibility();
        $menu_visibility->register();

        // Hook for role changes to assign member numbers
        add_action('set_user_role', [\WpOrg\Support\MemberData::class, 'handle_role_change'], 10, 3);
    }

    private function load_admin()
    {
        if (!is_admin()) {
            return;
        }

        $menu = new \WpOrg\Admin\AdminMenu();
        $menu->register();
    }

    private function load_frontend()
    {
        $assets = new \WpOrg\Frontend\Assets();
        $assets->register();

        $auth = new \WpOrg\Frontend\Auth();
        $auth->register();

        $members = new \WpOrg\Frontend\Members();
        $members->register();

        $profile = new \WpOrg\Frontend\Profile();
        $profile->register();
    }
}
