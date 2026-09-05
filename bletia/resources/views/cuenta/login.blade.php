@extends('tienda.layout')
@section('title', 'Ingresar · ' . config('tienda.marca'))
@section('content')
<div class="t-auth">
    <h1 class="t-h1" style="text-align:center">Mi cuenta</h1>
    @if($errors->any())<div class="t-flash" style="background:#fdeaea;border-color:#f3c4c4;color:#c0392b">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('cuenta.login') }}">
        @csrf
        <label>Correo</label>
        <input type="email" name="email" value="{{ old('email') }}" required>
        <label>Contraseña</label>
        <input type="password" name="password" required>
        <button class="t-btn t-btn--block" type="submit">Ingresar</button>
    </form>
    <p class="t-auth-alt">¿No tienes cuenta? <a href="{{ route('cuenta.registro') }}" style="color:var(--brand);font-weight:500">Crear una</a></p>
</div>
@endsection
