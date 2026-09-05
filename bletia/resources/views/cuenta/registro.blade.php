@extends('tienda.layout')
@section('title', 'Crear cuenta · ' . config('tienda.marca'))
@section('content')
<div class="t-auth">
    <h1 class="t-h1" style="text-align:center">Crear cuenta</h1>
    @if($errors->any())<div class="t-flash" style="background:#fdeaea;border-color:#f3c4c4;color:#c0392b">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('cuenta.registro') }}">
        @csrf
        <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
        <label>Nombre</label>
        <input type="text" name="nombre" value="{{ old('nombre') }}" required>
        <label>Correo</label>
        <input type="email" name="email" value="{{ old('email') }}" required>
        <label>Contraseña</label>
        <input type="password" name="password" required>
        <label>Repetir contraseña</label>
        <input type="password" name="password_confirmation" required>
        @if($turnstileActivo && $turnstileSiteKey)
        <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-theme="light" data-size="flexible" style="margin:12px 0"></div>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endif
        <button class="t-btn t-btn--block" type="submit">Crear cuenta</button>
    </form>
    <p class="t-auth-alt">¿Ya tienes cuenta? <a href="{{ route('cuenta.login') }}" style="color:var(--brand);font-weight:500">Ingresar</a></p>
</div>
@endsection
