{{-- Shared frame for every email: brand header, gold rule, content, footer. Inline styles only (email clients). --}}
@php($rtl = $locale === 'ar')
@php($align = $rtl ? 'right' : 'left')
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#F7F8F6;font-family:{{ $rtl ? "Tahoma, 'Segoe UI', Arial, sans-serif" : "'Helvetica Neue', Helvetica, Arial, sans-serif" }};color:#17211C;">
<span style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $preheader ?? '' }}</span>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7F8F6;">
  <tr><td align="center" style="padding:32px 12px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #E5E9E6;" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
      <tr><td style="background:#0B5D3B;padding:26px 32px;text-align:{{ $align }};">
        <img src="{{ asset('images/brand/logo-full-white-256.png') }}" alt="{{ __('mail.brand', [], $locale) }}" width="120" style="display:block;border:0;max-width:120px;height:auto;{{ $rtl ? 'margin-left:auto;' : '' }}">
        <div style="margin-top:12px;font-size:12px;letter-spacing:1px;color:#D4B76A;">{{ $kicker ?? '' }}</div>
      </td></tr>
      <tr><td style="height:4px;background:#B8903A;line-height:4px;font-size:0;">&nbsp;</td></tr>
      <tr><td style="padding:32px;text-align:{{ $align }};font-size:15px;line-height:1.8;">
        @yield('content')
      </td></tr>
      <tr><td style="background:#F7F8F6;padding:20px 32px;font-size:12px;line-height:1.7;color:#66706A;text-align:{{ $align }};border-top:1px solid #E5E9E6;">
        <div style="color:#8A6A22;font-weight:bold;">{{ __('mail.footer', [], $locale) }}</div>
        <div>{{ __('mail.automatic', [], $locale) }}</div>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
