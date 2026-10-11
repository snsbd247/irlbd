<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\JobApplicationStatusRequest;
use App\Http\Resources\JobApplicationResource;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobApplicationController extends CrudController
{
    protected string $model = JobApplication::class;

    protected string $resource = JobApplicationResource::class;

    protected string $request = JobApplicationStatusRequest::class;

    protected array $searchable = ['name', 'email', 'phone', 'job_title'];

    protected array $filters = ['status' => 'status', 'job' => 'job_post_id'];

    protected array $order = ['created_at', 'desc'];

    protected array $sortableColumns = ['id', 'created_at', 'name', 'status'];

    public function show($id)
    {
        $application = $this->find($id);
        $this->authorize('view', $application);
        $application->read_at ??= now();
        $application->save();

        return new JobApplicationResource($application);
    }

    public function store()
    {
        abort(405, 'Applications are submitted from the careers page only.');
    }

    /** Streams the private CV file. Always a download (never rendered inline). */
    public function cv($id): StreamedResponse
    {
        $application = $this->find($id);
        $this->authorize('view', $application);
        abort_unless(Storage::disk('local')->exists($application->cv_path), 404, 'CV file not found.');

        app(\App\Services\ActivityLogger::class)->log('cv_downloaded', $application);
        $ext = pathinfo($application->cv_path, PATHINFO_EXTENSION);
        $name = preg_replace('/[^A-Za-z0-9_-]+/', '-', $application->name).'-CV.'.$ext;

        return Storage::disk('local')->download($application->cv_path, $name, [
            'Content-Type' => $application->cv_mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
