@extends('mail.layout')
@section('content')
  <p style="margin:0 0 12px;font-size:17px;font-weight:bold;">{{ __('mail.staff.greeting', ['name' => $name], $locale) }}</p>
  <p style="margin:0 0 14px;">{{ $intro }}</p>
  @include('mail.partials.details', ['rows' => [[__('mail.staff.account', [], $locale), $email]]])
  @include('mail.partials.button', ['url' => $url, 'label' => __('mail.staff.button', [], $locale)])
  <p style="margin:14px 0 6px;color:#66706A;font-size:13px;">{{ __('mail.staff.expires', ['minutes' => $minutes], $locale) }}</p>
  <p style="margin:0;color:#66706A;font-size:13px;">{{ __('mail.staff.otp_note', [], $locale) }}</p>
@endsection
