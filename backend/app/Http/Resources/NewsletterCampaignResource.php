<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class NewsletterCampaignResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'preheader' => $this->preheader,
            'content' => $this->content,
            'status' => $this->status,
            'recipients_total' => $this->recipients_total,
            'sent_count' => $this->sent_count,
            'failed_count' => $this->failed_count,
            'started_at' => $this->date($this->started_at),
            'finished_at' => $this->date($this->finished_at),
            'author' => $this->author?->name,
            'created_at' => $this->date($this->created_at),
            'updated_at' => $this->date($this->updated_at),
        ];
    }
}
