<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 11px; color: #1a1a1a; margin: 0; }
    .head { border-bottom: 2px solid #161921; padding-bottom: 10px; margin-bottom: 14px; }
    .head h1 { margin: 0; font-size: 15px; }
    .head .emp { font-size: 10px; color: #555; margin-top: 2px; }
    .title { text-align: center; font-size: 13px; font-weight: bold; margin: 6px 0 4px; text-transform: uppercase; letter-spacing: .5px; }
    .sub { text-align: center; font-size: 10px; color: #555; margin-bottom: 14px; }
    .meta { width: 100%; margin-bottom: 12px; }
    .meta td { font-size: 10px; padding: 3px 0; }
    table.det { width: 100%; border-collapse: collapse; }
    table.det th { background: #161921; color: #fff; text-align: left; padding: 5px 8px; font-size: 10px; }
    table.det td { padding: 4px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
    .num { text-align: right; }
    .tot td { font-weight: bold; background: #f4f6f8; border-top: 2px solid #ccc; }
    .anul { color: #b91c1c; font-weight: bold; }
    .cod { color: #555; }
</style>
</head>
<body>
    <div class="head">
        <h1>{{ $empresa['nombre'] }}</h1>
        <div class="emp">RUC/CI: {{ $empresa['ruc'] ?: '—' }} · {{ $empresa['direccion'] ?: '' }} · {{ $empresa['ciudad'] ?: '' }}</div>
    </div>

    <div class="title">Comprobante Contable</div>
    <div class="sub">Asiento N° {{ $asiento->numero ?: $asiento->id }} @if($asiento->estado === 'anulado')<span class="anul">· ANULADO</span>@endif</div>

    <table class="meta">
        <tr>
            <td><strong>Fecha:</strong> {{ \Illuminate\Support\Carbon::parse($asiento->fecha)->format('d/m/Y') }}</td>
            <td><strong>Origen:</strong> {{ ucfirst($asiento->origen ?: 'manual') }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Glosa:</strong> {{ $asiento->glosa }}</td>
        </tr>
    </table>

    <table class="det">
        <tr><th>Código</th><th>Cuenta</th><th>Detalle</th><th class="num">Debe</th><th class="num">Haber</th></tr>
        @foreach($asiento->lineas as $l)
        <tr>
            <td class="cod">{{ $l->cuenta->codigo ?? '' }}</td>
            <td>{{ $l->cuenta->nombre ?? '' }}</td>
            <td style="color:#555">{{ $l->detalle }}</td>
            <td class="num">{{ (float)$l->debe > 0 ? '$'.number_format($l->debe,2) : '' }}</td>
            <td class="num">{{ (float)$l->haber > 0 ? '$'.number_format($l->haber,2) : '' }}</td>
        </tr>
        @endforeach
        <tr class="tot">
            <td colspan="3">TOTALES</td>
            <td class="num">${{ number_format($asiento->debe,2) }}</td>
            <td class="num">${{ number_format($asiento->haber,2) }}</td>
        </tr>
    </table>

    @include('contable._firmas', ['recibe' => '', 'recibe_id' => ''])
</body>
</html>
