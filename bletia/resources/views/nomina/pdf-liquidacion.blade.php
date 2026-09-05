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
    .meta td { font-size: 10px; padding: 2px 0; }
    table.det { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    table.det th { background: #161921; color: #fff; text-align: left; padding: 5px 8px; font-size: 10px; }
    table.det td { padding: 4px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
    .num { text-align: right; }
    .tot { font-weight: bold; background: #eef7ee; font-size: 12px; }
</style>
</head>
<body>
    <div class="head">
        <h1>{{ $empresa['nombre'] }}</h1>
        <div class="emp">RUC/CI: {{ $empresa['ruc'] ?: '—' }} · {{ $empresa['direccion'] ?: '' }} · {{ $empresa['ciudad'] ?: '' }}</div>
    </div>

    <div class="title">Acta de Finiquito / Liquidación de Haberes</div>

    <table class="meta">
        <tr>
            <td><strong>Empleado:</strong> {{ $liq->empleado->nombre ?? '' }}</td>
            <td><strong>C.I./RUC:</strong> {{ $liq->empleado->identificacion ?? '—' }}</td>
            <td><strong>Liquidación N°:</strong> {{ $liq->folio ?: $liq->id }}</td>
        </tr>
        <tr>
            <td><strong>Fecha ingreso:</strong> {{ $liq->empleado->fecha_ingreso ? \Illuminate\Support\Carbon::parse($liq->empleado->fecha_ingreso)->format('d/m/Y') : '—' }}</td>
            <td><strong>Fecha salida:</strong> {{ \Illuminate\Support\Carbon::parse($liq->fecha_salida)->format('d/m/Y') }}</td>
            <td><strong>Tiempo de servicio:</strong> {{ $liq->tiempo_servicio ?: '—' }}</td>
            <td><strong>Motivo:</strong> {{ ucfirst(str_replace('_',' ',$liq->motivo ?? '—')) }}</td>
        </tr>
    </table>

    <table class="det">
        <tr><th>Concepto</th><th class="num">Valor</th></tr>
        <tr><td>Décimo tercero proporcional</td><td class="num">${{ number_format($liq->decimo_tercero,2) }}</td></tr>
        <tr><td>Décimo cuarto proporcional</td><td class="num">${{ number_format($liq->decimo_cuarto,2) }}</td></tr>
        <tr><td>Vacaciones no gozadas</td><td class="num">${{ number_format($liq->vacaciones,2) }}</td></tr>
        <tr><td>Fondos de reserva</td><td class="num">${{ number_format($liq->fondos_reserva,2) }}</td></tr>
        @if((float)$liq->indemnizacion > 0)<tr><td>Indemnización Art. 188 ({{ $liq->anios_servicio }} año(s), mejor remun. ${{ number_format($liq->mejor_remuneracion,2) }})</td><td class="num">${{ number_format($liq->indemnizacion,2) }}</td></tr>@endif
        @if((float)$liq->bonificacion_desahucio > 0)<tr><td>Bonificación por desahucio Art. 185</td><td class="num">${{ number_format($liq->bonificacion_desahucio,2) }}</td></tr>@endif
        @if((float)$liq->otros>0)<tr><td>Otros haberes</td><td class="num">${{ number_format($liq->otros,2) }}</td></tr>@endif
        @if((float)$liq->descuentos>0)<tr><td>(-) Descuentos</td><td class="num">(${{ number_format($liq->descuentos,2) }})</td></tr>@endif
        <tr class="tot"><td>TOTAL A PAGAR</td><td class="num">${{ number_format($liq->total,2) }}</td></tr>
    </table>

    @if($liq->detalle)<p style="font-size:10px;color:#555">{{ $liq->detalle }}</p>@endif

    <p style="font-size:9px;color:#555;margin-top:14px">
        Declaro haber recibido de {{ $empresa['nombre'] }} la cantidad total detallada, por concepto de
        liquidación de haberes, quedando en paz y salvo por todo concepto derivado de la relación laboral.
    </p>

    @include('nomina._firmas', [
        'elaborado' => $empresa['nombre'],
        'autorizado' => '',
        'recibe' => $liq->empleado->nombre ?? '',
        'recibe_id' => $liq->empleado->identificacion ?? '________',
    ])
</body>
</html>
