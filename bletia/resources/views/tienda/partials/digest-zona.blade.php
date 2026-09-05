@php($df_zona_html = \App\Support\DigestZonas::render($zona ?? ''))
@if(trim($df_zona_html) !== '')
<link rel="stylesheet" href="{{ asset('css/digest-forms.css') }}?v={{ @filemtime(public_path('css/digest-forms.css')) }}">
{!! $df_zona_html !!}
<script src="{{ asset('js/digest-forms.js') }}?v={{ @filemtime(public_path('js/digest-forms.js')) }}"></script>
@endif
