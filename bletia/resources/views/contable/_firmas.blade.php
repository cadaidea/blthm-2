<table style="width:100%;margin-top:55px;border-collapse:collapse">
    <tr>
        <td style="width:33%;text-align:center;padding:0 10px;vertical-align:bottom">
            <div style="border-top:1px solid #000;padding-top:6px;font-size:10px">
                <strong>Elaborado por</strong><br>&nbsp;<br>
                <span style="color:#555">Responsable / Contabilidad</span>
            </div>
        </td>
        <td style="width:33%;text-align:center;padding:0 10px;vertical-align:bottom">
            <div style="border-top:1px solid #000;padding-top:6px;font-size:10px">
                <strong>Autorizado por</strong><br>&nbsp;<br>
                <span style="color:#555">Gerencia / Representante legal</span>
            </div>
        </td>
        <td style="width:33%;text-align:center;padding:0 10px;vertical-align:bottom">
            <div style="border-top:1px solid #000;padding-top:6px;font-size:10px">
                <strong>Recibí conforme</strong><br>{{ $recibe ?? '' }}<br>
                <span style="color:#555">C.I./RUC {{ $recibe_id ?: '________' }}</span>
            </div>
        </td>
    </tr>
</table>
