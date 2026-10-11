<?php

namespace Tests\Feature;

use App\Mail\JobApplicationReceived;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CareersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('uploads');
    }

    protected function job(array $attributes = []): JobPost
    {
        return JobPost::create($attributes + ['title' => 'Production Supervisor', 'status' => 'published', 'employment_type' => 'full_time']);
    }

    protected function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('My CV.pdf', "%PDF-1.4\n1 0 obj << >> endobj\n%%EOF");
    }

    protected function apply(string $slug, array $overrides = [])
    {
        return $this->post("/api/v1/jobs/{$slug}/apply", $overrides + [
            'name' => 'Rahim Uddin', 'email' => 'Rahim@Example.com', 'phone' => '01711000000',
            'cover_letter' => 'I have 5 years of experience.', 'consent' => '1', 'cv' => $this->pdf(),
        ], ['Accept' => 'application/json']);
    }

    public function test_only_open_published_jobs_are_listed(): void
    {
        $this->job(['title' => 'Open job']);
        $this->job(['title' => 'Draft job', 'status' => 'draft']);
        $this->job(['title' => 'Expired job', 'deadline' => now()->subDay()->toDateString()]);
        $this->job(['title' => 'Last day', 'deadline' => now()->toDateString()]);

        $titles = collect($this->getJson('/api/v1/jobs')->assertOk()->json())->pluck('title')->all();
        $this->assertEqualsCanonicalizing(['Open job', 'Last day'], $titles);

        $this->getJson('/api/v1/jobs/draft-job')->assertNotFound();
        $this->getJson('/api/v1/jobs/expired-job')->assertOk()->assertJsonPath('is_open', false);
    }

    public function test_application_stores_cv_privately(): void
    {
        $job = $this->job();

        $this->apply($job->slug)->assertCreated();

        $application = JobApplication::sole();
        $this->assertSame('rahim@example.com', $application->email);
        $this->assertSame('Production Supervisor', $application->job_title);
        $this->assertStringStartsWith('cvs/', $application->cv_path);
        Storage::disk('local')->assertExists($application->cv_path);
        // Never written to the public uploads disk.
        $this->assertSame([], Storage::disk('uploads')->allFiles());
        $this->assertSame('application/pdf', $application->cv_mime);
    }

    public function test_application_validation(): void
    {
        $job = $this->job();

        $this->post("/api/v1/jobs/{$job->slug}/apply", ['email' => 'bad'], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'phone', 'cv', 'consent']);
    }

    public function test_disguised_or_dangerous_files_are_rejected(): void
    {
        $job = $this->job();
        $php = UploadedFile::fake()->createWithContent('cv.pdf', '<?php system($_GET["c"]); ?>');
        $html = UploadedFile::fake()->createWithContent('cv.docx', '<html><script>alert(1)</script></html>');
        $exe = UploadedFile::fake()->create('cv.exe', 10, 'application/octet-stream');

        foreach ([$php, $html, $exe] as $file) {
            $this->apply($job->slug, ['cv' => $file])->assertStatus(422);
        }
        $this->assertDatabaseCount('job_applications', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_closed_and_draft_jobs_reject_applications(): void
    {
        $this->apply($this->job(['title' => 'Old', 'deadline' => now()->subWeek()->toDateString()])->slug)->assertStatus(422);
        $this->apply($this->job(['title' => 'Hidden', 'status' => 'draft'])->slug)->assertNotFound();
        $this->assertDatabaseCount('job_applications', 0);
    }

    public function test_hr_can_review_and_download_cv_but_others_cannot(): void
    {
        Mail::fake();
        app(SettingsService::class)->save(['site_name' => 'IR', 'careers_notify_email' => 'hr@example.com']);
        $this->apply($this->job()->slug)->assertCreated();
        Mail::assertSent(JobApplicationReceived::class, fn ($m) => $m->hasTo('hr@example.com') && $m->attachments === [] && $m->rawAttachments === []);

        $id = JobApplication::sole()->id;
        $hr = $this->makeUser('hr');

        $this->get("/api/v1/admin/job-applications/{$id}/cv", ['Accept' => 'application/json'])->assertUnauthorized();
        $this->actingAs($this->support())->spa()->get("/api/v1/admin/job-applications/{$id}/cv")->assertForbidden();
        $this->actingAs($this->editor())->spa()->getJson('/api/v1/admin/job-applications')->assertForbidden();

        $download = $this->actingAs($hr)->spa()->get("/api/v1/admin/job-applications/{$id}/cv")->assertOk();
        $this->assertStringContainsString('attachment;', $download->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Rahim-Uddin-CV.pdf', $download->headers->get('Content-Disposition'));

        $this->actingAs($hr)->spa()->getJson("/api/v1/admin/job-applications/{$id}")->assertOk()->assertJsonMissingPath('cv_path');
        $this->actingAs($hr)->spa()->putJson("/api/v1/admin/job-applications/{$id}", ['status' => 'shortlisted'])->assertOk();
        $this->actingAs($hr)->spa()->postJson('/api/v1/admin/job-posts', ['title' => 'QC Officer', 'employment_type' => 'full_time', 'status' => 'published'])->assertCreated();

        // Deleting the application removes the private CV file too.
        $path = JobApplication::find($id)->cv_path;
        $this->actingAs($hr)->spa()->deleteJson("/api/v1/admin/job-applications/{$id}")->assertNoContent();
        Storage::disk('local')->assertMissing($path);
    }

    public function test_old_applications_and_cvs_are_pruned(): void
    {
        $job = $this->job();
        $this->apply($job->slug)->assertCreated();
        $this->apply($job->slug, ['email' => 'new@example.com'])->assertCreated();
        [$old, $new] = \App\Models\JobApplication::orderBy('id')->get()->all();
        $old->forceFill(['created_at' => now()->subDays(400)])->saveQuietly();

        $this->artisan('cms:prune-applications', ['--dry-run' => true])->expectsOutputToContain('1 application(s)')->assertSuccessful();
        $this->assertSame(2, \App\Models\JobApplication::count());

        $this->artisan('cms:prune-applications')->assertSuccessful();
        $this->assertSame([$new->id], \App\Models\JobApplication::pluck('id')->all());
        Storage::disk('local')->assertMissing($old->cv_path);
        Storage::disk('local')->assertExists($new->cv_path);

        app(\App\Services\SettingsService::class)->save(['cv_retention_days' => '0']);
        $new->forceFill(['created_at' => now()->subDays(5000)])->saveQuietly();
        $this->artisan('cms:prune-applications')->expectsOutputToContain('keep forever')->assertSuccessful();
        $this->assertSame(1, \App\Models\JobApplication::count());
    }
}

