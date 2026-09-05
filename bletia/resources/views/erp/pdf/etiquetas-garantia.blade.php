<!DOCTYPE html><html><head><meta charset="utf-8"><style>
@page { margin: 0; size: A4 landscape; }
body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; }
.etiqueta { position: relative; width: 273mm; height: 186mm; border: 2px solid #161921; margin: 12mm auto; box-sizing: border-box; page-break-after: always; }
.badge { position: absolute; top: 12mm; left: 12mm; background: #0499FC; color: #fff; padding: 4mm 10mm; border-radius: 6mm; font-size: 11pt; font-weight: bold; letter-spacing: 1px; }
.garantia-badge { position: absolute; top: 12mm; right: 12mm; background: #b8860b; color: #fff; padding: 4mm 10mm; border-radius: 6mm; font-size: 11pt; font-weight: bold; }
.nombre { position: absolute; top: 35mm; left: 12mm; font-size: 22pt; font-weight: bold; color: #161921; max-width: 200mm; }
.dir { position: absolute; top: 62mm; left: 12mm; font-size: 13pt; color: #2b3038; max-width: 200mm; }
.ciudad { position: absolute; top: 75mm; left: 12mm; font-size: 13pt; color: #2b3038; }
.pedido { position: absolute; bottom: 20mm; left: 12mm; font-size: 10pt; color: #8a929c; }
.bulto { position: absolute; bottom: 12mm; right: 12mm; font-size: 28pt; font-weight: bold; color: #161921; }
</style></head><body>
@foreach($etiquetas as $et)
@php $cli = $et['cliente']; $ped = $et['pedido']; @endphp
<div class="etiqueta">
    <div class="badge">BLETIA</div>
    <div class="garantia-badge">GARANTÍA</div>
    <div class="nombre">{{ $cli->nombre ?? 'Cliente' }}</div>
    <div class="dir">{{ $cli->direccion ?? '' }}</div>
    <div class="ciudad">{{ $cli->ciudad ?? '' }}</div>
    <div class="pedido">Pedido: {{ $ped->folio ?: ('#'.$ped->id) }}</div>
    <div class="bulto">{{ $et['bulto'] }} / {{ $et['total'] }}</div>
</div>
@endforeach
</body></html>
