@php($md = \App\Support\MarkdownText::class)
<x-mail::message>
# New website enquiry

**Subject:** {{ $md::cell($enquiry->subject) }}

<x-mail::table>
| | |
|:--|:--|
| **Name** | {{ $md::cell($enquiry->name) }} |
| **Email** | {{ $md::cell($enquiry->email) }} |
| **Phone** | {{ $md::cell($enquiry->phone ?: '-') }} |
| **Received** | {{ $enquiry->created_at->format('d M Y, H:i') }} |
</x-mail::table>

**Message**

<x-mail::panel>
{!! nl2br(e($md::escape($enquiry->message))) !!}
</x-mail::panel>

Reply to this email to answer {{ $md::cell($enquiry->name) }} directly.

<x-mail::button :url="$adminUrl">
Open in admin panel
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
