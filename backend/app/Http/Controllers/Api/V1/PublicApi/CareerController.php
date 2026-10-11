<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobApplicationForm;
use App\Http\Resources\JobPostResource;
use App\Mail\JobApplicationReceived;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Models\Setting;
use App\Services\CvStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CareerController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return JobPostResource::collection(JobPost::open()->ordered()->get());
    }

    public function show(string $slug): JobPostResource
    {
        // Published jobs stay readable after the deadline (marked closed), drafts never are.
        return new JobPostResource(JobPost::published()->where('slug', $slug)->firstOrFail());
    }

    public function apply(JobApplicationForm $request, string $slug, CvStorage $cvs): JsonResponse
    {
        $job = JobPost::published()->where('slug', $slug)->firstOrFail();
        $message = 'Thank you for applying! We will contact you if your profile matches the position.';

        if (! $job->isOpen()) {
            return response()->json(['message' => 'Applications for this position are closed.'], 422);
        }
        if ($request->filled('website')) {
            return response()->json(['message' => $message], 201);
        }

        $cv = $cvs->store($request->file('cv'));

        $application = JobApplication::create([
            ...$request->safe()->only(['name', 'email', 'phone', 'cover_letter']),
            'job_post_id' => $job->id,
            'job_title' => $job->title,
            'cv_path' => $cv['path'],
            'cv_original_name' => $cv['original'],
            'cv_mime' => $cv['mime'],
            'cv_size' => $cv['size'],
            'status' => 'new',
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 490, ''),
        ]);

        if ($to = Setting::get('careers_notify_email') ?: Setting::get('enquiry_notify_email')) {
            try {
                Mail::to($to)->send(new JobApplicationReceived($application));
            } catch (\Throwable $e) {
                Log::warning('Application notification failed: '.$e->getMessage());
            }
        }

        return response()->json(['message' => $message], 201);
    }
}
