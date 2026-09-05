<div style="font-family:sans-serif">
    <p style="margin:0 0 10px">
        <strong>{{ $c->tipo === 'proveedor' ? 'Proveedor' : 'Destino' }}:</strong>
        {{ $c->tipo === 'proveedor' ? ($c->proveedor->nombre ?? '—') : ($c->localDestino->nombre ?? '—') }}
        @if($c->tipo === 'proveedor' && $c->localDestino) · <strong>Entra a:</strong> {{ $c->localDestino->nombre }} @endif
    </p>
    @foreach($c->items as $it)
        <div style="border:1px solid #eee;border-radius:10px;padding:12px;margin-bottom:10px">
            <p style="margin:0 0 6px"><strong>{{ $it->nombre }}</strong> · x{{ $it->cantidad }}
                @if($it->bultos > 1) <span style="color:#8a929c">· {{ $it->bultos }} bulto(s)</span> @endif
            </p>
            @if($it->variante && $it->variante->foto)
                <div style="margin-top:8px;">
                    <img src="{{ $it->variante->foto_url }}" style="height:90px;border-radius:8px;border:1px solid #eee" />
                </div>
            @elseif($it->producto && $it->producto->imagen_principal)
                <div style="margin-top:8px;">
                    <img src="{{ $it->producto->imagen_principal }}" style="height:90px;border-radius:8px;border:1px solid #eee" />
                </div>
            @endif
        </div>
    @endforeach
</div>
