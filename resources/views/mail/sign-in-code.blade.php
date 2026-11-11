@extends('mail.layout')
@section('content')
  <p style="margin:0 0 12px;font-size:17px;font-weight:bold;">{{ __('mail.signin.greeting', ['name' => $name], $locale) }}</p>
  <p style="margin:0 0 18px;">{{ __('mail.signin.intro', [], $locale) }}</p>
  <div style="margin:0 0 18px;padding:18px;background:#073E2A;border-radius:10px;text-align:center;">
    <span style="font-family:'Courier New',monospace;font-size:34px;letter-spacing:10px;color:#D4B76A;font-weight:bold;" dir="ltr">{{ $code }}</span>
  </div>
  <p style="margin:0 0 8px;color:#66706A;font-size:13px;">{{ __('mail.signin.expires', ['minutes' => $minutes], $locale) }}</p>
  <p style="margin:0;color:#66706A;font-size:13px;">{{ __('mail.signin.ignore', [], $locale) }}</p>
@endsection
