<?php

namespace App\Support;

/**
 * Convierte el JSON de bloques de Editor.js a HTML para guardar en la columna
 * de texto plano que ya usan las vistas públicas (contenido/descripcion).
 */
class EditorJsRenderer
{
    public static function render($input): string
    {
        if (! $input) return '';
        if (is_array($input)) {
            $data = $input;
        } else {
            $data = json_decode((string) $input, true);
        }
        if (! is_array($data) || empty($data['blocks'])) return '';

        $html = '';
        foreach ($data['blocks'] as $block) {
            $type = $block['type'] ?? '';
            $d = $block['data'] ?? [];
            $html .= match ($type) {
                'header'     => self::header($d),
                'paragraph'  => self::paragraph($d),
                'list'       => self::list($d),
                'checklist'  => self::checklist($d),
                'quote'      => self::quote($d),
                'table'      => self::table($d),
                'delimiter'  => '<hr class="ej-delimiter">',
                'code'       => self::code($d),
                'image'      => self::image($d),
                'warning'    => self::warning($d),
                'embed'      => self::embed($d),
                'raw'        => $d['html'] ?? '',
                'attaches'   => self::attaches($d),
                'linkTool'   => self::linkTool($d),
                default      => '',
            };
        }
        return $html;
    }

    protected static function header(array $d): string
    {
        $level = (int) ($d['level'] ?? 2);
        $level = max(2, min(4, $level)); // nunca H1 dentro del cuerpo
        $text = $d['text'] ?? '';
        return "<h{$level}>{$text}</h{$level}>";
    }

    protected static function paragraph(array $d): string
    {
        $text = $d['text'] ?? '';
        if ($text === '') return '';
        return "<p>{$text}</p>";
    }

    protected static function list(array $d): string
    {
        $style = ($d['style'] ?? 'unordered') === 'ordered' ? 'ol' : 'ul';
        $items = '';
        foreach (($d['items'] ?? []) as $item) {
            $text = is_array($item) ? ($item['content'] ?? '') : $item;
            $items .= "<li>{$text}</li>";
        }
        return "<{$style}>{$items}</{$style}>";
    }

    protected static function checklist(array $d): string
    {
        $items = '';
        foreach (($d['items'] ?? []) as $item) {
            $checked = ! empty($item['checked']) ? 'checked disabled' : 'disabled';
            $items .= '<li class="ej-check"><input type="checkbox" ' . $checked . '> ' . ($item['text'] ?? '') . '</li>';
        }
        return "<ul class=\"ej-checklist\">{$items}</ul>";
    }

    protected static function quote(array $d): string
    {
        $text = $d['text'] ?? '';
        $caption = $d['caption'] ?? '';
        $cap = $caption ? "<cite>{$caption}</cite>" : '';
        return "<blockquote>{$text}{$cap}</blockquote>";
    }

    protected static function table(array $d): string
    {
        $rows = $d['content'] ?? [];
        $withHead = ! empty($d['withHeadings']);
        $html = '<table class="ej-table">';
        foreach ($rows as $i => $row) {
            $tag = ($withHead && $i === 0) ? 'th' : 'td';
            $html .= '<tr>';
            foreach ($row as $cell) $html .= "<{$tag}>{$cell}</{$tag}>";
            $html .= '</tr>';
        }
        return $html . '</table>';
    }

    protected static function code(array $d): string
    {
        $code = e($d['code'] ?? '');
        return "<pre class=\"ej-code\"><code>{$code}</code></pre>";
    }

    protected static function image(array $d): string
    {
        $url = $d['file']['url'] ?? ($d['url'] ?? '');
        if (! $url) return '';
        $caption = $d['caption'] ?? '';
        $cls = 'ej-image';
        if (! empty($d['withBorder'])) $cls .= ' ej-image--border';
        if (! empty($d['stretched'])) $cls .= ' ej-image--stretched';
        if (! empty($d['withBackground'])) $cls .= ' ej-image--bg';
        $fig = "<figure class=\"{$cls}\"><img src=\"" . e($url) . "\" alt=\"" . e(strip_tags($caption)) . "\" loading=\"lazy\">";
        if ($caption) $fig .= "<figcaption>{$caption}</figcaption>";
        return $fig . '</figure>';
    }

    protected static function warning(array $d): string
    {
        $title = $d['title'] ?? '';
        $message = $d['message'] ?? '';
        return "<div class=\"ej-warning\"><strong>{$title}</strong><p>{$message}</p></div>";
    }

    protected static function attaches(array $d): string
    {
        $url = $d['file']['url'] ?? '';
        $name = $d['title'] ?? ($d['file']['name'] ?? 'Archivo adjunto');
        $size = isset($d['file']['size']) ? self::humanSize((int) $d['file']['size']) : '';
        if (! $url) return '';
        return '<a class="ej-attach" href="' . e($url) . '" target="_blank" rel="noopener">📎 ' . e($name) . ($size ? " <span>({$size})</span>" : '') . '</a>';
    }

    protected static function linkTool(array $d): string
    {
        $link = $d['link'] ?? '';
        $meta = $d['meta'] ?? [];
        $title = $meta['title'] ?? $link;
        $desc = $meta['description'] ?? '';
        $img = $meta['image']['url'] ?? null;
        if (! $link) return '';
        $html = '<a class="ej-linktool" href="' . e($link) . '" target="_blank" rel="noopener">';
        if ($img) $html .= '<span class="ej-linktool__img" style="background-image:url(\'' . e($img) . '\')"></span>';
        $html .= '<span class="ej-linktool__body"><strong>' . e($title) . '</strong>';
        if ($desc) $html .= '<span class="ej-linktool__desc">' . e($desc) . '</span>';
        $html .= '<span class="ej-linktool__url">' . e(parse_url($link, PHP_URL_HOST) ?: $link) . '</span></span></a>';
        return $html;
    }

    /** Embed responsive con soporte de 7 plataformas + schema VideoObject en YouTube/Vimeo. */
    protected static function embed(array $d): string
    {
        $embed = $d['embed'] ?? null;
        $service = $d['service'] ?? '';
        $caption = $d['caption'] ?? '';
        if (! $embed) return '';

        $schema = '';
        if (in_array($service, ['youtube', 'vimeo'], true)) {
            $schema = '<script type="application/ld+json">' . json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'VideoObject',
                'name' => $caption ?: 'Video',
                'description' => $caption ?: 'Video',
                'embedUrl' => $embed,
                'thumbnailUrl' => $d['source'] ?? '',
                'uploadDate' => now()->toIso8601String(),
            ], JSON_UNESCAPED_SLASHES) . '</script>';
        }

        $html = '<div class="embed-wrap embed-wrap--' . e($service) . '"><iframe src="' . e($embed)
            . '" frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>';
        if ($caption) $html .= '<p class="ej-embed-caption">' . e($caption) . '</p>';
        return $html . $schema;
    }

    protected static function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < 3) { $bytes /= 1024; $i++; }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}
