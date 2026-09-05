#!/bin/bash
# #1 robots.txt dinámico + toggle IA — aplicar en el servidor bletia
# Cada perl usa string exacto. Si un bloque ya estaba, no duplica (grep guard).
set -e
cd ~/domains/bletia.ec/bletia

SEO=app/Filament/Pages/AjustesSeoBletia.php
CTRL=app/Http/Controllers/TiendaController.php
ROUTES=routes/web.php

# --- 1) whitelist de claves ---
grep -q "'ai_bots'," "$SEO" || perl -0pi -e "s/('direccion', 'ruc', 'email_logo', 'email_footer_texto', 'email_redes',\n    \];)/'direccion', 'ruc', 'email_logo', 'email_footer_texto', 'email_redes',\n        'ai_bots',\n    ];/" "$SEO"

# --- 2) Toggle en la sección SEO (después del og_image FileUpload) ---
grep -q "make('ai_bots')" "$SEO" || perl -0pi -e "s/(FileUpload::make\('og_image'\)->label\('Imagen para compartir \(og:image\)'\)->image\(\)\n                    ->directory\('marca'\)->disk\('public'\)->imageEditor\(\),)/\$1\n                \\\\Filament\\\\Forms\\\\Components\\\\Toggle::make('ai_bots')\n                    ->label('Permitir rastreadores de IA (GPTBot, ClaudeBot, PerplexityBot, Google-Extended…)')\n                    ->helperText('Si lo apagas, esos bots quedan bloqueados en robots.txt. Google y Bing normales no se afectan.')\n                    ->default(true),/" "$SEO"

# --- 3) ruta /robots.txt junto al sitemap ---
grep -q "'robots'" "$ROUTES" || perl -0pi -e "s{(Route::get\('/sitemap\.xml', \[TiendaController::class, 'sitemap'\]\);)}{\$1\nRoute::get('/robots.txt', [TiendaController::class, 'robots']);}" "$ROUTES"

# --- 4) método robots() en el controller ---
grep -q "function robots()" "$CTRL" || perl -0pi -e "s/(class TiendaController extends Controller\n\{)/\$1\n    public function robots()\n    {\n        \$lines = [\"User-agent: *\", \"Disallow:\", \"\"];\n        \$ai = \\\\App\\\\Models\\\\Ajuste::get('ai_bots', '1');\n        if (\$ai === '0' || \$ai === 0 || \$ai === false) {\n            foreach (['GPTBot','ChatGPT-User','ClaudeBot','anthropic-ai','PerplexityBot','Google-Extended','CCBot','Bytespider','Amazonbot'] as \$bot) {\n                \$lines[] = \"User-agent: {\$bot}\";\n                \$lines[] = \"Disallow: \/\";\n                \$lines[] = \"\";\n            }\n        }\n        \$lines[] = \"Sitemap: \" . url('\/sitemap.xml');\n        return response(implode(\"\\\\n\", \$lines) . \"\\\\n\", 200)->header('Content-Type', 'text\/plain; charset=UTF-8');\n    }\n/" "$CTRL"

# --- 5) borrar robots físico para que Laravel tome el control ---
rm -f public/robots.txt
[ -f ../public_html/robots.txt ] && rm -f ../public_html/robots.txt || true

# --- 6) validar sintaxis ---
/opt/alt/php84/usr/bin/php -l "$SEO"
/opt/alt/php84/usr/bin/php -l "$CTRL"
/opt/alt/php84/usr/bin/php -l "$ROUTES"

echo "OK — ahora corre: /opt/alt/php84/usr/bin/php artisan optimize:clear && /opt/alt/php84/usr/bin/php artisan filament:cache-components"
