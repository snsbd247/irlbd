<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $campaign->subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#10223d;">
@if ($campaign->preheader)
<div style="display:none;max-height:0;overflow:hidden;">{{ $campaign->preheader }}</div>
@endif
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px;">
<tr><td align="center">
  <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;">
    <tr><td style="padding:24px 32px;border-bottom:3px solid #1677e5;">
      <a href="{{ $siteUrl }}" style="text-decoration:none;color:#0b2545;font-size:20px;font-weight:bold;">
        @if ($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $siteName }}" height="48" style="height:48px;border:0;">@else{{ $siteName }}@endif
      </a>
    </td></tr>
    <tr><td style="padding:32px;font-size:15px;line-height:1.6;">
      {{-- Sanitized on save by the CMS (HTMLPurifier allow-list). --}}
      {!! $campaign->content !!}
    </td></tr>
    <tr><td style="padding:20px 32px;background:#0b2545;color:#cbd5e1;font-size:12px;line-height:1.5;">
      {{ $siteName }}@if ($address) &middot; {{ $address }}@endif<br>
      You are receiving this email because you subscribed on our website.
      <a href="{{ $unsubscribeUrl }}" style="color:#ffffff;">Unsubscribe</a>
    </td></tr>
  </table>
</td></tr>
</table>
</body>
</html>
