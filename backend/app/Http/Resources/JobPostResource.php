<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class JobPostResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'department' => $this->department,
            'location' => $this->location,
            'employment_type' => $this->employment_type,
            'vacancies' => $this->vacancies,
            'salary' => $this->salary,
            'deadline' => $this->deadline?->toDateString(),
            'summary' => $this->summary,
            'description' => $this->when($this->isAdmin($request) || $request->route('slug') !== null, $this->description),
            'is_open' => $this->isOpen(),
            'status' => $this->when($this->isAdmin($request), $this->status),
            'applications_count' => $this->whenCounted('applications'),
            'published_at' => $this->date($this->published_at),
            'sort_order' => $this->sort_order,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
        ];
    }
}
