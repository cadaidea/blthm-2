<div class="t-modal" id="t-auth-modal" aria-hidden="true" data-open="{{ (isset($errors) && $errors->any()) ? 1 : 0 }}">
    <div class="t-modal-box">
        <button class="t-modal-close" id="t-auth-close" aria-label="Cerrar">&times;</button>
        <div class="t-modal-tabs">
            <button class="t-modal-tab is-active" data-tab="login">Ingresar</button>
            <button class="t-modal-tab" data-tab="registro">Crear cuenta</button>
        </div>
        @if(isset($errors) && $errors->any())<div class="t-flash" style="background:#fdeaea;border-color:#f3c4c4;color:#c0392b">{{ $errors->first() }}</div>@endif

        <div class="t-modal-pane is-active" id="pane-login">
            <form method="post" action="{{ route('cuenta.login') }}">
                <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
                @csrf
                <label>Correo</label><input type="email" name="email" required>
                <label>Contraseña</label><input type="password" name="password" required>
                <button class="t-btn t-btn--block" type="submit" style="margin-top:18px">Ingresar</button>
            </form>
        </div>
        <div class="t-modal-pane" id="pane-registro">
            <form method="post" action="{{ route('cuenta.registro') }}">
                <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
                @csrf
                <label>Nombre</label><input type="text" name="nombre" required>
                <label>Correo</label><input type="email" name="email" required>
                <label>Contraseña</label><input type="password" name="password" required>
                <label>Repetir contraseña</label><input type="password" name="password_confirmation" required>
                @if(\App\Models\Ajuste::get("turnstile_activo") === "1" && \App\Models\Ajuste::get("turnstile_site_key"))
                <div class="cf-turnstile" data-sitekey="{{ \App\Models\Ajuste::get('turnstile_site_key') }}" data-theme="light" data-size="flexible" style="margin:12px 0"></div>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                @endif
                <button class="t-btn t-btn--block" type="submit" style="margin-top:18px">Crear cuenta</button>
            </form>
        </div>
    </div>
</div>
