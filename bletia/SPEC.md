# Bletia Seridea — SPEC / memoria del proyecto · v0.9.0

> Documento de continuidad. Lee SOLO esto para retomar; no hace falta revisar el historial.
> Estilo de trabajo: optimizador máximo, terse, sin recap, entregable + pasos exactos. Español.

## ESTADO: bletia.ec EN PRODUCCIÓN Y FUNCIONANDO 100%.

## STACK / HOSTING
- Laravel 12.61.1 + Filament 3.3. Panel admin en `/admin`. Cuentas cliente en `/cuenta` (sesión `cliente_id`, separado del staff).
- Hostinger Business shared (CloudLinux/LiteSpeed). Proyecto: `~/domains/bletia.ec/bletia`.
- PHP CLI: `/opt/alt/php83/usr/bin/php`. Cada sesión SSH: `php=/opt/alt/php83/usr/bin/php` y correr `$php artisan ...`.
- SSH: `ssh -o ServerAliveInterval=60 u737310842@IP -p 65002` (PowerShell, expira rápido). Admin: bletiaform@gmail.com.

## REGLAS DE DESPLIEGUE (críticas, ya aprendidas)
1. `public_html` es carpeta REAL (copia de `bletia/public`); `public_html/index.php` => `require __DIR__.'/../bletia/vendor/autoload.php'`. `storage` sí es symlink.
2. Tras CADA update copiar assets: `cp -r public/css/* ../public_html/css/` y `cp -r public/js/* ../public_html/js/` — NUNCA sobrescribir `public_html/index.php`.
3. User implements FilamentUser con `canAccessPanel(): true`.
4. CDN/OPcache cachea agresivo. Tras cambios de vista: `rm -f storage/framework/views/*.php && $php artisan view:clear && optimize:clear`. Bust con `?v=`, Ctrl+Shift+R o incógnito.
5. Al generar PHP: revisar `use` duplicados (rompen panel).
6. BLADE EN ESTE SERVIDOR — NO USAR: (a) `@php(a)(b)` doble expresión en una línea; (b) `@continue`/`continue;` dentro de `@php…@endphp` en un `@foreach`; (c) bloques `@php … @endphp` multilínea propensos a romper el parser. USAR: `@php(...)` de una sola línea, y para saltar iteración `@if(filled($x)) … @endif`. Vistas que usan `$errors` deben blindarse: `@if(isset($errors) && $errors->any())`.

## ENTREGA
Carpeta de archivos-sobre-Laravel + README + (este) SPEC. Subir zip a `~/domains/bletia.ec/`, `unzip -o ../ZIP -d /tmp/X`, `cp -r /tmp/X/INNER/* .`, copiar assets a public_html, `migrate`, limpiar/cachear. Alternativa: subir archivos sueltos por hPanel File Manager. Validar balance de llaves PHP y anidamiento Blade (con pila, no solo conteo) ANTES de zipear.

## MARCA / DISEÑO
- Colores: primario #161921; secundario/botones #0499FC; footer #EFEBDD. Made to Order: texto/borde #263D3A, fondo #A7BC76 translúcido.
- Tipografía: títulos Geomanist-ExtraLight, texto Geomanist-Light, subtítulos Geomanist-Book (vía @font-face GeoExtraLight/GeoLight/GeoBook con fallback a Geomanist). PENDIENTE del usuario: subir `Geomanist-ExtraLight.otf`, `Geomanist-Light.otf`, `Geomanist-Book.otf` a `bletia/public/fonts/` y `public_html/fonts/` (solo llegaron las itálicas; mientras usa Geomanist normal de respaldo).
- Fotos/galería con esquinas RECTAS. Botones pill (100%). Menú y "Made to Order" 17px. Variables y precio en Geomanist-Light.
- Precio en DB (`precio`) = PVP (IVA incluido); se muestra PVP + "Incluido IVA". IVA EC 15% configurable.

## URLS (catch-alls al FINAL de routes/web.php: artículo 2-seg, luego página 1-seg)
- Producto `/producto/{slug}`; categoría tienda `/categoria/{slug}`.
- Blog: `/blog`, `/blog/categoria/{slug}`, `/blog/etiqueta/{slug}`, `/blog/autor/{slug}`.
- Artículo (canónico, 301 si no coincide): `/{categoria}/{slug}` en la raíz.
- Página/landing: `/{slug}` en la raíz (NO usar slugs reservados: blog, carrito, checkout, cuenta, buscar, producto, categoria, p…).
- Cuenta `/cuenta` (login/registro/panel/salir). Newsletter `/newsletter`. Carrito/checkout `/carrito*`, `/checkout*`.

