<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 11px; color: #1a1a1a; margin: 0; }
    .page { page-break-after: always; }
    .page:last-child { page-break-after: auto; }
    .copia-tag { text-align: right; font-size: 9px; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
    .head { border-bottom: 2px solid #161921; padding-bottom: 10px; margin-bottom: 14px; }
    .head h1 { margin: 0; font-size: 15px; }
    .head .emp { font-size: 10px; color: #555; margin-top: 2px; }
    .title { text-align: center; font-size: 13px; font-weight: bold; margin: 6px 0 14px; text-transform: uppercase; letter-spacing: .5px; }
    .meta { width: 100%; margin-bottom: 12px; }
    .meta td { font-size: 10px; padding: 2px 0; }
    table.det { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    table.det th { background: #161921; color: #fff; text-align: left; padding: 5px 8px; font-size: 10px; }
    table.det td { padding: 4px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
    .num { text-align: right; }
    .tot { font-weight: bold; }
    .liq { background: #eef7ee; font-weight: bold; font-size: 12px; }
    .cols { width: 100%; }
    .cols td { vertical-align: top; width: 50%; padding: 0 6px; }
    .firmas { width: 100%; margin-top: 55px; border-collapse: collapse; }
    .firmas td { width: 33%; text-align: center; padding: 0 10px; vertical-align: bottom; }
    .firmas .line { border-top: 1px solid #000; padding-top: 6px; font-size: 10px; }
    .firmas .rol { color: #555; }
</style>
</head>
<body>

{{-- ============ HOJA 1: COPIA EMPLEADO (sin costos internos) ============ --}}
<div class="page">
    <div class="copia-tag">Copia empleado</div>
    <div class="head">
        <h1>{{ $empresa['nombre'] }}</h1>
        <div class="emp">RUC/CI: {{ $empresa['ruc'] ?: '—' }} · {{ $empresa['direccion'] ?: '' }} · {{ $empresa['ciudad'] ?: '' }} · {{ $empresa['telefono'] ?: '' }}</div>
    </div>

    <div class="title">Rol de Pagos {{ $rol->nombreMes() }} {{ $rol->anio }}</div>

    <table class="meta">
        <tr>
            <td><strong>Empleado:</strong> {{ $rol->empleado->nombre ?? '' }}</td>
            <td><strong>C.I./RUC:</strong> {{ $rol->empleado->identificacion ?? '—' }}</td>
            <td><strong>Rol N°:</strong> {{ $rol->folio ?: $rol->id }}</td>
        </tr>
        <tr>
            <td><strong>Cargo:</strong> {{ $rol->empleado->cargo ?? '—' }}</td>
            <td><strong>Relación:</strong> {{ $rol->relacion === 'honorarios' ? 'Honorarios' : 'Dependencia' }}</td>
            <td><strong>Fecha ingreso:</strong> {{ $rol->empleado->fecha_ingreso ? \Illuminate\Support\Carbon::parse($rol->empleado->fecha_ingreso)->format('d/m/Y') : '—' }}</td>
        </tr>
    </table>

    <table class="cols"><tr>
        <td>
            <table class="det">
                <tr><th colspan="2">Ingresos</th></tr>
                <tr><td>Sueldo</td><td class="num">${{ number_format($rol->sueldo,2) }}</td></tr>
                @if((float)$rol->horas_extra>0)<tr><td>Horas extra</td><td class="num">${{ number_format($rol->horas_extra,2) }}</td></tr>@endif
                @if((float)$rol->comisiones>0)<tr><td>Comisiones</td><td class="num">${{ number_format($rol->comisiones,2) }}</td></tr>@endif
                @if((float)$rol->bonos>0)<tr><td>Bonos</td><td class="num">${{ number_format($rol->bonos,2) }}</td></tr>@endif
                @if((float)$rol->otros_ingresos>0)<tr><td>Otros</td><td class="num">${{ number_format($rol->otros_ingresos,2) }}</td></tr>@endif
                <tr class="tot"><td>Total ingresos</td><td class="num">${{ number_format($rol->total_ingresos,2) }}</td></tr>
            </table>
        </td>
        <td>
            <table class="det">
                <tr><th colspan="2">Descuentos</th></tr>
                @if((float)$rol->aporte_personal>0)<tr><td>Aporte IESS (9,45%)</td><td class="num">${{ number_format($rol->aporte_personal,2) }}</td></tr>@endif
                @if((float)$rol->anticipos>0)<tr><td>Anticipos</td><td class="num">${{ number_format($rol->anticipos,2) }}</td></tr>@endif
                @if((float)$rol->prestamos_iess>0)<tr><td>Préstamos IESS</td><td class="num">${{ number_format($rol->prestamos_iess,2) }}</td></tr>@endif
                @if((float)$rol->ret_renta>0)<tr><td>Retención renta</td><td class="num">${{ number_format($rol->ret_renta,2) }}</td></tr>@endif
                @if((float)$rol->otros_descuentos>0)<tr><td>Otros</td><td class="num">${{ number_format($rol->otros_descuentos,2) }}</td></tr>@endif
                <tr class="tot"><td>Total descuentos</td><td class="num">${{ number_format($rol->total_descuentos,2) }}</td></tr>
            </table>
        </td>
    </tr></table>

    <table class="det">
        <tr class="liq"><td>LÍQUIDO A RECIBIR</td><td class="num">${{ number_format($rol->liquido,2) }}</td></tr>
    </table>

    <table class="firmas">
        <tr>
            <td><div class="line"><strong>Elaborado por</strong><br>{{ $empresa['nombre'] }}<br><span class="rol">Responsable de nómina</span></div></td>
            <td><div class="line"><strong>Autorizado por</strong><br>&nbsp;<br><span class="rol">Gerencia</span></div></td>
            <td><div class="line"><strong>Recibí conforme</strong><br>{{ $rol->empleado->nombre ?? '' }}<br><span class="rol">C.I. {{ $rol->empleado->identificacion ?? '________' }}</span></div></td>
        </tr>
    </table>
</div>

{{-- ============ HOJA 2: COPIA EMPRESA (completa con provisiones) ============ --}}
<div class="page">
    <div class="copia-tag">Copia empresa · archivo</div>
    <div class="head">
        <h1>{{ $empresa['nombre'] }}</h1>
        <div class="emp">RUC/CI: {{ $empresa['ruc'] ?: '—' }} · {{ $empresa['direccion'] ?: '' }} · {{ $empresa['ciudad'] ?: '' }} · {{ $empresa['telefono'] ?: '' }}</div>
    </div>

    <div class="title">Rol de Pagos {{ $rol->nombreMes() }} {{ $rol->anio }}</div>

    <table class="meta">
        <tr>
            <td><strong>Empleado:</strong> {{ $rol->empleado->nombre ?? '' }}</td>
            <td><strong>C.I./RUC:</strong> {{ $rol->empleado->identificacion ?? '—' }}</td>
            <td><strong>Rol N°:</strong> {{ $rol->folio ?: $rol->id }}</td>
        </tr>
        <tr>
            <td><strong>Cargo:</strong> {{ $rol->empleado->cargo ?? '—' }}</td>
            <td><strong>Relación:</strong> {{ $rol->relacion === 'honorarios' ? 'Honorarios' : 'Dependencia' }}</td>
            <td><strong>Fecha ingreso:</strong> {{ $rol->empleado->fecha_ingreso ? \Illuminate\Support\Carbon::parse($rol->empleado->fecha_ingreso)->format('d/m/Y') : '—' }}</td>
        </tr>
    </table>

    <table class="cols"><tr>
        <td>
            <table class="det">
                <tr><th colspan="2">Ingresos</th></tr>
                <tr><td>Sueldo</td><td class="num">${{ number_format($rol->sueldo,2) }}</td></tr>
                @if((float)$rol->horas_extra>0)<tr><td>Horas extra</td><td class="num">${{ number_format($rol->horas_extra,2) }}</td></tr>@endif
                @if((float)$rol->comisiones>0)<tr><td>Comisiones</td><td class="num">${{ number_format($rol->comisiones,2) }}</td></tr>@endif
                @if((float)$rol->bonos>0)<tr><td>Bonos</td><td class="num">${{ number_format($rol->bonos,2) }}</td></tr>@endif
                @if((float)$rol->otros_ingresos>0)<tr><td>Otros</td><td class="num">${{ number_format($rol->otros_ingresos,2) }}</td></tr>@endif
                <tr class="tot"><td>Total ingresos</td><td class="num">${{ number_format($rol->total_ingresos,2) }}</td></tr>
            </table>
        </td>
        <td>
            <table class="det">
                <tr><th colspan="2">Descuentos</th></tr>
                @if((float)$rol->aporte_personal>0)<tr><td>Aporte IESS (9,45%)</td><td class="num">${{ number_format($rol->aporte_personal,2) }}</td></tr>@endif
                @if((float)$rol->anticipos>0)<tr><td>Anticipos</td><td class="num">${{ number_format($rol->anticipos,2) }}</td></tr>@endif
                @if((float)$rol->prestamos_iess>0)<tr><td>Préstamos IESS</td><td class="num">${{ number_format($rol->prestamos_iess,2) }}</td></tr>@endif
                @if((float)$rol->ret_renta>0)<tr><td>Retención renta</td><td class="num">${{ number_format($rol->ret_renta,2) }}</td></tr>@endif
                @if((float)$rol->otros_descuentos>0)<tr><td>Otros</td><td class="num">${{ number_format($rol->otros_descuentos,2) }}</td></tr>@endif
                <tr class="tot"><td>Total descuentos</td><td class="num">${{ number_format($rol->total_descuentos,2) }}</td></tr>
            </table>
        </td>
    </tr></table>

    <table class="det">
        <tr class="liq"><td>LÍQUIDO A RECIBIR</td><td class="num">${{ number_format($rol->liquido,2) }}</td></tr>
    </table>

    @if($rol->relacion !== 'honorarios')
    <table class="det" style="margin-top:8px">
        <tr><th colspan="2">Provisiones patronales (costo empresa, no se descuenta al empleado)</th></tr>
        <tr><td>Aporte patronal (11,15%)</td><td class="num">${{ number_format($rol->aporte_patronal,2) }}</td></tr>
        <tr><td>Décimo tercero</td><td class="num">${{ number_format($rol->decimo_tercero,2) }}</td></tr>
        <tr><td>Décimo cuarto</td><td class="num">${{ number_format($rol->decimo_cuarto,2) }}</td></tr>
        <tr><td>Vacaciones</td><td class="num">${{ number_format($rol->vacaciones,2) }}</td></tr>
        @if((float)$rol->fondos_reserva>0)<tr><td>Fondos de reserva (8,33%)</td><td class="num">${{ number_format($rol->fondos_reserva,2) }}</td></tr>@endif
        <tr class="tot"><td>Costo total empresa</td><td class="num">${{ number_format($rol->costo_empresa,2) }}</td></tr>
    </table>
    @endif

    <table class="firmas">
        <tr>
            <td><div class="line"><strong>Elaborado por</strong><br>{{ $empresa['nombre'] }}<br><span class="rol">Responsable de nómina</span></div></td>
            <td><div class="line"><strong>Autorizado por</strong><br>&nbsp;<br><span class="rol">Gerencia</span></div></td>
            <td><div class="line"><strong>Recibí conforme</strong><br>{{ $rol->empleado->nombre ?? '' }}<br><span class="rol">C.I. {{ $rol->empleado->identificacion ?? '________' }}</span></div></td>
        </tr>
    </table>
</div>

</body>
</html>
