@php($md = \App\Support\MarkdownText::class)
<x-mail::message>
# New job application

<x-mail::table>
| | |
|:--|:--|
| **Position** | {{ $md::cell($application->job_title ?: 'General application') }} |
| **Name** | {{ $md::cell($application->name) }} |
| **Email** | {{ $md::cell($application->email) }} |
| **Phone** | {{ $md::cell($application->phone) }} |
| **CV** | {{ $md::cell($application->cv_original_name) }} |
</x-mail::table>

For privacy the CV is not attached. Open the admin panel to review and download it.

<x-mail::button :url="$adminUrl">
Review application
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
