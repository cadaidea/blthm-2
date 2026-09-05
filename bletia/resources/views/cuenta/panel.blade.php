@extends('tienda.layout')
@section('title', 'Mi cuenta · ' . config('tienda.marca'))
@section('content')
<div style="max-width:760px;margin:0 auto">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
        <div>
            <h1 class="t-h1" style="margin:0">Hola, {{ $cliente->nombre }}</h1>
            {{-- cuenta-verif-badge --}}
            @if($cliente->email_verified_at)
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:.82rem;color:#16a34a;font-weight:600;margin-top:4px">✓ Correo verificado</span>
            @else
                <span style="display:inline-flex;align-items:center;gap:8px;font-size:.82rem;color:#b45309;margin-top:4px">Correo sin verificar
                    <form method="post" action="{{ route('cuenta.reenviar') }}" style="display:inline">@csrf<button type="submit" style="background:none;border:0;color:var(--brand);text-decoration:underline;cursor:pointer;font-size:.82rem;padding:0">Reenviar correo</button></form>
                </span>
            @endif
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="{{ route('cuenta.guardados') }}" class="t-btn">Mis guardados</a>
            <form method="post" action="{{ route('cuenta.salir') }}">@csrf<button class="t-btn" type="submit">Salir</button></form>
        </div>
    </div>
    <h2 class="t-h2" style="margin-top:32px">Mis pedidos</h2>
    @forelse($pedidos as $p)
        @php($pg = $pagos[$p->id] ?? null)
        <div class="t-panel-card">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
                <strong>{{ $p->codigo }}</strong>
                <span class="t-badge t-badge--{{ $p->estado }}">{{ ucfirst(str_replace('_',' ',$p->estado)) }}</span>
            </div>
            <p style="color:var(--muted);margin:8px 0 0">{{ $p->created_at->format('d/m/Y') }} · ${{ number_format($p->total,2) }} (IVA incl.)</p>

            @if($pg)
                @if($pg['saldo'] <= 0)
                    <p style="margin:8px 0 0;color:#2e7d32;font-weight:500">Pago OK</p>
                @else
                    <p style="margin:8px 0 0;color:#b00020;font-weight:500">Saldo pendiente ${{ number_format($pg['saldo'],2) }}</p>
                @endif
                @if($pg['recibos']->count())
                    <ul style="margin:8px 0 0;padding-left:18px;color:var(--muted);font-size:.92em">
                        @foreach($pg['recibos'] as $r)
                            <li>{{ $r->fecha ? \Illuminate\Support\Carbon::parse($r->fecha)->format('d/m/Y') : '' }} · {{ ucfirst($r->tipo) }} ${{ number_format($r->monto,2) }}@if($r->metodo) · {{ ucfirst($r->metodo) }}@endif</li>
                        @endforeach
                    </ul>
                @endif
            @endif

            <p style="margin:10px 0 0"><a href="{{ route('erp.seguimiento', ['p' => $p->id]) }}" style="color:var(--brand)">Ver seguimiento</a></p>
        </div>
    @empty
        <p class="t-empty">Aún no tienes pedidos. <a href="{{ route('tienda.home') }}" style="color:var(--brand)">Explorar la tienda</a></p>
    @endforelse
</div>
@endsection
