<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditorJsController extends Controller
{
    /** Sube una imagen (por archivo o por URL) y devuelve el formato que espera @editorjs/image. */
    public function uploadImage(Request $request)
    {
        try {
            if ($request->hasFile('image')) {
                $request->validate(['image' => 'required|image|max:8192']);
                $path = $request->file('image')->store('editorjs/images', 'public');
                return response()->json(['success' => 1, 'file' => ['url' => Storage::disk('public')->url($path)]]);
            }

            if ($request->filled('url')) {
                $url = $request->input('url');
                $contents = @file_get_contents($url);
                if ($contents === false) {
                    return response()->json(['success' => 0, 'message' => 'No se pudo descargar la imagen'], 422);
                }
                $ext = pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'jpg';
                $name = 'editorjs/images/' . Str::random(20) . '.' . $ext;
                Storage::disk('public')->put($name, $contents);
                return response()->json(['success' => 1, 'file' => ['url' => Storage::disk('public')->url($name)]]);
            }

            return response()->json(['success' => 0, 'message' => 'Sin archivo ni URL'], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => 0, 'message' => $e->getMessage()], 500);
        }
    }

    /** Sube un archivo adjunto genérico (@editorjs/attaches). */
    public function uploadFile(Request $request)
    {
        try {
            $request->validate(['file' => 'required|file|max:20480']);
            $file = $request->file('file');
            $path = $file->store('editorjs/attaches', 'public');
            return response()->json([
                'success' => 1,
                'file' => [
                    'url' => Storage::disk('public')->url($path),
                    'size' => $file->getSize(),
                    'name' => $file->getClientOriginalName(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => 0, 'message' => $e->getMessage()], 500);
        }
    }

    /** Metadatos Open Graph de una URL pegada (@editorjs/link). */
    public function fetchUrl(Request $request)
    {
        $url = $request->query('url');
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['success' => 0], 422);
        }

        try {
            $html = @file_get_contents($url, false, stream_context_create([
                'http' => ['timeout' => 5, 'header' => "User-Agent: Mozilla/5.0\r\n"],
            ]));
            if (! $html) return response()->json(['success' => 0], 422);

            $og = function (string $prop) use ($html) {
                if (preg_match('/<meta[^>]+property=["\']' . preg_quote($prop, '/') . '["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $m)) return $m[1];
                if (preg_match('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+property=["\']' . preg_quote($prop, '/') . '["\']/i', $html, $m)) return $m[1];
                return null;
            };
            $title = $og('og:title') ?: (preg_match('/<title>(.*?)<\/title>/is', $html, $m) ? trim($m[1]) : $url);
            $desc = $og('og:description') ?: '';
            $image = $og('og:image') ?: '';

            return response()->json([
                'success' => 1,
                'link' => $url,
                'meta' => [
                    'title' => $title,
                    'description' => $desc,
                    'image' => $image ? ['url' => $image] : null,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => 0], 422);
        }
    }
}
