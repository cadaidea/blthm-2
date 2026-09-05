{{-- Pie de 3 firmas legales (Ecuador): Elaborado / Autorizado / Recibí conforme --}}
<table style="width:100%;margin-top:60px;border-collapse:collapse">
    <tr>
        <td style="width:33%;text-align:center;padding:0 10px;vertical-align:bottom">
            <div style="border-top:1px solid #000;padding-top:6px;font-size:10px">
                <strong>Elaborado por</strong><br>
                {{ $elaborado ?? '' }}<br>
                <span style="color:#555">Responsable de nómina</span>
            </div>
        </td>
        <td style="width:33%;text-align:center;padding:0 10px;vertical-align:bottom">
            <div style="border-top:1px solid #000;padding-top:6px;font-size:10px">
                <strong>Autorizado por</strong><br>
                {{ $autorizado ?? '' }}<br>
                <span style="color:#555">Gerencia / Representante legal</span>
            </div>
        </td>
        <td style="width:33%;text-align:center;padding:0 10px;vertical-align:bottom">
            <div style="border-top:1px solid #000;padding-top:6px;font-size:10px">
                <strong>Recibí conforme</strong><br>
                {{ $recibe ?? '' }}<br>
                <span style="color:#555">C.I. {{ $recibe_id ?? '________' }}</span>
            </div>
        </td>
    </tr>
</table>
