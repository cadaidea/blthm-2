<?php


use App\Http\Controllers\EnlacesController;
Route::redirect('/admin', '/dash');
Route::redirect('/admin/login', '/dash/login');
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\GuardadoController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PaginaController;
use App\Http\Controllers\TiendaController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

// Storefront
Route::get('/', [TiendaController::class, 'home'])->name('tienda.home');
Route::get('/sitemap.xml', [TiendaController::class, 'sitemap']);
Route::get('/robots.txt', [TiendaController::class, 'robots']);
Route::get('/feed.xml', [\App\Http\Controllers\BlogController::class, 'feed']);
// indexnow_key_route
Route::get('/{key}.txt', function (string $key) {
    abort_unless($key === \App\Support\IndexNow::key(), 404);
    return response($key, 200)->header('Content-Type', 'text/plain');
})->where('key', '[a-f0-9]{32}');
Route::get('/buscar', [TiendaController::class, 'buscar'])->name('tienda.buscar');
Route::get('/buscar/api', [TiendaController::class, 'buscarApi'])->name('tienda.buscar.api');
Route::get('/api/enlaces', [EnlacesController::class, 'index'])->name('tienda.enlaces.api');
Route::get('/categoria/{categoria}', [TiendaController::class, 'categoria'])->name('tienda.categoria');
Route::get('/producto/{producto}', [TiendaController::class, 'producto'])->name('tienda.producto');

// Blog
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/categoria/{blogCategoria}', [BlogController::class, 'categoria'])->name('blog.categoria');
Route::get('/blog/etiqueta/{etiqueta}', [BlogController::class, 'etiqueta'])->name('blog.etiqueta');
Route::get('/blog/autor/{editor}', [BlogController::class, 'autor'])->name('blog.autor');

// Carrito
Route::get('/carrito', [CarritoController::class, 'ver'])->name('carrito.ver');
Route::post('/carrito/agregar/{producto}', [CarritoController::class, 'agregar'])->name('carrito.agregar');
Route::post('/carrito/actualizar', [CarritoController::class, 'actualizar'])->name('carrito.actualizar');
Route::post('/carrito/eliminar', [CarritoController::class, 'eliminar'])->name('carrito.eliminar');

// Checkout + PayPhone
Route::get('/checkout', [CheckoutController::class, 'form'])->name('checkout.form');
Route::post('/checkout', [CheckoutController::class, 'crear'])->name('checkout.crear');
Route::get('/checkout/pagar/{pedido}', [CheckoutController::class, 'pagar'])->name('checkout.pagar');
Route::get('/checkout/confirmar', [CheckoutController::class, 'confirmar'])->name('checkout.confirmar');
Route::get('/checkout/gracias/{pedido}', [CheckoutController::class, 'gracias'])->name('checkout.gracias');

// Cuenta de cliente (separada del panel admin /admin)
Route::get('/cuenta', [CuentaController::class, 'panel'])->name('cuenta.panel');
Route::get('/cuenta/login', [CuentaController::class, 'loginForm'])->name('cuenta.login');
Route::post('/cuenta/login', [CuentaController::class, 'login']);
Route::get('/cuenta/registro', [CuentaController::class, 'registroForm'])->name('cuenta.registro');
Route::post('/cuenta/registro', [CuentaController::class, 'registro']);
Route::post('/cuenta/salir', [CuentaController::class, 'salir'])->name('cuenta.salir');
Route::post('/guardar/{producto}', [GuardadoController::class, 'toggle'])->name('guardar.toggle');
Route::get('/guardados', [GuardadoController::class, 'index'])->name('cuenta.guardados');
Route::get('/cuenta/verificar/{id}/{token}', [CuentaController::class, 'verificar'])->name('cuenta.verificar');
Route::post('/cuenta/reenviar-verificacion', [CuentaController::class, 'reenviarVerificacion'])->name('cuenta.reenviar');


// === Marketing (Digest) ===
Route::post('/newsletter', [\App\Http\Controllers\DigestController::class, 'subscribe'])->name('newsletter');
Route::post('/digest/subscribe', [\App\Http\Controllers\DigestController::class, 'subscribe'])->name('digest.subscribe');
Route::get('/digest/confirm', [\App\Http\Controllers\DigestController::class, 'confirm'])->name('digest.confirm');
Route::get('/digest/unsubscribe', [\App\Http\Controllers\DigestController::class, 'unsubscribeForm'])->name('digest.unsubscribe.form');
Route::post('/digest/unsubscribe', [\App\Http\Controllers\DigestController::class, 'unsubscribe'])->name('digest.unsubscribe');
Route::get('/digest/preferences', [\App\Http\Controllers\DigestController::class, 'preferencesForm'])->name('digest.preferences.form');
Route::post('/digest/preferences', [\App\Http\Controllers\DigestController::class, 'preferences'])->name('digest.preferences');
Route::get('/digest/track/open', [\App\Http\Controllers\DigestTrackController::class, 'open'])->name('digest.track.open');
Route::get('/digest/track/click', [\App\Http\Controllers\DigestTrackController::class, 'click'])->name('digest.track.click');
Route::get('/digest/impr', [\App\Http\Controllers\DigestController::class, 'impression'])->name('digest.impr');
Route::post('/api/digest/webhook/bounce', [\App\Http\Controllers\DigestWebhookController::class, 'bounce'])->name('digest.webhook.bounce');

