@php($adsActivo = \App\Models\Ajuste::get('adsense_activo') === '1')
@if($adsActivo)
@php($slot = $slot ?? null)
<div class="t-ad-slot" style="margin:32px 0">
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-client="ca-pub-4712910980244467"
         @if($slot) data-ad-slot="{{ $slot }}" @endif
         data-ad-format="auto"
         data-full-width-responsive="true"></ins>
</div>
<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
@endif
