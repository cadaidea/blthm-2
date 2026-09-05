<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8">
<style>
  @page { margin: 0; }
  * { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 0; }
  body { color: #161921; }
  .hoja {
    width: 297mm; height: 210mm;
    page-break-after: always;
    position: relative;
  }
  .hoja:last-child { page-break-after: auto; }
  .borde {
    position: absolute;
    top: 12mm; left: 12mm;
    width: 273mm; height: 186mm;
    border: 3px solid #161921; border-radius: 14px;
  }
  .pad { padding: 16mm 18mm; }
  .marca { font-size: 46px; font-weight: bold; letter-spacing: 3px; }
  .bulto {
    position: absolute; top: 16mm; right: 18mm;
    background: #0499FC; color: #fff; border-radius: 12px;
    padding: 8px 22px; font-size: 26px; font-weight: bold;
  }
  .linea { border-top: 3px solid #c0c6cf; margin-top: 14mm; }
  .label { color: #8a929c; font-size: 15px; text-transform: uppercase; letter-spacing: 1px; }
  .folio { font-size: 48px; font-weight: bold; }
  .producto { font-size: 30px; font-weight: bold; }
  .pieza { font-size: 20px; color: #5b6470; }
  .cliente { font-size: 38px; font-weight: bold; }
  .dir { font-size: 24px; }
  .ciudad { font-size: 28px; font-weight: bold; }
  .colL { position: absolute; left: 18mm; top: 52mm; width: 105mm; }
  .colR { position: absolute; left: 138mm; top: 52mm; width: 115mm; padding-left: 12mm; border-left: 3px solid #edeff2; }
  .mb { margin-bottom: 8mm; }
  .mb-sm { margin-bottom: 5mm; }
</style></head><body>
  @foreach($etiquetas as $e)
    <div class="hoja">
      <div class="borde">
        <div class="pad">
          <span class="marca">BLETIA</span>
          <span class="bulto">BULTO {{ $e['indice'] }} / {{ $e['total'] }}</span>
          <div class="linea"></div>
        </div>
        <div class="colL">
          <div class="label">Pedido</div>
          <div class="folio mb">{{ $datos['folio'] }}</div>
          <div class="label">Producto</div>
          <div class="producto">{{ $e['item'] }}</div>
          @if($e['bultos_item'] > 1)<div class="pieza">pieza {{ $e['bulto_item'] }} de {{ $e['bultos_item'] }}</div>@endif
        </div>
        <div class="colR">
          <div class="label">Destinatario</div>
          <div class="cliente mb">{{ $datos['cliente'] }}</div>
          <div class="label">Dirección</div>
          <div class="dir mb-sm">{{ $datos['direccion'] }}</div>
          <div class="label">Ciudad</div>
          <div class="ciudad">{{ $datos['ciudad'] }}</div>
        </div>
      </div>
    </div>
  @endforeach
</body></html>
