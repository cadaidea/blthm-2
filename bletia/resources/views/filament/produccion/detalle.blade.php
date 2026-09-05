<div style="font-family:sans-serif">
    <p style="margin:0 0 10px"><strong>Cliente:</strong> {{ $pedido->cliente->nombre ?? '—' }}
        @if($pedido->fecha_comprometida) · <strong>Entrega:</strong> {{ \Illuminate\Support\Carbon::parse($pedido->fecha_comprometida)->format('d/m/Y') }} @endif
    </p>
    @if(! $pedido->retira_local && $pedido->direccion_envio)
        <p style="margin:0 0 10px;color:#555"><strong>Envío:</strong> {{ $pedido->direccion_envio }} · {{ $pedido->ciudad_envio }}</p>
    @endif
    @foreach($pedido->items as $it)
        <div style="border:1px solid #eee;border-radius:10px;padding:12px;margin-bottom:10px">
            <p style="margin:0 0 6px"><strong>{{ $it->nombre }}</strong> · x{{ $it->cantidad }}
                @if($it->variantes) <span style="color:#0499FC">· {{ $it->variantes }}</span> @endif
            </p>
            @php($specs = collect(['tapiz_principal'=>'Tapiz','tapiz_secundario'=>'Tapiz sec.','cojines'=>'Cojines','cojines_secundario'=>'Cojines sec.','lacado'=>'Lacado'])->filter(fn($l,$k)=>filled($it->$k)))
            @if($specs->count())
                <ul style="margin:6px 0;padding-left:18px;color:#444">
                    @foreach($specs as $k=>$label)<li>{{ $label }}: {{ $it->$k }}</li>@endforeach
                </ul>
            @endif
            @if($it->notas_adicionales)<p style="margin:6px 0;color:#666">Notas: {{ $it->notas_adicionales }}</p>@endif
            @php($fotos = collect([$it->foto_modelo,$it->foto_tapiz_principal,$it->foto_tapiz_secundario,$it->foto_cojines,$it->foto_cojines_secundario,$it->foto_lacado])->filter())
            @if($fotos->count())
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
                    @foreach($fotos as $f)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($f) }}" style="height:80px;border-radius:8px;border:1px solid #eee" />
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>
