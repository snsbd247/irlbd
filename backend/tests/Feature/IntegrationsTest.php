<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IntegrationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('uploads');
    }

    protected function pdf(string $name = 'brochure.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF");
    }

    public function test_pdf_documents_can_be_uploaded(): void
    {
        $this->actingAs($this->editor())->spa()
            ->post('/api/v1/admin/media', ['file' => $this->pdf()], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('mime_type', 'application/pdf')
            ->assertJsonPath('folder', 'documents');

        Storage::disk('uploads')->assertExists(Media::sole()->path);
    }

    public function test_fake_pdf_is_rejected(): void
    {
        $fake = UploadedFile::fake()->createWithContent('evil.pdf', '<html><script>alert(1)</script></html>');

        $this->actingAs($this->admin())->spa()
            ->post('/api/v1/admin/media', ['file' => $fake], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->assertDatabaseCount('media', 0);
    }

    public function test_media_list_can_filter_documents(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->spa()->post('/api/v1/admin/media', ['file' => $this->pdf()], ['Accept' => 'application/json']);
        $this->actingAs($admin)->spa()->post('/api/v1/admin/media', ['file' => UploadedFile::fake()->image('a.png')], ['Accept' => 'application/json']);

        $this->actingAs($admin)->spa()->getJson('/api/v1/admin/media?type=document')->assertJsonCount(1, 'data');
        $this->actingAs($admin)->spa()->getJson('/api/v1/admin/media?type=image')->assertJsonCount(1, 'data');
    }

    public function test_whatsapp_and_analytics_settings_are_validated(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->spa()->putJson('/api/v1/admin/settings', [
            'site_name' => 'IR', 'whatsapp_number' => '<script>', 'ga4_measurement_id' => 'UA-123', 'facebook_pixel_id' => 'abc',
        ])->assertStatus(422)->assertJsonValidationErrors(['whatsapp_number', 'ga4_measurement_id', 'facebook_pixel_id']);

        $this->actingAs($admin)->spa()->putJson('/api/v1/admin/settings', [
            'site_name' => 'IR', 'whatsapp_enabled' => true, 'whatsapp_number' => '+8801711000000',
            'whatsapp_message' => 'Hello', 'ga4_measurement_id' => 'G-ABC123XYZ', 'facebook_pixel_id' => '1234567890',
        ])->assertOk();

        $this->getJson('/api/v1/settings')
            ->assertJsonPath('whatsapp_enabled', true)
            ->assertJsonPath('whatsapp_number', '+8801711000000')
            ->assertJsonPath('ga4_measurement_id', 'G-ABC123XYZ');
    }

    public function test_brochure_setting_and_product_datasheet_are_public(): void
    {
        $admin = $this->admin();
        $path = $this->actingAs($admin)->spa()->post('/api/v1/admin/media', ['file' => $this->pdf()], ['Accept' => 'application/json'])->json('path');

        $this->actingAs($admin)->spa()->putJson('/api/v1/admin/settings', ['site_name' => 'IR', 'brochure_file' => $path])->assertOk();
        $this->assertStringEndsWith($path, $this->getJson('/api/v1/settings')->json('brochure_file_url'));

        $this->actingAs($admin)->spa()->postJson('/api/v1/admin/products', [
            'name' => 'Bottle', 'status' => 'published', 'datasheet' => $path,
        ])->assertCreated();
        $this->assertStringEndsWith($path, $this->getJson('/api/v1/products/bottle')->json('data.datasheet_url'));

        // Arbitrary URLs are never accepted as documents.
        $this->actingAs($admin)->spa()->putJson('/api/v1/admin/settings', ['site_name' => 'IR', 'brochure_file' => 'https://evil.example/x.pdf'])
            ->assertStatus(422);
        $this->assertSame(1, Product::count());
    }
}
