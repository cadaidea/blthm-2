<?php

namespace App\Support;

use App\Models\Ajuste;
use Illuminate\Support\Facades\Storage;

class CorreoBrand
{
    protected static function a(string $k, $d = null)
    {
        try { return class_exists(Ajuste::class) ? (Ajuste::get($k, $d) ?: $d) : $d; }
        catch (\Throwable $e) { return $d; }
    }

    /**
     * Marco premium único para TODOS los correos (ERP + Marketing).
     * Identidad leída de Ajustes (/dash → Identidad & SEO).
     * $opts: [
     *   'preheader'=>'', 'cta'=>['text'=>'','url'=>''], 'footer_extra'=>'',
     *   'unsubscribe_url'=>'', 'preferences_url'=>'',
     * ]
     */
    public static function wrap(string $titulo, string $cuerpoHtml, array $opts = []): string
    {
        $marca   = e(self::a('marca', config('tienda.marca', 'Bletia')));
        $eslogan = e(self::a('eslogan', config('tienda.eslogan', '')));
        $tinta   = self::a('color_primario', '#161921');
        $accent  = self::a('color_footer', '#EFEBDD');
        $bg      = '#ffffff';
        $linea   = '#ECECEC';
        $muted   = '#8a8578';
        $pre     = e($opts['preheader'] ?? '');
        $year    = date('Y');

        // Logo PNG para email (SVG no renderiza en muchos clientes)
        $logoRaw = self::a('email_logo') ?: self::a('logo_movil');
        $logoUrl = '';
        if ($logoRaw && ! str_ends_with(strtolower($logoRaw), '.svg')) {
            try { $logoUrl = Storage::disk('public')->url($logoRaw); } catch (\Throwable $e) {}
        }

        $ciudad    = self::a('ciudad', 'Cuenca');
        $provincia = self::a('provincia', '');
        $direccion = self::a('direccion', '');
        $ruc       = self::a('ruc', '');
        $telefono  = self::a('telefono', '');

        // Header: logo o wordmark + eslogan
        if ($logoUrl) {
            $head = '<img src="' . e($logoUrl) . '" alt="' . $marca . '" height="34" style="height:34px;display:block;margin:0 auto;border:0">';
        } else {
            $head = '<div style="font-family:Georgia,\'Times New Roman\',serif;font-size:13px;letter-spacing:.42em;color:' . $tinta . ';text-transform:uppercase">' . $marca . '</div>';
        }
        $headSlogan = $eslogan
            ? '<div style="font-family:Helvetica,Arial,sans-serif;font-size:11px;letter-spacing:.14em;color:' . $muted . ';text-transform:uppercase;margin-top:10px">' . $eslogan . '</div>'
            : '';

        // CTA
        $cta = '';
        if (! empty($opts['cta']['url'])) {
            $cta = '<tr><td align="center" style="padding:6px 0 4px">'
                 . '<a href="' . e($opts['cta']['url']) . '" style="background:' . $tinta . ';color:#ffffff;text-decoration:none;'
                 . 'padding:14px 34px;border-radius:999px;font-size:15px;display:inline-block;letter-spacing:.02em;font-family:Helvetica,Arial,sans-serif">'
                 . e($opts['cta']['text'] ?? 'Ver más') . '</a></td></tr>';
        }

        // Línea de negocio (legal)
        $legalParts = array_filter([
            $direccion ?: null,
            trim(implode(', ', array_filter([$ciudad, $provincia]))) ?: null,
            $ruc ? ('RUC ' . $ruc) : null,
            $telefono ? ('Tel ' . $telefono) : null,
        ]);
        $legal = e(implode(' · ', $legalParts)) ?: e($ciudad . ', Ecuador');

        // Social desde sameas (cualquier red)
        $social = '';
        $links = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) (self::a('email_redes') ?: self::a('sameas', '')))));
        foreach ($links as $u) {
            $u = trim($u);
            if (! preg_match('~^https?://~i', $u)) continue;
            $host = strtolower((string) parse_url($u, PHP_URL_HOST));
            $map = [
                'instagram' => 'Instagram', 'facebook' => 'Facebook', 'fb.com' => 'Facebook',
                'youtube' => 'YouTube', 'youtu.be' => 'YouTube', 'linkedin' => 'LinkedIn',
                'tiktok' => 'TikTok', 'wa.me' => 'WhatsApp', 'whatsapp' => 'WhatsApp',
                'x.com' => 'X', 'twitter' => 'X', 'pinterest' => 'Pinterest', 'g.page' => 'Google',
            ];
            $lbl = null;
            foreach ($map as $k => $v) { if (strpos($host, $k) !== false || strpos(strtolower($u), $k) !== false) { $lbl = $v; break; } }
            if (! $lbl) $lbl = ucfirst(preg_replace('/^www\./', '', explode('.', $host)[0] ?? 'Enlace'));
            $social .= '<a href="' . e($u) . '" style="color:' . $muted . ';text-decoration:none;font-size:12px;margin:0 8px">' . $lbl . '</a>';
        }
        $socialRow = $social
            ? '<tr><td align="center" style="padding:4px 38px 14px;font-family:Helvetica,Arial,sans-serif">' . $social . '</td></tr>'
            : '';

        // Texto de footer + web
        $footerTexto = e((string) self::a('email_footer_texto', ''));
        $web = rtrim((string) url('/'), '/');
        $webHost = e(preg_replace('~^https?://~', '', $web));
        $footerTextoRow = $footerTexto
            ? '<tr><td align="center" style="padding:2px 38px 10px;font-family:Helvetica,Arial,sans-serif"><div style="font-size:12px;color:' . $muted . ';line-height:1.6">' . $footerTexto . '</div></td></tr>'
            : '';

        // Baja / preferencias (entregabilidad)
        $manage = '';
        if (! empty($opts['unsubscribe_url'])) {
            $manage .= '<a href="' . e($opts['unsubscribe_url']) . '" style="color:#aaa6a0;text-decoration:underline">Darme de baja</a>';
        }
        if (! empty($opts['preferences_url'])) {
            $sep = $manage ? ' · ' : '';
            $manage .= $sep . '<a href="' . e($opts['preferences_url']) . '" style="color:#aaa6a0;text-decoration:underline">Preferencias</a>';
        }
        $manageRow = $manage
            ? '<div style="font-size:11px;color:#aaa6a0;margin-top:8px;font-family:Helvetica,Arial,sans-serif">' . $manage . '</div>'
            : '';

        $footerExtra = $opts['footer_extra'] ?? '';

        return <<<HTML