## MÓDULOS IMPLEMENTADOS (todos en producción)
- Catálogo/SEO: locales, categorias, productos (+SEO/JSON-LD), producto_imagenes, sitemap.xml. Producto: accessors `precio_con_iva`, `comprable`, `bajo_pedido`, `imagen_principal`.
- Carrito + Checkout + PayPhone Cajita de Pagos v2.0 (hosted widget, dominio bletia.ec, respuesta /checkout/confirmar; amounts en CENTAVOS; PAYPHONE_TOKEN + PAYPHONE_STORE_ID en .env). CONFIRMADO funcionando.
  - Cart (App\Support\Cart): una línea por combinación producto+variantes; key=producto_id|ids; `pvp` unit = producto.precio + extras (IVA incl); totales derivan neto/iva. pedido_items + columna `variantes` (label). PayPhone::montos acepta lineas ['pvp','iva_rate','cantidad'] (o legacy ['producto']).
- Diseño/tema: fuentes, colores vía modelo Ajuste (Ajuste::get/set; arrays => json), página ConfiguracionTienda (incluye Footer con Repeaters de menús "Nosotros" y "Legal" {titulo,url}; "Colecciones" auto). Home, footer, cuenta cliente, lightbox.
- Blog + Páginas + headers múltiples (tienda/partials/header tipos tienda|paginas|blog|articulo). Menú móvil = DRAWER popup pantalla completa.
- ERP stock: movimientos_stock (entrada/salida/ajuste/transferencia -> aplica a Stock). MovimientoStockResource. Página "Inventario → Stock por bodega" (resalta bajo mínimo). Pedido: +bodega_despacho_id +despachado_at; PedidoResource acción "Despachar" (pagado -> genera salidas -> estado despachado).
- Editor por BLOQUES (Filament Builder, campo `bloques` json) en Artículos Y Páginas. Bloques: titulo(h2/h3), texto(RichEditor), imagen(ancho completa/grande/mediana + alineación + pie), imagen_texto(2 columnas, pos izq/der), tabla (filas por línea, columnas con "|", 1ª fila encabezado), cita. Render en tienda/partials/bloques.blade. Campo `contenido` (RichEditor simple) queda como respaldo/legacy.

## VARIABLES / VARIANTES DE PRODUCTO (v0.8.0 — modelo COMBINACIÓN)
- Biblioteca: atributos(nombre,tipo[color|imagen|texto]) + atributo_opciones(valor,color,imagen). AtributoResource grupo "Catálogo" = "Variables de producto".
- variantes (cada fila = UNA combinación a la venta): columnas nuevas `pvp`(req), `costo`(req, compra/fabricación), `opciones`(json {atributo_id:opcion_id}), `foto`(req). Legacy sin uso: precio_extra/atributo_opcion_id/nombre/valor/color.
- Variante accessors: `pvp_final` (pvp ?: producto.precio), `combo_label` ("Tapiz: Beige · Lado: Left" vía AtributoOpcion), `foto_url`.
- ProductoResource → Repeater 'variantes' (relationship): Fieldset dinámico con un Select por cada Atributo (`opciones.{id}`, opcional) + PVP(req) + Costo(req) + Foto(req). itemLabel = combinación + $pvp. Guard: si no hay atributos, Placeholder.
- Producto: campo `mto_texto` (editable) + accessor `mto_texto_final`. Toggle permitir_pedido (=Made to Order sin stock). bajo_pedido accessor = sin stock y (permitir_pedido o ajuste).
- Front producto.blade: calcula attrs usados (aids) + opciones (AtributoOpcion) + $vdata json [{id,pvp,foto,op:{aid:oid}}]. Renderiza un selector por atributo (imagen=swatch cuadrado / color=círculo / texto=select). Precio inicial = min(pvp) o precio base. Desglose IVA bajo el precio. JS (tienda.js "Selector de combinación"): al elegir, arma `selected{aid:oid}`; cuando completa nAttrs busca la variante cuyo `op` coincide EXACTO -> setea #t-variante-id, PVP, IVA, foto, habilita botón; si falta o no existe combinación, deshabilita + hint. Botón SIEMPRE "Comprar". MTO solo en Detalles + carrito/checkout (badge) + factura(futuro).
- Cart (reescrito): UNA variante por línea. key=producto_id|variante_id. pvp=variante.pvp_final o producto.precio. label=combo_label. img=variante.foto_url o producto. mto=producto.bajo_pedido. CarritoController.agregar lee `variante_id` (único). PayPhone/checkout sin cambios (usan $l['pvp']/$l['label']).
- NOTA: tras v0.8.0 hay que RE-CREAR las variantes de cada producto en el nuevo formato (las viejas no fijan precio).


