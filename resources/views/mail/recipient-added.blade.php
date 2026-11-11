@extends('mail.layout')
@section('content')
  <p style="margin:0 0 12px;font-size:17px;font-weight:bold;">{{ __('mail.recipient.greeting', ['name' => $name], $locale) }}</p>
  <p style="margin:0 0 18px;">{{ $intro }}</p>
  <p style="margin:0;color:#66706A;font-size:13px;">{{ __('mail.recipient.outro', [], $locale) }}</p>
@endsection
