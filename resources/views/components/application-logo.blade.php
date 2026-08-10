{{-- 128x128 is this file's real intrinsic size; the caller sizes it in CSS,
     and the attributes are here so the box is reserved before that lands. --}}
<img
    src="{{ asset('images/brand/logo-icon-color-128.png') }}"
    alt="{{ __('site.brand.name') }}"
    width="128"
    height="128"
    {{ $attributes }}
>
