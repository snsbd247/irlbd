<?php

namespace Tests\Feature;

use App\Models\ContactSubmission;
use App\Models\QuoteRequest;
use Tests\TestCase;

class DashboardReportTest extends TestCase
{
    protected function quote(string $product, $when): void
    {
        $q = QuoteRequest::create(['product_name' => $product, 'name' => 'A', 'email' => 'a@example.com', 'phone' => '0171', 'quantity' => '1', 'status' => 'new']);
        $q->forceFill(['created_at' => $when])->saveQuietly();
    }

    public function test_report_counts_last_twelve_months_and_top_products(): void
    {
        $this->quote('PET Bottle', now());
        $this->quote('PET Bottle', now());
        $this->quote('Cap', now()->subMonths(2));
        $this->quote('Old', now()->subMonths(14)); // outside the window
        $e = ContactSubmission::create(['name' => 'B', 'email' => 'b@example.com', 'subject' => 'Hi', 'message' => 'Hello there', 'status' => 'new']);
        $e->forceFill(['created_at' => now()->subMonth()])->saveQuietly();

        $report = $this->actingAs($this->admin())->spa()->getJson('/api/v1/admin/dashboard')->assertOk()->json('report');

        $this->assertCount(12, $report['months']);
        $this->assertSame(now()->format('Y-m'), end($report['months']));
        $this->assertSame(2, $report['series']['quotes'][11]);
        $this->assertSame(1, $report['series']['quotes'][9]);
        $this->assertSame(3, array_sum($report['series']['quotes']));
        $this->assertSame(1, $report['series']['enquiries'][10]);
        $this->assertSame(['name' => 'PET Bottle', 'count' => 2], $report['top_products'][0]);
    }

    public function test_report_only_includes_what_the_user_may_see(): void
    {
        $report = $this->actingAs($this->makeUser('hr'))->spa()->getJson('/api/v1/admin/dashboard')->assertOk()->json('report');

        $this->assertSame(['applications'], array_keys($report['series']));
        $this->assertSame([], $report['top_products']);
    }
}
