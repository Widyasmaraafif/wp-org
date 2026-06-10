<?php

namespace WpOrg\Core;

class Roles
{
    public function register()
    {
        add_role('org_member', 'Org Member', [
            'read' => true,
        ]);
    }
}
