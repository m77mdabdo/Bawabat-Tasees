{{-- Two-column table. $rows is a list of [label, value]; values are escaped, line breaks kept. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E5E9E6;border-radius:8px;border-collapse:separate;overflow:hidden;margin:0 0 8px;">
  @foreach ($rows as $i => [$label, $value])
  <tr>
    <td style="padding:11px 14px;background:{{ $i % 2 ? '#ffffff' : '#F7F8F6' }};font-size:12px;color:#0B5D3B;width:36%;vertical-align:top;font-weight:bold;">{{ $label }}</td>
    <td style="padding:11px 14px;background:{{ $i % 2 ? '#ffffff' : '#F7F8F6' }};font-size:14px;color:#17211C;vertical-align:top;" dir="auto">{!! nl2br(e($value)) !!}</td>
  </tr>
  @endforeach
</table>
