<!doctype html><html><head><meta charset="utf-8"><style>
@page { margin: 0; }
* { font-family: DejaVu Sans, sans-serif; }
body { margin: 0; }
.et { width: 240px; height: 156px; padding: 8px 10px; box-sizing: border-box; page-break-after: always; }
.et:last-child { page-break-after: auto; }
.marca { font-size: 11px; font-weight: bold; }
.prod { font-size: 13px; font-weight: bold; margin: 3px 0; }
.cli { font-size: 11px; }
.muted { color: #555; font-size: 9px; }
.bulto { float: right; border: 2px solid #161921; border-radius: 6px; padding: 4px 8px; font-size: 16px; font-weight: bold; }
</style></head><body>
@foreach($items as $it)
    @for($b = 1; $b <= max(1,(int)$it['bultos']); $b++)
    <div class="et">
        <span class="bulto">{{ $b }} / {{ max(1,(int)$it['bultos']) }}</span>
        <div class="marca">{{ $empresa['nombre'] }}</div>
        <div class="prod">{{ $it['nombre'] }}</div>
        <div class="cli">{{ $cliente['nombre'] }}</div>
        <div class="muted">Compra: {{ $fecha }} · Destino: {{ $cliente['ciudad'] ?: $empresa['ciudad'] }}</div>
        <div class="muted">Pedido N° {{ $numero }}</div>
    </div>
    @endfor
@endforeach
    @include('tienda.partials.cookies')
</body></html>
