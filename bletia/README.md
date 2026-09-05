# Bletia Seridea — v0.9.0 · Módulo 9 Marketing (Fase 1)

Va sobre v0.8.1. Fase 1 = captura + suscriptores + listas + formularios + opt-in DOBLE público.
Fase 2 (campañas + envío con tracking) viene después.

## Incluye
- /admin → grupo "Marketing": Suscriptores, Listas, Formularios.
- Opt-in doble: el formulario crea suscriptor "pendiente" y envía correo de confirmación. Al confirmar pasa a "confirmado".
- Páginas públicas: /digest/confirm, /digest/unsubscribe, /digest/preferences. Honeypot + rate-limit por IP.
- El newsletter del sitio (footer) ahora alimenta este módulo (lista "Newsletter" se crea sola).

## Aplicar (SSH)
```
cd ~/domains/bletia.ec/bletia
cp routes/web.php routes/web.php.bak
unzip -o ../bletia-v090-0.9.0.zip -d /tmp/v90
cp -r /tmp/v90/bletia-v090/* .
php=/opt/alt/php83/usr/bin/php
$php artisan migrate
find storage/framework/views -name "*.php" -delete
$php artisan optimize:clear && $php artisan config:cache && $php artisan route:cache
```

## CONFIGURAR BREVO (SMTP) en .env  (necesario para que llegue el correo de confirmación)
Edita ~/domains/bletia.ec/bletia/.env y pon:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=TU_LOGIN_SMTP_BREVO         # lo da Brevo en SMTP & API > SMTP
MAIL_PASSWORD=TU_SMTP_KEY_BREVO           # la "Master password"/SMTP key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@bletia.ec"
MAIL_FROM_NAME="Bletia Seridea"
```
- El dominio del remitente (bletia.ec) debe estar verificado en Brevo (SPF/DKIM).
- Tras editar .env: `$php artisan config:cache`.
- Prueba: `$php artisan tinker --execute="Illuminate\Support\Facades\Mail::raw('test', fn(\$m)=>\$m->to('TUCORREO')->subject('Prueba Brevo'));"`

## Probar
- Suscríbete desde el footer → revisa correo → confirma → en /admin debe quedar "confirmado".
