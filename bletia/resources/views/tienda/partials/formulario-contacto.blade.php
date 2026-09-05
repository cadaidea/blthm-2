@php
    $slug = $slug ?? 'contacto';
    $formulario = \App\Models\FormularioContacto::where('slug', $slug)->where('activo', true)->first();
    $temas = $formulario?->temasArray() ?? [];
    $turnstileActivo = \App\Models\Ajuste::get('turnstile_activo') === '1';
    $turnstileSiteKey = \App\Models\Ajuste::get('turnstile_site_key');
@endphp
@if($formulario)
<div class="t-block t-block-formulario-contacto" style="max-width:640px;margin:0 auto">
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
    <form method="post" action="{{ route('contacto.submit', $formulario->slug) }}" style="display:flex;flex-direction:column;gap:12px">
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
@endif
