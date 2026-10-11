<?php

namespace App\Http\Requests\Admin;

use App\Models\JobPost;

class JobPostRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'slug' => $this->slug('job_posts'),
            'department' => 'nullable|string|max:120',
            'location' => 'nullable|string|max:150',
            'employment_type' => 'required|in:'.implode(',', JobPost::TYPES),
            'vacancies' => 'nullable|integer|min:1|max:999',
            'salary' => 'nullable|string|max:120',
            'deadline' => 'nullable|date',
            'summary' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:100000',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date',
            ...$this->seo(),
        ];
    }
}
