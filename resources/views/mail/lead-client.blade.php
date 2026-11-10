@extends('mail.layout')
@section('content')
  <p style="margin:0 0 12px;font-size:17px;font-weight:bold;">{!! str_replace('%NAME%', '<bdi>'.e($lead->full_name).'</bdi>', e(__('mail.lead.client_greeting', ['name' => '%NAME%'], $locale))) !!}</p>
  <p style="margin:0 0 20px;">{{ __('mail.lead.client_intro', [], $locale) }}</p>
  <div style="margin:0 0 8px;padding:14px 18px;background:#F7F8F6;border:1px solid #E5E9E6;border-radius:8px;">
    <div style="font-size:12px;color:#66706A;">{{ __('mail.lead.client_reference', [], $locale) }}</div>
    <div style="font-family:'Courier New',monospace;font-size:20px;letter-spacing:2px;color:#0B5D3B;font-weight:bold;" dir="ltr">{{ $lead->reference() }}</div>
  </div>

  @include('mail.partials.heading', ['text' => __('mail.lead.client_summary', [], $locale)])
  @include('mail.partials.details', ['rows' => $summaryRows])

  @if ($whatsappUrl)
    @include('mail.partials.button', ['url' => $whatsappUrl, 'label' => __('mail.lead.client_whatsapp', [], $locale)])
  @endif
  <p style="margin:20px 0 0;font-size:12px;color:#66706A;">{{ __('mail.lead.client_ignore', [], $locale) }}</p>
@endsection
