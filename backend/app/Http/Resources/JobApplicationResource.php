<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class JobApplicationResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_post_id' => $this->job_post_id,
            'job_title' => $this->job_title,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'cover_letter' => $this->cover_letter,
            'cv_original_name' => $this->cv_original_name,
            'cv_mime' => $this->cv_mime,
            'cv_size' => $this->cv_size,
            'status' => $this->status,
            'admin_note' => $this->admin_note,
            'ip_address' => $this->ip_address,
            'read_at' => $this->date($this->read_at),
            'created_at' => $this->date($this->created_at),
        ];
    }
}