// === Recursos / lead magnets + back-in-stock ===
Route::post('/api/digest/recurso', [\App\Http\Controllers\RecursoController::class, 'solicitar'])->name('recurso.solicitar');
Route::get('/recurso/descargar/{token}', [\App\Http\Controllers\RecursoController::class, 'descargar'])->name('recurso.descargar');
Route::post('/api/digest/back-in-stock', [\App\Http\Controllers\RecursoController::class, 'avisoStock'])->name('recurso.aviso');

Route::get('/confirmar/{token}', [\App\Http\Controllers\ConfirmacionController::class, 'show'])->name('erp.confirmar.show');
Route::post('/confirmar/{token}', [\App\Http\Controllers\ConfirmacionController::class, 'submit'])->name('erp.confirmar.submit');
Route::get('/confirmar-garantia/{token}', [\App\Http\Controllers\GarantiaController::class, 'show'])->name('garantia.show');
Route::post('/confirmar-garantia/{token}', [\App\Http\Controllers\GarantiaController::class, 'submit'])->name('garantia.submit');
Route::get('/confirmar-compra/{token}', [\App\Http\Controllers\CompraProveedorController::class, 'show'])->name('compra.show');
Route::post('/confirmar-compra/{token}', [\App\Http\Controllers\CompraProveedorController::class, 'submit'])->name('compra.submit');
Route::get('/confirmar-compra/{token}/etiquetas', [\App\Http\Controllers\CompraProveedorController::class, 'etiquetas'])->name('compra.etiquetas');
Route::get('/confirmar-garantia/{token}/etiquetas', [\App\Http\Controllers\GarantiaController::class, 'etiquetas'])->name('garantia.etiquetas');
Route::get('/confirmar/{token}/etiquetas', [\App\Http\Controllers\ConfirmacionController::class, 'etiquetas'])->name('erp.confirmar.etiquetas');
Route::get('/seguimiento', [\App\Http\Controllers\SeguimientoController::class, 'show'])->name('erp.seguimiento');
Route::get('/shop', [ShopController::class, 'index'])->name('tienda.shop');

// Editor.js — subida de archivos y metadatos de enlace (panel admin)
Route::post('/editorjs/upload-image', [\App\Http\Controllers\EditorJsController::class, 'uploadImage'])->middleware(['web','auth'])->name('editorjs.upload-image');
Route::post('/editorjs/upload-file', [\App\Http\Controllers\EditorJsController::class, 'uploadFile'])->middleware(['web','auth'])->name('editorjs.upload-file');
Route::get('/editorjs/fetch-url', [\App\Http\Controllers\EditorJsController::class, 'fetchUrl'])->middleware(['web','auth'])->name('editorjs.fetch-url');
Route::get('/editorjs/content-search', [\App\Http\Controllers\ContentSearchController::class, '__invoke'])->middleware(['web','auth'])->name('editorjs.content-search');

// Contacto (ruta especifica, debe ir ANTES del catch-all de paginas dinamicas)
Route::get('/contacto', [\App\Http\Controllers\ContactoController::class, 'index'])->name('contacto.form');
Route::post('/contacto', [\App\Http\Controllers\ContactoController::class, 'submit'])->name('contacto.submit');

// Artículo del blog en la raíz: dominio/{categoria}/{slug}. DEBE ir al final. // ARTICULO_CATCHALL
Route::get('/{categoria}/{articulo}', [BlogController::class, 'articulo'])->name('blog.articulo');

// Página/landing en la raíz: dominio/{slug}. DEBE ir al final del archivo.
Route::get('/{pagina}', [PaginaController::class, 'show'])->name('paginas.show');

Route::get('/descargas/etiquetas-compra/{compra}', function (\App\Models\Compra $compra) {
    abort_unless(auth()->check(), 403);
    $path = storage_path('app/etiquetas/ETIQUETAS-COMPRA_' . ($compra->folio ?: $compra->id) . '.pdf');
    abort_unless(file_exists($path), 404);
    return response()->download($path, 'etiquetas-' . ($compra->folio ?: $compra->id) . '.pdf');
})->middleware(['web', 'auth'])->name('etiquetas.compra.descarga');
