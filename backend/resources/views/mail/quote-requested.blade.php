@php($md = \App\Support\MarkdownText::class)
<x-mail::message>
# New quotation request

<x-mail::table>
| | |
|:--|:--|
| **Product** | {{ $md::cell($quote->product_name ?: 'Custom / not listed') }} |
| **Quantity** | {{ $md::cell($quote->quantity) }} |
| **Specification** | {{ $md::cell($quote->specification ?: '-') }} |
| **Needed by** | {{ $quote->needed_by?->format('d M Y') ?: '—' }} |
| **Name** | {{ $md::cell($quote->name) }} |
| **Company** | {{ $md::cell($quote->company ?: '-') }} |
| **Email** | {{ $md::cell($quote->email) }} |
| **Phone** | {{ $md::cell($quote->phone) }} |
</x-mail::table>

@if ($quote->message)
**Additional details**

<x-mail::panel>
{!! nl2br(e($md::escape($quote->message))) !!}
</x-mail::panel>
@endif

Reply to this email to send the quotation to {{ $md::cell($quote->name) }} directly.

<x-mail::button :url="$adminUrl">
Open in admin panel
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
