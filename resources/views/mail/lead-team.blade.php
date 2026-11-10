@extends('mail.layout')
@section('content')
  <p style="margin:0 0 18px;font-size:16px;">{{ $intro }}</p>
  <div style="margin:0 0 8px;padding:14px 18px;background:#073E2A;border-radius:8px;">
    <span style="font-family:'Courier New',monospace;font-size:19px;letter-spacing:2px;color:#D4B76A;" dir="ltr">{{ $lead->reference() }}</span>
  </div>

  @include('mail.partials.heading', ['text' => __('mail.lead.client_section', [], $locale)])
  @include('mail.partials.details', ['rows' => $clientRows])

  @include('mail.partials.heading', ['text' => __('mail.lead.request_section', [], $locale)])
  @include('mail.partials.details', ['rows' => $requestRows])

  @if ($lead->message)
    @include('mail.partials.heading', ['text' => __('mail.lead.message', [], $locale)])
    <div style="padding:14px 16px;border-inline-start:3px solid #B8903A;background:#F7F8F6;color:#17211C;white-space:pre-line;" dir="auto">{{ $lead->message }}</div>
  @endif

  @if ($sourceRows)
    @include('mail.partials.heading', ['text' => __('mail.lead.source_section', [], $locale)])
    @include('mail.partials.details', ['rows' => $sourceRows])
  @endif

  @include('mail.partials.button', ['url' => $dashboardUrl, 'label' => __('mail.lead.open_dashboard', [], $locale)])
@endsection
