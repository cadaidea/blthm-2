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

    <div class="title">Solicitud y Autorización de Vacaciones</div>

    <table class="meta">
        <tr>
            <td><strong>Empleado:</strong> {{ $vac->empleado->nombre ?? '' }}</td>
            <td><strong>C.I./RUC:</strong> {{ $vac->empleado->identificacion ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Cargo:</strong> {{ $vac->empleado->cargo ?? '—' }}</td>
            <td><strong>Registro N°:</strong> {{ $vac->folio ?: $vac->id }}</td>
        </tr>
        <tr>
            <td><strong>Fecha inicio:</strong> {{ \Illuminate\Support\Carbon::parse($vac->fecha_inicio)->format('d/m/Y') }}</td>
            <td><strong>Fecha fin:</strong> {{ \Illuminate\Support\Carbon::parse($vac->fecha_fin)->format('d/m/Y') }}</td>
        </tr>
    </table>

    <div class="box">
        <div class="lbl">Días tomados (calendario)</div>
        <div class="val">{{ number_format($vac->dias, 2) }}</div>
    </div>

    <table class="meta">
        <tr><td><strong>Saldo pendiente después de este período:</strong> {{ number_format($pendientesDespues, 2) }} días</td></tr>
    </table>

    @if($vac->nota)<p style="font-size:10px;color:#555">{{ $vac->nota }}</p>@endif

    <p style="font-size:9px;color:#555;margin-top:14px">
        El empleado solicita y la empresa autoriza el período de vacaciones detallado, conforme al
        Art. 69 del Código del Trabajo del Ecuador.
    </p>

    @include('nomina._firmas', [
        'elaborado' => $empresa['nombre'],
        'autorizado' => '',
        'recibe' => $vac->empleado->nombre ?? '',
        'recibe_id' => $vac->empleado->identificacion ?? '________',
    ])
</body>
</html>
