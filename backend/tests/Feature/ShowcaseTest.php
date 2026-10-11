<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use App\Models\Page;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Database\Seeders\ContentSeeder;
use Database\Seeders\ShowcaseContentSeeder;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShowcaseTest extends TestCase
{
    public function test_gallery_album_crud_and_public_listing(): void
    {
        $admin = $this->admin();
        $a = $this->mediaPath('gallery/a.png');
        $b = $this->mediaPath('gallery/b.png');

        $id = $this->actingAs($admin)->spa()->postJson('/api/v1/admin/gallery-albums', [
            'title' => 'Trade Fair 2024', 'event_date' => '2024-01-10',
            'images' => [['path' => $a, 'alt' => 'Our stall'], ['path' => $b, 'alt' => 'Visitors']],
        ])->assertCreated()->assertJsonPath('slug', 'trade-fair-2024')->assertJsonCount(2, 'images')->json('id');

        $this->getJson('/api/v1/gallery')->assertOk()->assertJsonPath('0.images_count', 2);
        $this->getJson('/api/v1/gallery/trade-fair-2024')->assertOk()
            ->assertJsonPath('images.0.caption', 'Our stall')
            ->assertJsonPath('images.1.caption', 'Visitors');

        // Reorder photos by resubmitting the list.
        $this->actingAs($admin)->spa()->putJson("/api/v1/admin/gallery-albums/{$id}", [
            'title' => 'Trade Fair 2024', 'images' => [['path' => $b, 'alt' => 'Visitors']],
        ])->assertOk()->assertJsonCount(1, 'images');

        // Hidden and empty albums are not public.
        GalleryAlbum::create(['title' => 'Empty album']);
        GalleryAlbum::find($id)->update(['is_active' => false]);
        $this->getJson('/api/v1/gallery')->assertJsonCount(0);
        $this->getJson('/api/v1/gallery/trade-fair-2024')->assertNotFound();
    }

    public function test_gallery_requires_permission(): void
    {
        $this->actingAs($this->support())->spa()->getJson('/api/v1/admin/gallery-albums')->assertForbidden();
        $this->actingAs($this->editor())->spa()->getJson('/api/v1/admin/gallery-albums')->assertOk();
    }

    public function test_only_active_testimonials_are_shown(): void
    {
        $page = Page::create(['title' => 'Home', 'slug' => 'home-test', 'status' => 'published']);
        $page->sections()->create(['type' => 'testimonials', 'title' => 'Clients say']);
        Testimonial::create(['name' => 'Hidden', 'quote' => 'Hidden quote', 'is_active' => false]);
        Testimonial::create(['name' => 'Shown', 'quote' => 'Great supplier', 'rating' => 5]);

        $this->getJson('/api/v1/pages/home-test')->assertJsonCount(1, 'sections.0.data')->assertJsonPath('sections.0.data.0.name', 'Shown');

        $this->actingAs($this->editor())->spa()->postJson('/api/v1/admin/testimonials', ['name' => 'X', 'quote' => 'Q', 'rating' => 9])
            ->assertStatus(422)->assertJsonValidationErrors('rating');
    }

    public function test_team_grid_excludes_founder_unless_requested(): void
    {
        $page = Page::create(['title' => 'Team', 'slug' => 'team', 'status' => 'published']);
        $page->sections()->create(['type' => 'team_grid', 'settings' => ['include_founder' => false]]);
        TeamMember::create(['name' => 'Founder', 'is_founder' => true]);
        TeamMember::create(['name' => 'Director']);
        TeamMember::create(['name' => 'Inactive', 'is_active' => false]);

        $this->getJson('/api/v1/pages/team')->assertJsonCount(1, 'sections.0.data')->assertJsonPath('sections.0.data.0.name', 'Director');
    }

    public function test_video_section_accepts_only_https_urls(): void
    {
        $page = Page::create(['title' => 'About', 'status' => 'published']);

        $this->actingAs($this->admin())->spa()->postJson("/api/v1/admin/pages/{$page->id}/sections", [
            'type' => 'video', 'video_url' => 'javascript:alert(1)',
        ])->assertStatus(422)->assertJsonValidationErrors('video_url');

        $this->actingAs($this->admin())->spa()->postJson("/api/v1/admin/pages/{$page->id}/sections", [
            'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'settings' => ['background' => 'dark'],
        ])->assertCreated()->assertJsonPath('settings.background', 'dark');
    }

    public function test_showcase_seeder_is_idempotent_and_keeps_samples_hidden(): void
    {
        Storage::fake('uploads');
        $this->seed(ContentSeeder::class);
        $this->seed(ShowcaseContentSeeder::class);

        $this->assertSame(1, GalleryAlbum::count());
        $this->assertSame(0, Testimonial::where('is_active', true)->count());
        $this->assertSame('draft', Page::where('slug', 'management-team')->value('status'));
        $this->getJson('/api/v1/pages/gallery')->assertOk()->assertJsonPath('template', 'gallery');
        $this->getJson('/api/v1/pages/management-team')->assertNotFound();
        $this->assertSame(1, Page::where('slug', 'about')->first()->sections()->where('type', 'testimonials')->count());
    }
}
