<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminIpRestrictionTest extends TestCase
{
    public function test_admin_api_is_open_when_no_allow_list_is_set(): void
    {
        config(['cms.admin_allowed_ips' => []]);

        $this->actingAs($this->admin())->spa()->getJson('/api/v1/admin/me')->assertOk();
    }

    public function test_only_listed_networks_reach_the_admin_api(): void
    {
        config(['cms.admin_allowed_ips' => ['203.0.113.0/24']]);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
            ->postJson('/api/v1/admin/login', ['email' => 'a@example.com', 'password' => 'x'])
            ->assertForbidden();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->actingAs($this->admin())->spa()->getJson('/api/v1/admin/me')->assertOk();

        // The public website is never affected.
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])->getJson('/api/v1/settings')->assertOk();
    }
}
