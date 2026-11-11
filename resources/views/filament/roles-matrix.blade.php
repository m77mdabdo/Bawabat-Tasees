{{-- What each role may do (App\Auth\PermissionMatrix). --}}
<div style="overflow-x:auto">
  <table style="width:100%;border-collapse:collapse;font-size:.85rem">
    <thead>
      <tr>
        <th style="text-align:start;padding:.5rem;border-bottom:1px solid rgba(0,0,0,.1)">{{ __('Permission') }}</th>
        @foreach ($roles as $role)
          <th style="padding:.5rem;border-bottom:1px solid rgba(0,0,0,.1);white-space:nowrap">{{ __($role->label()) }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach ($permissions as $name => $allowed)
        <tr>
          <td style="padding:.45rem .5rem;border-bottom:1px solid rgba(0,0,0,.05)">{{ __('permission.'.$name) }}</td>
          @foreach ($roles as $role)
            <td style="text-align:center;padding:.45rem;border-bottom:1px solid rgba(0,0,0,.05)">
              @if (in_array($role->value, $allowed, true))
                <span style="color:#0B5D3B;font-weight:700" aria-label="{{ __('Yes') }}">✓</span>
              @else
                <span style="color:#9ca3af" aria-label="{{ __('No') }}">—</span>
              @endif
            </td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
  </table>
  <ul style="margin-top:1rem;font-size:.8rem;color:#66706A;list-style:none;padding:0">
    @foreach ($roles as $role)
      <li style="margin:.25rem 0"><strong>{{ __($role->label()) }}:</strong> {{ __($role->description()) }}</li>
    @endforeach
  </ul>
</div>
