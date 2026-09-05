<x-filament-panels::page>
    @php($sol = $this->solicitudes())
    @php($res = $this->resumen())

    <div style="display:flex;flex-direction:column;gap:24px">

        <section>
            <h2 style="font-weight:600;margin-bottom:8px">Solicitudes de material (detalle)</h2>
            <div class="fi-ta" style="overflow-x:auto;border:1px solid var(--gray-200,#e5e7eb);border-radius:8px">
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead style="background:#161921;color:#fff">
                        <tr>
                            <th style="text-align:left;padding:8px">Pedido</th>
                            <th style="text-align:left;padding:8px">Material</th>
                            <th style="text-align:right;padding:8px">Solicitado</th>
                            <th style="text-align:right;padding:8px">Entregado</th>
                            <th style="text-align:right;padding:8px">Pendiente</th>
                            <th style="text-align:right;padding:8px">Usado</th>
                            <th style="text-align:right;padding:8px">Sobrante</th>
                            <th style="text-align:right;padding:8px">% uso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sol as $r)
                            <tr style="border-top:1px solid #eee">
                                <td style="padding:8px">{{ $r->pedido }}</td>
                                <td style="padding:8px">{{ $r->materia }} <span style="color:#888">{{ $r->unidad }}</span></td>
                                <td style="padding:8px;text-align:right">{{ rtrim(rtrim(number_format($r->solicitado,2),'0'),'.') }}</td>
                                <td style="padding:8px;text-align:right">{{ rtrim(rtrim(number_format($r->entregado,2),'0'),'.') }}</td>
                                <td style="padding:8px;text-align:right;color:{{ $r->pendiente > 0 ? '#e6862e' : '#888' }}">{{ rtrim(rtrim(number_format($r->pendiente,2),'0'),'.') }}</td>
                                <td style="padding:8px;text-align:right">{{ rtrim(rtrim(number_format($r->usado,2),'0'),'.') }}</td>
                                <td style="padding:8px;text-align:right;color:{{ $r->sobrante > 0 ? '#2e9e6b' : '#888' }}">{{ rtrim(rtrim(number_format($r->sobrante,2),'0'),'.') }}</td>
                                <td style="padding:8px;text-align:right;font-weight:600">{{ $r->pct_uso }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" style="padding:16px;text-align:center;color:#888">Sin solicitudes registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <h2 style="font-weight:600;margin-bottom:8px">Resumen de materias primas</h2>
            <div class="fi-ta" style="overflow-x:auto;border:1px solid var(--gray-200,#e5e7eb);border-radius:8px">
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead style="background:#161921;color:#fff">
                        <tr>
                            <th style="text-align:left;padding:8px">Material</th>
                            <th style="text-align:right;padding:8px">Stock</th>
                            <th style="text-align:right;padding:8px">Mínimo</th>
                            <th style="text-align:right;padding:8px">Entradas</th>
                            <th style="text-align:right;padding:8px">Solicitado</th>
                            <th style="text-align:right;padding:8px">Entregado</th>
                            <th style="text-align:right;padding:8px">Gastado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($res as $mp)
                            <tr style="border-top:1px solid #eee;{{ $mp->bajo ? 'background:#fdecec' : '' }}">
                                <td style="padding:8px">{{ $mp->nombre }} <span style="color:#888">{{ $mp->unidad ?? '' }}</span></td>
                                <td style="padding:8px;text-align:right;font-weight:600;color:{{ $mp->bajo ? '#d9534f' : 'inherit' }}">{{ rtrim(rtrim(number_format($mp->stock,2),'0'),'.') }}</td>
                                <td style="padding:8px;text-align:right;color:#888">{{ rtrim(rtrim(number_format($mp->minimo ?? 0,2),'0'),'.') }}</td>
                                <td style="padding:8px;text-align:right">{{ rtrim(rtrim(number_format($mp->entradas,2),'0'),'.') }}</td>
                                <td style="padding:8px;text-align:right">{{ rtrim(rtrim(number_format($mp->solicitado,2),'0'),'.') }}</td>
                                <td style="padding:8px;text-align:right">{{ rtrim(rtrim(number_format($mp->entregado,2),'0'),'.') }}</td>
                                <td style="padding:8px;text-align:right">{{ rtrim(rtrim(number_format($mp->gastado,2),'0'),'.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" style="padding:16px;text-align:center;color:#888">Sin materias primas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</x-filament-panels::page>