<!doctype html>
<html lang="es"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<title>{$marca}</title></head>
<body style="margin:0;padding:0;background:{$bg};">
<div style="display:none;font-size:1px;color:{$bg};line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden">{$pre}</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{$bg}">
<tr><td align="center" style="padding:34px 16px">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:{$bg};border:1px solid {$linea};border-radius:18px;overflow:hidden">
    <tr><td style="background:{$accent};height:4px;font-size:0;line-height:0">&nbsp;</td></tr>
    <tr><td align="center" style="padding:32px 28px 8px">
      {$head}
      {$headSlogan}
      <div style="height:1px;background:{$accent};max-width:60px;margin:16px auto 0;font-size:0;line-height:0">&nbsp;</div>
    </td></tr>
    <tr><td style="padding:22px 38px 6px">
      <h1 style="font-family:Georgia,'Times New Roman',serif;font-weight:normal;font-size:23px;line-height:1.32;margin:0 0 14px;letter-spacing:.01em;color:{$tinta}">{$titulo}</h1>
      <div style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:1.7;color:#2b2b30">{$cuerpoHtml}</div>
    </td></tr>
    <tr><td style="padding:18px 38px 30px">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">{$cta}</table>
    </td></tr>
    <tr><td style="padding:0 38px"><div style="height:1px;background:{$linea};font-size:0;line-height:0">&nbsp;</div></td></tr>
    {$footerTextoRow}
    {$socialRow}
    <tr><td align="center" style="padding:18px 38px 30px;font-family:Helvetica,Arial,sans-serif">
      <div style="font-size:12px;color:{$muted};line-height:1.7">{$legal}</div>
      <div style="font-size:12px;margin-top:8px"><a href="{$web}" style="color:{$tinta};text-decoration:none">{$webHost}</a></div>
      <div style="font-size:11px;color:#aaa6a0;margin-top:10px">© {$year} {$marca}{$footerExtra}</div>
      {$manageRow}
    </td></tr>
  </table>
  <div style="font-family:Helvetica,Arial,sans-serif;font-size:11px;color:#b8b4ac;margin-top:16px">Hecho con cuidado en Ecuador</div>
</td></tr>
</table>
</body></html>
HTML;
    }
}
