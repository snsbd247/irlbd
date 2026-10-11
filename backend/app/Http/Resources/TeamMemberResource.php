<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TeamMemberResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'designation' => $this->designation,
            'photo' => $this->photo,
            'photo_url' => $this->url($this->photo),
            'signature' => $this->signature,
            'signature_url' => $this->url($this->signature),
            'message' => $this->message,
            'quote' => $this->quote,
            'is_founder' => $this->is_founder,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
