@php($__logo = \App\Services\PdfErp::logoEmpresa())
@if($__logo)<img class="logo-pdf" src="{{ $__logo }}" alt="{{ $empresa['nombre'] }}">@else<div class="marca">{{ $empresa['nombre'] }}</div>@endif