## BLOG AVANZADO
- etiquetas + articulo_etiqueta (Articulo::etiquetas belongsToMany). EtiquetaResource (grupo Blog). En artículo: cápsulas al final -> /blog/etiqueta/{slug}.
- editores(nombre,slug,cargo,bio,foto,redes web/instagram/facebook/x/linkedin). EditorResource (grupo Blog). articulos+editor_id (Articulo::editor). En artículo: barra lateral con AUTOR (foto+nombre+cargo) -> /blog/autor/{slug}.
- Artículo: sidebar fija = fecha actualización, minutos_lectura (accessor), índice TOC (JS desde h2/h3, 3 + "ver más", scroll suave), categoría, AUTOR, compartir popup + copiar enlace; ETIQUETAS al final.
- BlogController: articulo(categoria,Articulo) con redirect canónico; etiqueta(Etiqueta) y autor(Editor) -> vista blog/listado.

## LOGIN MODAL
- tienda/partials/auth-modal.blade (tabs Ingresar/Crear cuenta -> /cuenta/login y /cuenta/registro). Incluido en layout si `!session('cliente_id')`. Abre con botón #t-open-auth (ícono login del header) o si hay errores. BLINDADO: `@if(isset($errors) && $errors->any())`.



## MÓDULO 9 — MARKETING (Digest) · Fase 1 (v0.9.0)
- Tablas: listas, suscriptores(estado pendiente|confirmado|baja|rebotado, token, source), lista_suscriptor (pivot), formularios(tipo inline|popup|slide_in|bar_top|bar_bottom|after_content, lista_ids json, opciones json).
- Modelos: Lista (slug auto, suscriptores Bt M), Suscriptor (token auto al crear, listas BtM, nombre_completo), Formulario (casts lista_ids/opciones/pedir_nombre).
- Filament grupo "Marketing": SuscriptorResource (filtros estado/lista, badges), ListaResource, FormularioResource.
- Opt-in DOBLE. DigestController: subscribe (honeypot 'website' + RateLimiter 5/h por IP; firstOrNew por email; reactiva si baja/rebotado; adjunta listas del form o 'Newsletter' por defecto; envía Mail ConfirmarSuscripcion), confirm (sid+token), unsubscribeForm/unsubscribe, preferencesForm/preferences. Lista 'newsletter' se crea con firstOrCreate.
- Mailable App\Mail\ConfirmarSuscripcion + vista emails/confirmar.blade. Vistas públicas digest/confirmado|baja|preferencias (layout tienda).
- Rutas (en web.php ANTES de los catch-all; DigestController por FQN, sin `use` a mitad de archivo): POST /newsletter (=subscribe), POST /digest/subscribe, GET /digest/confirm, GET|POST /digest/unsubscribe, GET|POST /digest/preferences. El newsletter del footer ya alimenta el módulo.
- SMTP: BREVO vía .env (MAIL_MAILER=smtp, host smtp-relay.brevo.com, 587, tls, MAIL_USERNAME/PASSWORD=SMTP key, FROM no-reply@bletia.ec verificado SPF/DKIM). Mailer por defecto de Laravel.
- PENDIENTE Fase 2: Campañas + motor de envío (cola/throttle speed_per_hour/batch_size, personalización {first_name}…, Branding wrap header/footer, tracking open pixel /digest/track/open y click /digest/track/click). Fase 3 (Pro): automations (post_publish/digest_daily/weekly/birthday), resources/lead magnets, webhooks/bounces, housekeeping. Front-render de formularios (popup/slide-in/bar) también pendiente. Spec completa en archivo del usuario digest-spec-filament.md.

## ROADMAP (siguiente)
- **Módulo 9 Marketing Fase 2** (campañas + envío Brevo + tracking) ← PRÓXIMO probable.
- **Módulo 5 — Facturación electrónica SRI** (emitir al aprobarse el pago). Dátil vs Contífico.
- Módulo 6 — Contabilidad EC / ATS.
- Módulo 7 — SEO/GEO: añadir artículos/páginas/etiquetas/autores a sitemap.xml (pendiente), llms.txt, performance.

## VERSIONES
v0.1.0 catálogo · v0.2.0 carrito/PayPhone · v0.4.0 diseño · v0.5.0 blog/páginas/ERP · v0.6.0 correcciones+despacho+inventario · v0.6.1/6.2 fixes URL/bloques/menú/footer · v0.7.0 variables/etiquetas/editores/bloques-páginas/tipografía/made-to-order/fotos-rectas/pill/modal · v0.7.1 producto estilo Westwing · v0.7.2 foto por variante + blindaje · v0.7.3 fix 500 producto · v0.8.0 variantes por COMBINACIÓN (pvp+costo+foto obligatorios, opciones json), botón siempre "Comprar", Made to Order editable (mto_texto) y solo en detalles/resumen/factura, Cart 1-variante-por-línea · v0.8.1 oculta variables no usadas + disponibilidad/Made-to-Order (En stock·N / texto MTO) + limpia opciones vacías · v0.9.0 Módulo 9 Marketing Fase 1 (suscriptores/listas/formularios + opt-in doble Brevo). TODO APLICADO.
