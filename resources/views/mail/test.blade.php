@extends('mail.layout')
@section('content')
  <p style="margin:0 0 12px;font-size:16px;">{{ __('mail.test.body', [], $locale) }}</p>
  <p style="margin:0;color:#66706A;font-size:13px;" dir="ltr">{{ __('mail.test.sent_at', ['time' => $time], $locale) }}</p>
@endsection
