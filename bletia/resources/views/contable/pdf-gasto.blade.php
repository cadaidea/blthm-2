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
        <div class="emp">RUC/CI: {{ $empresa['ruc'] ?: '—' }} · {{ $empresa['direccion'] ?: '' }} · {{ $empresa['ciudad'] ?: '' }} · {{ $empresa['telefono'] ?: '' }}</div>
    </div>

    <div class="title">Comprobante de Egreso</div>
    <div class="sub">N° {{ $gasto->folio ?: $gasto->id }} @if($gasto->estado === 'anulado')<span class="anul">· ANULADO</span>@endif</div>

    <table class="meta">
        <tr>
            <td><strong>Fecha:</strong> {{ \Illuminate\Support\Carbon::parse($gasto->fecha)->format('d/m/Y') }}</td>
            <td><strong>Categoría:</strong> {{ \App\Models\Gasto::CATEGORIAS[$gasto->categoria] ?? $gasto->categoria }}</td>
        </tr>
        <tr>
            <td><strong>Beneficiario:</strong> {{ $gasto->proveedor->nombre ?? ($gasto->beneficiario ?: '—') }}</td>
            <td><strong>RUC/CI:</strong> {{ $gasto->beneficiario_id_num ?: (optional($gasto->proveedor)->identificacion ?: '—') }}</td>
        </tr>
        <tr>
            <td><strong>Documento:</strong> {{ ucfirst(str_replace('_',' ', $gasto->doc_tipo ?: '—')) }} {{ $gasto->doc_numero ? '· N° '.$gasto->doc_numero : '' }}</td>
            <td><strong>Autorización SRI:</strong> {{ $gasto->autorizacion_sri ?: '—' }}</td>
        </tr>
        <tr>
            <td><strong>Forma de pago:</strong> {{ $gasto->forma_pago === 'credito' ? 'A crédito' : 'Contado' }}</td>
            <td><strong>Método:</strong> {{ ucfirst($gasto->metodo_pago ?: '—') }}</td>
        </tr>
    </table>

    <table class="det">
        <tr><th>Concepto</th><th class="num">Valor</th></tr>
        <tr><td>Base imponible</td><td class="num">${{ number_format($gasto->base,2) }}</td></tr>
        @if((float)$gasto->iva > 0)<tr><td>IVA</td><td class="num">${{ number_format($gasto->iva,2) }}</td></tr>@endif
        @if((float)$gasto->ret_iva > 0)<tr><td>(-) Retención IVA</td><td class="num">(${{ number_format($gasto->ret_iva,2) }})</td></tr>@endif
        @if((float)$gasto->ret_renta > 0)<tr><td>(-) Retención Renta</td><td class="num">(${{ number_format($gasto->ret_renta,2) }})</td></tr>@endif
        <tr class="tot"><td>TOTAL PAGADO</td><td class="num">${{ number_format($gasto->total,2) }}</td></tr>
    </table>

    @if($gasto->notas)<p style="font-size:10px;color:#555">{{ $gasto->notas }}</p>@endif

    @if($asiento)
    <table class="det" style="margin-top:10px">
        <tr><th colspan="3">Registro contable · Asiento {{ $asiento->numero ?: $asiento->id }}</th></tr>
        <tr><td style="font-size:9px;color:#555" colspan="3">{{ $asiento->glosa }}</td></tr>
    </table>
    @endif

    @include('contable._firmas', [
        'recibe' => $gasto->proveedor->nombre ?? ($gasto->beneficiario ?: ''),
        'recibe_id' => $gasto->beneficiario_id_num ?: (optional($gasto->proveedor)->identificacion ?: ''),
    ])
</body>
</html>
