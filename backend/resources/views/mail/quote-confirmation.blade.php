@php($md = \App\Support\MarkdownText::class)
<x-mail::message>
# Thank you, {{ $md::cell($quote->name) }}

We have received your quotation request. Our sales team will contact you shortly.

<x-mail::panel>
**Your reference number: {{ $quote->reference }}**
</x-mail::panel>

<x-mail::table>
| | |
|:--|:--|
| **Product** | {{ $md::cell($quote->product_name ?: 'Custom / not listed') }} |
| **Quantity** | {{ $md::cell($quote->quantity) }} |
| **Needed by** | {{ $quote->needed_by?->format('d M Y') ?: '—' }} |
</x-mail::table>

You can check the status of your request at any time with this reference number and your email address.

<x-mail::button :url="$trackUrl">
Track my request
</x-mail::button>

If you did not submit this request, you can ignore this email.

{{ $siteName }}
</x-mail::message>
