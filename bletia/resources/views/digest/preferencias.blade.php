@extends('tienda.layout')
@section('title', 'Preferencias · ' . config('tienda.marca'))
@section('content')
<div class="t-page" style="max-width:560px;padding:40px 0">
    <h1 class="t-h1">Tus preferencias</h1>
    <p class="t-lead">{{ $s->email }}</p>
    @if(!empty($guardado))<div class="t-flash">Preferencias guardadas.</div>@endif
    <form method="post" action="{{ route('digest.preferences') }}" style="margin-top:18px">
        @csrf
        <input type="hidden" name="sid" value="{{ $s->id }}">
        <input type="hidden" name="token" value="{{ $s->token }}">
        @foreach($listas as $l)
            <label style="display:flex;gap:10px;align-items:flex-start;padding:12px 0;border-bottom:1px solid var(--line)">
                <input type="checkbox" name="listas[]" value="{{ $l->id }}" @checked(in_array($l->id, $actuales))>
                <span><strong>{{ $l->nombre }}</strong>@if($l->descripcion)<br><small style="color:var(--muted)">{{ $l->descripcion }}</small>@endif</span>
            </label>
        @endforeach
        <button type="submit" class="t-btn t-btn--cta" style="margin-top:18px">Guardar preferencias</button>
    </form>
    <p style="margin-top:18px"><a href="{{ route('digest.unsubscribe.form', ['sid' => $s->id, 'token' => $s->token]) }}" style="color:var(--muted)">Darme de baja de todo</a></p>
</div>
@endsection
