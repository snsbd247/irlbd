<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadAndSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('uploads');
    }

    public function test_admin_can_upload_an_image(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 3000, 1500);

        $response = $this->actingAs($this->editor())->spa()->post('/api/v1/admin/media', ['file' => $file, 'folder' => 'products'], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('folder', 'products')
            ->assertJsonPath('mime_type', 'image/jpeg');

        $media = Media::sole();
        Storage::disk('uploads')->assertExists($media->path);
        // Re-encoded and downscaled to the max dimension.
        $this->assertSame(2000, $media->width);
        $this->assertSame(1000, $media->height);
        // Stored under a random name, never the client-supplied one.
        $this->assertStringNotContainsString('photo', $media->path);
        $this->assertStringStartsWith('products/', $response->json('path'));
    }

    public function test_svg_and_scripts_are_rejected(): void
    {
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $php = UploadedFile::fake()->createWithContent('shell.php.jpg', '<?php system($_GET["c"]); ?>');

        $this->actingAs($this->admin())->spa()->post('/api/v1/admin/media', ['file' => $svg], ['Accept' => 'application/json'])->assertStatus(422);
        $this->actingAs($this->admin())->spa()->post('/api/v1/admin/media', ['file' => $php], ['Accept' => 'application/json'])->assertStatus(422);

        $this->assertDatabaseCount('media', 0);
    }

    public function test_oversized_upload_is_rejected(): void
    {
        config(['cms.uploads.max_kb' => 100]);
        $file = UploadedFile::fake()->image('big.png')->size(500);

        $this->actingAs($this->admin())->spa()->post('/api/v1/admin/media', ['file' => $file], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_guests_and_unprivileged_users_cannot_upload(): void
    {
        $file = UploadedFile::fake()->image('a.png');

        $this->post('/api/v1/admin/media', ['file' => $file], ['Accept' => 'application/json'])->assertUnauthorized();
        $this->actingAs($this->support())->spa()->post('/api/v1/admin/media', ['file' => $file], ['Accept' => 'application/json'])->assertForbidden();
    }

    public function test_deleting_media_removes_the_file(): void
    {
        $admin = $this->admin();
        $path = $this->actingAs($admin)->spa()->post('/api/v1/admin/media', ['file' => UploadedFile::fake()->image('a.png')], ['Accept' => 'application/json'])->json('path');
        $media = Media::sole();

        $this->actingAs($admin)->spa()->deleteJson('/api/v1/admin/media/'.$media->id)->assertNoContent();

        Storage::disk('uploads')->assertMissing($path);
    }

    public function test_rich_text_is_sanitized(): void
    {
        $id = $this->actingAs($this->admin())->spa()->postJson('/api/v1/admin/pages', [
            'title' => 'Evil', 'template' => 'sections', 'status' => 'published',
            'content' => '<p onclick="steal()">Hi<script>alert(1)</script><a href="javascript:alert(1)">x</a><iframe src="https://evil.example"></iframe><img src="x" onerror="alert(1)"></p>',
        ])->assertCreated()->json('id');

        $html = Page::find($id)->content;
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringContainsString('Hi', $html);
    }

    public function test_javascript_links_are_rejected(): void
    {
        $page = Page::create(['title' => 'X', 'status' => 'published']);

        $this->actingAs($this->admin())->spa()->postJson("/api/v1/admin/pages/{$page->id}/sections", [
            'type' => 'cta_banner', 'button_url' => 'javascript:alert(1)',
        ])->assertStatus(422)->assertJsonValidationErrors('button_url');

        $this->actingAs($this->admin())->spa()->postJson('/api/v1/admin/menu-items', [
            'location' => 'header', 'label' => 'X', 'link_type' => 'url', 'url' => 'data:text/html,hi',
        ])->assertStatus(422)->assertJsonValidationErrors('url');
    }

    public function test_settings_update_and_map_url_allow_list(): void
    {
        $admin = $this->admin();
        $logo = $this->mediaPath('branding/logo.png');

        $this->actingAs($admin)->spa()->putJson('/api/v1/admin/settings', [
            'site_name' => 'IR Limited BD', 'logo_header' => $logo, 'topbar_enabled' => false,
        ])->assertOk()->assertJsonPath('values.site_name', 'IR Limited BD');

        $public = $this->getJson('/api/v1/settings')
            ->assertJsonPath('site_name', 'IR Limited BD')
            ->assertJsonPath('topbar_enabled', false);
        $this->assertStringEndsWith('branding/logo.png', $public->json('logo_header_url'));

        $this->actingAs($admin)->spa()->putJson('/api/v1/admin/settings', [
            'site_name' => 'IR', 'map_embed_url' => 'https://evil.example/maps/embed?x=1',
        ])->assertStatus(422);

        $this->actingAs($admin)->spa()->putJson('/api/v1/admin/settings', [
            'site_name' => 'IR', 'map_embed_url' => 'https://www.google.com/maps/embed?pb=abc',
        ])->assertOk();

        $this->actingAs($admin)->spa()->putJson('/api/v1/admin/settings', ['site_name' => 'IR', 'favicon' => 'https://evil.example/x.ico'])
            ->assertStatus(422);
    }

    public function test_installer_is_disabled_without_token(): void
    {
        $this->postJson('/api/install', ['token' => ''])->assertNotFound();
    }

    public function test_security_headers_are_sent(): void
    {
        $this->getJson('/api/v1/settings')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_cors_only_allows_configured_origin(): void
    {
        $this->withHeaders(['Origin' => 'http://localhost:5173'])->getJson('/api/v1/settings')
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
            ->assertHeader('Access-Control-Allow-Credentials', 'true');

        // Other origins never get their own origin echoed back, so browsers block them.
        $response = $this->withHeaders(['Origin' => 'https://evil.example'])->getJson('/api/v1/settings');
        $this->assertNotSame('https://evil.example', $response->headers->get('Access-Control-Allow-Origin'));
    }
}
