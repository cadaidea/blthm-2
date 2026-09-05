<?php
namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class BletiaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // CSS de marca servido como asset normal (sin Vite) + cache-buster
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => new HtmlString('<link rel="stylesheet" href="/css/bletia-panel-v38.css?v=' . date('Ymd') . '">'),
        );
        // Editor.js — CSS de bloques (global, para que cargue aunque el campo se agregue por Livewire)
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => new HtmlString('<link rel="stylesheet" href="/css/editorjs-field.css?v=' . date('Ymd') . '">'),
        );
        // Autocompletado de URLs internas en editores (Bletia)
        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_AFTER,
            fn (): string => new HtmlString('<script src="/js/dash-enlaces-v2.js?v=' . date('Ymd') . '"></script>'),
        );
        // Buscador de enlaces (funcion global independiente): debe existir ANTES
        // de que Alpine/Livewire inicialicen los componentes de la pagina, por eso
        // va en SCRIPTS_BEFORE (no SCRIPTS_AFTER) y con version por filemtime real
        // (no por fecha) para que el navegador nunca sirva una copia vieja en cache.
        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_BEFORE,
            fn (): string => new HtmlString('<script src="/js/editorjs-link-search.js?v=' . @filemtime(public_path('js/editorjs-link-search.js')) . '"></script>'),
        );
        // Editor.js — bundle + inicializador Alpine (global, disponible en toda página del panel)
        // SCRIPTS_BEFORE: debe cargar ANTES de que Alpine arranque (@filamentScripts corre luego de SCRIPTS_AFTER... no, corre ANTES de SCRIPTS_AFTER y despues de SCRIPTS_BEFORE)
        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_AFTER,
            fn (): string => new HtmlString('<script src="/js/editorjs-bundle.js?v=' . @filemtime(public_path('js/editorjs-bundle.js')) . '"></script><script src="/js/editorjs-init.js?v=' . @filemtime(public_path('js/editorjs-init.js')) . '"></script>'),
        );

        return $panel
            ->default()
            ->id('bletia')
            ->path('dash')
            ->login()
            ->brandName('Bletia')
            ->favicon(asset('favicon.ico') . '?v=' . (@filemtime(public_path('favicon.ico')) ?: time()))
            ->navigationGroups([
                'Ventas',
                'Logística',
                'Compras',
                'Inventario',
                'Producción',
                'Catálogo',
                'Contabilidad',
                'Nómina / RRHH',
                'Blog',
                'Marketing',
                'Ajustes',
                'Sistema',
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::TOPBAR_START,
                fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString((function(){
                    $u=auth()->user(); if(!$u) return '';
                    $rol=\App\Support\Acl::ROLES[$u->rol ?? '']??ucfirst($u->rol??'');
                    $nom=trim(explode(' ', trim($u->name??''))[0] ?? '');
                    return '<div class="bletia-userbar" style="display:flex;align-items:center;gap:6px;font-family:sans-serif;padding-left:6px">'
                        .'<span style="font-weight:700;color:#161921">BLETIA</span>'
                        .'<span style="color:#bbb">|</span>'
                        .'<span style="font-weight:600;color:#0499FC">'.e($rol).'</span>'
                        .'<span style="color:#888">· Soy '.e($nom).'</span></div>';
                })()),
            )
            ->colors([
                'primary' => Color::hex('#161921'),
                'info'    => Color::hex('#0499FC'),
                'taller'  => Color::hex('#7a5af8'),
                'ok'      => Color::hex('#2e9e6b'),
            ])
            ->globalSearch() // búsqueda global en la topbar
            ->sidebarCollapsibleOnDesktop()
            ->profile(\App\Filament\Pages\PerfilBletia::class) // colapsa en escritorio, drawer en móvil
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\TableroBletia::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
