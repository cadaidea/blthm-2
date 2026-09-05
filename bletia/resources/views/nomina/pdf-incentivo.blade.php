<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 11px; color: #1a1a1a; margin: 0; }
    .head { border-bottom: 2px solid #161921; padding-bottom: 10px; margin-bottom: 14px; }
    .head h1 { margin: 0; font-size: 15px; }
    .head .emp { font-size: 10px; color: #555; margin-top: 2px; }
    .title { text-align: center; font-size: 13px; font-weight: bold; margin: 6px 0 4px; text-transform: uppercase; }
    .sub { text-align: center; font-size: 10px; color: #555; margin-bottom: 14px; }
    .meta { width: 100%; margin-bottom: 12px; }
    .meta td { font-size: 10px; padding: 3px 0; }
    table.det { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    table.det th { background: #161921; color: #fff; text-align: left; padding: 5px 8px; font-size: 10px; }
    table.det td { padding: 4px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
    .num { text-align: right; }
    .tot { font-weight: bold; background: #f4f6f8; font-size: 12px; }
    .anul { color: #b91c1c; font-weight: bold; }
</style>
</head>
<body>
    <div class="head">
        <h1>{{ $empresa['nombre'] }}</h1>
        <div class="emp">RUC/CI: {{ $empresa['ruc'] ?: '—' }} · {{ $empresa['direccion'] ?: '' }} · {{ $empresa['ciudad'] ?: '' }}</div>
    </div>

    <div class="title">Comprobante de Incentivo a Colaborador</div>
    <div class="sub">N° {{ $inc->folio ?: $inc->id }} @if($inc->estado === 'anulado')<span class="anul">· ANULADO</span>@endif</div>

    <table class="meta">
        <tr>
            <td><strong>Colaborador:</strong> {{ $inc->empleado->nombre ?? '' }}</td>
            <td><strong>C.I./RUC:</strong> {{ $inc->empleado->identificacion ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Fecha:</strong> {{ \Illuminate\Support\Carbon::parse($inc->fecha)->format('d/m/Y') }}</td>
            <td><strong>Método:</strong> {{ ucfirst($inc->metodo_pago ?? '—') }} @if($inc->nro_comprobante) · N° {{ $inc->nro_comprobante }}@endif</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Concepto:</strong> {{ $inc->concepto }}</td>
        </tr>
    </table>

    <table class="det">
        <tr><th>Detalle</th><th class="num">Valor</th></tr>
        <tr><td>Monto del incentivo</td><td class="num">${{ number_format($inc->monto, 2) }}</td></tr>
        @if((float)$inc->ret_renta > 0)<tr><td>(-) Retención en la fuente</td><td class="num">(${{ number_format($inc->ret_renta, 2) }})</td></tr>@endif
        <tr class="tot"><td>TOTAL ENTREGADO</td><td class="num">${{ number_format($inc->total, 2) }}</td></tr>
    </table>

    @if($inc->nota)<p style="font-size:10px;color:#555">{{ $inc->nota }}</p>@endif

    <p style="font-size:9px;color:#555;margin-top:14px">
        El presente incentivo se entrega por una colaboración puntual y no constituye relación laboral
        de dependencia ni genera obligaciones patronales conforme al Código del Trabajo del Ecuador.
        Declaro haber recibido conforme el valor detallado.
    </p>

    @include('nomina._firmas', [
        'elaborado' => $empresa['nombre'],
        'autorizado' => '',
        'recibe' => $inc->empleado->nombre ?? '',
        'recibe_id' => $inc->empleado->identificacion ?? '________',
    ])
</body>
</html>
