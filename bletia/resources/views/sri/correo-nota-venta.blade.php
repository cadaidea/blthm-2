<div style="font-family: Arial, sans-serif; max-width: 560px; margin: 0 auto; color: #1a1a1a;">
  <div style="background:#161921; padding:22px 24px; border-radius:10px 10px 0 0;">
    <span style="color:#fff; font-size:18px; font-weight:bold;">Bletia <span style="color:#0499FC;">x</span> Seridea</span>
  </div>
  <div style="border:1px solid #e7e9ed; border-top:none; border-radius:0 0 10px 10px; padding:24px;">
    <p style="font-size:15px;">Estimado/a <strong>{{ $venta->cliente->nombre ?? 'cliente' }}</strong>,</p>
    <p style="font-size:14px; line-height:1.6; color:#3a4250;">
      Adjuntamos su nota de venta <strong>{{ $venta->numero_comprobante }}</strong> por su compra.
    </p>
    <table style="width:100%; margin:18px 0; border-collapse:collapse; font-size:13px;">
      <tr><td style="padding:8px 0; color:#8a929c;">Documento</td><td style="padding:8px 0; text-align:right; font-weight:bold;">{{ $venta->numero_comprobante }}</td></tr>
      <tr style="border-top:1px solid #edeff2;"><td style="padding:8px 0; color:#8a929c;">Total</td><td style="padding:8px 0; text-align:right; font-weight:bold;">$ {{ number_format((float)$venta->total,2) }}</td></tr>
    </table>
    <p style="font-size:13px; color:#3a4250; margin-top:20px;">Gracias por su compra.</p>
  </div>
  <p style="text-align:center; font-size:11px; color:#a8aeb8; margin-top:14px;">Bletia x Seridea · Cuenca, Ecuador</p>
</div>
