<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 11px; color: #1a1a1a; margin: 0; }
    .head { border-bottom: 2px solid #161921; padding-bottom: 10px; margin-bottom: 14px; }
    .head h1 { margin: 0; font-size: 15px; }
    .head .emp { font-size: 10px; color: #555; margin-top: 2px; }
    .title { text-align: center; font-size: 13px; font-weight: bold; margin: 6px 0 14px; text-transform: uppercase; }
    .meta { width: 100%; margin-bottom: 12px; }
    .meta td { font-size: 10px; padding: 3px 0; }
    .box { border: 1px solid #ccc; border-radius: 4px; padding: 12px; text-align: center; margin: 14px 0; }
    .box .lbl { font-size: 10px; color: #555; text-transform: uppercase; }
    .box .val { font-size: 20px; font-weight: bold; }
</style>
</head>
<body>
    <div class="head">
        <h1>{{ $empresa['nombre'] }}</h1>
        <div class="emp">RUC/CI: {{ $empresa['ruc'] ?: '—' }} · {{ $empresa['direccion'] ?: '' }} · {{ $empresa['ciudad'] ?: '' }}</div>
    </div>

    <div class="title">Recibo de Pago · {{ \App\Models\PagoBeneficio::TIPOS[$pago->tipo] ?? $pago->tipo }}</div>

    <table class="meta">
        <tr>
            <td><strong>Empleado:</strong> {{ $pago->empleado->nombre ?? '' }}</td>
            <td><strong>C.I./RUC:</strong> {{ $pago->empleado->identificacion ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Recibo N°:</strong> {{ $pago->folio ?: $pago->id }}</td>
            <td><strong>Fecha:</strong> {{ \Illuminate\Support\Carbon::parse($pago->fecha)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><strong>Periodo:</strong> {{ $pago->periodo ?: '—' }}</td>
            <td><strong>Método:</strong> {{ ucfirst($pago->metodo_pago ?? '—') }} @if($pago->nro_comprobante) · N° {{ $pago->nro_comprobante }}@endif</td>
        </tr>
    </table>

    <div class="box">
        <div class="lbl">Valor pagado</div>
        <div class="val">${{ number_format($pago->monto,2) }}</div>
    </div>

    @if($pago->detalle)<p style="font-size:10px;color:#555">{{ $pago->detalle }}</p>@endif

    @include('nomina._firmas', [
        'elaborado' => $empresa['nombre'],
        'autorizado' => '',
        'recibe' => $pago->empleado->nombre ?? '',
        'recibe_id' => $pago->empleado->identificacion ?? '________',
    ])
</body>
</html>
