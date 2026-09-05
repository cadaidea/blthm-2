<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { color:#161921; font-size:12px; margin:0; padding:28px; }
    .head { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #161921; padding-bottom:12px; margin-bottom:18px; }
    .marca { font-size:20px; font-weight:bold; }
    .sub { color:#666; font-size:10px; line-height:1.5; }
    .titulo { font-size:15px; font-weight:bold; margin:6px 0 14px; }
    .box { border:1px solid #ddd; border-radius:6px; padding:12px; margin-bottom:14px; }
    .row { width:100%; }
    .row td { padding:5px 4px; vertical-align:top; font-size:11px; }
    .lbl { color:#888; font-size:10px; }
    table.items { width:100%; border-collapse:collapse; margin-top:6px; }
    table.items th { background:#161921; color:#fff; padding:7px; text-align:left; font-size:10px; }
    table.items td { padding:7px; border-bottom:1px solid #eee; font-size:11px; }
    .firmas { width:100%; margin-top:40px; }
    .firmas td { width:50%; text-align:center; vertical-align:bottom; padding:0 20px; }
    .firma-img { max-height:70px; margin-bottom:4px; }
    .linea { border-top:1px solid #333; margin-top:6px; padding-top:5px; font-size:11px; }
    .pie { margin-top:30px; color:#999; font-size:9px; text-align:center; border-top:1px solid #eee; padding-top:8px; }
</style></head>
<body>
    <div class="head">
        <div>
            <div class="marca">{{ $empresa['nombre'] }}</div>
            <div class="sub">
                @if($empresa['ruc'])RUC: {{ $empresa['ruc'] }}<br>@endif
                {{ $empresa['direccion'] }} · {{ $empresa['ciudad'] }}<br>
                {{ $empresa['telefono'] }} · {{ $empresa['email'] }}
            </div>
        </div>
        @if($logo)<img src="{{ $logo }}" style="max-height:54px">@endif
    </div>

    <div class="titulo">Acta de entrega de material — N° {{ $folio }}</div>

    <div class="box">
        <table class="row">
            <tr>
                <td style="width:50%"><span class="lbl">Pedido</span><br>{{ $pedidoFolio }}</td>
                <td style="width:50%"><span class="lbl">Cliente</span><br>{{ $cliente ?: '—' }}</td>
            </tr>
            <tr>
                <td><span class="lbl">Para fabricar</span><br>{{ $productos ?: '—' }}</td>
                <td><span class="lbl">Fecha y hora</span><br>{{ $fecha }}</td>
            </tr>
        </table>
    </div>

    <table class="items">
        <thead><tr><th>Material</th><th style="width:22%">Cantidad</th><th style="width:30%">Nota</th></tr></thead>
        <tbody>
            <tr>
                <td>{{ $material }}</td>
                <td>{{ $cantidad }} {{ $unidad }}</td>
                <td>{{ $nota ?: '—' }}</td>
            </tr>
        </tbody>
    </table>

    <table class="firmas">
        <tr>
            <td>
                <br><br><br>
                <div class="linea">
                    <strong>{{ $entregaNombre }}</strong><br>
                    <span class="lbl">Entrega · {{ $entregaRol }}</span>
                </div>
            </td>
            <td>
                @if($firmaRecibe)<img src="{{ $firmaRecibe }}" class="firma-img"><br>@else<br><br><br>@endif
                <div class="linea">
                    <strong>{{ $recibeNombre ?: '________________' }}</strong><br>
                    <span class="lbl">Recibe @if($recibeCedula) · C.I. {{ $recibeCedula }}@endif</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="pie">Documento generado por {{ $empresa['nombre'] }} · {{ $fecha }}</div>
</body>
</html>
