@extends('tienda.layout')
@section('title', $formulario->nombre . ' · ' . config('tienda.marca'))
@section('meta_description', '¿Tienes una pregunta o quieres una pieza a medida? Escríbenos y te respondemos pronto.')
@section('canonical', route('contacto.form'))
@section('content')
<div class="t-page" style="max-width:640px">
    <h1 class="t-h1">{{ $formulario->nombre }}</h1>
    <p style="color:#6a675c;margin:-8px 0 28px">¿Tienes una pregunta, una pieza a medida en mente, o algo que contarnos? Escríbenos.</p>

    @if(session('contact_success'))
        <div class="t-flash" style="position:static;transform:none;margin-bottom:24px;animation:none">
            <span class="t-flash-label">Confirmado</span>
            <span class="t-flash-txt">{{ session('contact_success') }}</span>
        </div>
    @endif
    @if(session('contact_error') || $errors->any())
        <div class="t-flash" style="position:static;transform:none;margin-bottom:24px;animation:none;color:#a33;background:#fdf2f2">
            <span class="t-flash-txt">{{ session('contact_error') ?: $errors->first() }}</span>
        </div>
    @endif

    <form method="post" action="{{ route('contacto.submit') }}" style="display:flex;flex-direction:column;gap:12px">
        @csrf
        <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
        <div style="display:flex;gap:8px">
            <input type="text" name="name" placeholder="Tu nombre *" required class="df-input" style="flex:1" value="{{ old('name') }}">
            <input type="email" name="email" placeholder="Tu correo *" required class="df-input" style="flex:1" value="{{ old('email') }}">
        </div>
        @if(count($temas))
            <select name="subject" class="df-input">
                <option value="">Elige un tema (opcional)</option>
                @foreach($temas as $t)
                    <option value="{{ $t }}" {{ old('subject') === $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
        @else
            <input type="text" name="subject" placeholder="Asunto" class="df-input" value="{{ old('subject') }}">
        @endif
        <textarea name="message" placeholder="Tu mensaje *" required class="df-input" rows="6">{{ old('message') }}</textarea>
        @if($turnstileActivo && $turnstileSiteKey)
        <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-theme="light" data-size="flexible"></div>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endif
        <button type="submit" class="t-btn" style="align-self:flex-start;margin-top:6px">Enviar mensaje</button>
    </form>
</div>
@endsection
