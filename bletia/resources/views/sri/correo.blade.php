@php
  $tipo = ['01'=>'factura','04'=>'nota de crédito','05'=>'nota de débito','06'=>'guía de remisión','07'=>'comprobante de retención'][$c->cod_doc] ?? 'comprobante';
  $num = $c->estab.'-'.$c->pto_emi.'-'.$c->secuencial;
@endphp
<div style="font-family: Arial, sans-serif; max-width: 560px; margin: 0 auto; color: #1a1a1a;">
  <div style="background:#161921; padding:22px 24px; border-radius:10px 10px 0 0;">
    <span style="color:#fff; font-size:18px; font-weight:bold;">Bletia <span style="color:#0499FC;">x</span> Seridea</span>
  </div>
  <div style="border:1px solid #e7e9ed; border-top:none; border-radius:0 0 10px 10px; padding:24px;">
    <p style="font-size:15px;">Estimado/a <strong>{{ $c->receptor_razon }}</strong>,</p>
    <p style="font-size:14px; line-height:1.6; color:#3a4250;">
      Adjuntamos su {{ $tipo }} electrónica <strong>No. {{ $num }}</strong>, autorizada por el SRI.
      Encontrará el documento en formato PDF (RIDE) y el archivo XML con validez tributaria.
    </p>
    <table style="width:100%; margin:18px 0; border-collapse:collapse; font-size:13px;">
      <tr><td style="padding:8px 0; color:#8a929c;">Comprobante</td><td style="padding:8px 0; text-align:right; font-weight:bold;">{{ $num }}</td></tr>
      <tr style="border-top:1px solid #edeff2;"><td style="padding:8px 0; color:#8a929c;">Total</td><td style="padding:8px 0; text-align:right; font-weight:bold;">$ {{ number_format((float)$c->total,2) }}</td></tr>
      <tr style="border-top:1px solid #edeff2;"><td style="padding:8px 0; color:#8a929c;">Estado</td><td style="padding:8px 0; text-align:right;"><span style="background:#e7f7ec; color:#1f8b4c; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:bold;">AUTORIZADO</span></td></tr>
    </table>
    <p style="font-size:12px; color:#8a929c; line-height:1.6;">
      Clave de acceso:<br><span style="font-family:monospace; word-break:break-all;">{{ $c->clave_acceso }}</span>
    </p>
    <p style="font-size:13px; color:#3a4250; margin-top:20px;">Gracias por su compra.</p>
  </div>
  <p style="text-align:center; font-size:11px; color:#a8aeb8; margin-top:14px;">Bletia x Seridea · Cuenca, Ecuador</p>
</div>
