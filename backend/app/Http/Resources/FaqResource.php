<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class FaqResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'answer' => $this->answer,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
