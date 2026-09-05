# BLETIA · Estado del proyecto (BACKUP COMPLETO)

> **Snapshot de seguridad**: este documento registra TODO lo que funciona hoy.
> Toda mejora nueva se agrega SOBRE este estado, sin reescribir lo que ya funciona.
> Stack: React 18 + Vite + Tailwind CSS 4 + TypeScript · Geomanist (público) / Inter (dash)
> Fondos claros #ffffff · marca #800000 puntual · modo oscuro solo en dash.

## 0. Mapa de archivos (inventario físico)
- `index.html` — Inter (Google) + @font-face Geomanist (public/fonts/, font-display:swap)
- `src/main.tsx`, `src/App.tsx` — rutas: tienda (`#/`), panel (`#/dash`, `#/dash/login`, alias `#/panel`)
- `src/index.css` — tokens de marca (paper #fff, maroon #800000), modo oscuro `.dark`, animaciones
- `src/data.ts` — semilla completa + IMG (mapa central de fotos) + CMS/Sitio (load/save localStorage)
- `src/utils/sri.ts` — validación Módulo 10/11 cédula/RUC
- `src/components/ui.tsx` — iconos SVG propios, Reveal, Modal, CopyBtn, CodeBlock
- `src/components/Storefront.tsx` — tienda pública completa
- `src/components/panel/` — pui, auth, Panel + Modules 1–6
- `referencia/` — carpeta TEMPORAL de intercambio (se borra al final)

## 0.1. Storefront — todo lo que la tienda hace HOY
- Barra de anuncio fija (configurable en panel) + header fijo con blur al hacer scroll
- Header: logo BLETIA, navegación desde `site.menus.tienda`, y 4 acciones:
  búsqueda global (piezas + diario + secciones), cuenta (nombre persistido),
  mis deseos (drawer + corazones en tarjetas y quick view), carrito (badge contador)
- Apertura: producto destacado configurable (Ken Burns) + tarjeta superpuesta
- Colección: filtros por categoría (colecciones apagables desde el panel), tarjetas con
  corazón, quick view (specs, stock, IVA), "Añadir" y "Comprar ahora"
- Secciones: Taller (4 pasos, foto), Servicios (4 bloques), Diario (servido por el CMS),
  Footer (menús desde CMS, bloque #legal, sin mención al panel), copyright limpio
- Checkout PayPhone: datos (cédula/RUC validados Módulo 10/11), método link 24h o directo
  (según lo publicado en Sitio público), confirmación con link de seguimiento de un uso
- Persistencia: carrito, deseos y cuenta en localStorage

## 0.2. Panel — 15 módulos en 6 grupos (IA exacta del original)
- Operación: Panel de control (bus de eventos +2.000 ev/s, prueba de carga, KPIs) ·
  Pedidos OMS (15 estados + pedido bajo specs con fotos por campo) · Logística & guías
  SRI (autorización 49 dígitos + etiquetas) · Taller & fabricación (fases) · BOM & materiales (MRP)
- Relaciones: Clientes & proveedores (pestañas CRM+SRM, consulta por documento SRI,
  caché 5 min, modo offline) · Cobros PayPhone
- Producto & activos: Productos PIM · Fototeca DAM (subida simulada, aprobación, URL CDN)
- Finanzas: Contabilidad & SRI (Facturas + Partida doble + Formulario 104, export CSV)
- Plataforma: Accesos de un solo uso · Seguridad & porting · Ajustes & despliegue (guía OVH)
- Canal digital: Sitio público (anuncio, destacado, colecciones, SEO, pagos) ·
  Contenido web CMS (diario publicar/despublicar/eliminar + editor de menús del sitio)
- Auth: login por roles en /dash/login (logo SVG BLETIA), auto-Gerencia, sesión v2,
  menú de usuario (entrar como colaborador / volver a Gerencia), contraste claro/oscuro,
  bienvenida editable ("inspírate · editar mensaje")

## 0.3. Reglas que NO se tocan
1. Roles: Gerencia ve los 15 módulos; cada trabajador solo su área (infra/seguridad solo Gerencia).
2. Auto-Gerencia al entrar sin sesión válida de trabajador.
3. Fondo claro #ffffff; modo oscuro solo dash; #800000 puntual.
4. La tienda nunca muestra "Panel interno" (acceso equipo: solo texto bletia.ec/dash en popup de cuenta).
5. Las fotos viven en el mapa IMG de data.ts (un solo punto de cambio).
6. Lo publicado en Canal digital rige la tienda (anuncio, destacado, colecciones, pagos, menús, diario).

## 1. Inventario de lo implementado (estado actual)

### Tienda pública (Geomanist · bletia.ec)
- Apertura editorial con producto destacado configurable (Ken Burns) y tarjeta superpuesta
- Colección: 7 productos, filtros por categoría, quick view con specs, desglose base/IVA 15%
- Carrito persistente (localStorage) con cantidades y totales
- Checkout PayPhone: link de un solo uso (24 h) y pago directo (diferido), confirmación con
  código de orden + link privado de seguimiento de un uso
- Secciones: Taller (proceso 4 pasos), Servicios (guante blanco, garantía, proyectos), Diario, footer legal EC

### Panel interno (Inter · /dash → login con logo BLETIA)
- **Roles**: Gerencia (los 13 módulos), Ventas, Taller, Logística, Contabilidad — cada uno ve solo su área.
  El dueño entra automáticamente como Gerencia (sesión v2); colaboradores vía menú del avatar.
- **Contraste del dash** claro/oscuro (solo panel, la tienda siempre va en claro)
- **Bienvenida editable** ("inspírate · editar mensaje", persiste)
- **14 módulos en 3 grupos** (5 + 5 + 4 + encabezados = 17 filas en sidebar):
  - Operación: Panel de control (bus de eventos +2.000 ev/s, prueba de carga, cola Redis),
    Pedidos OMS (**máquina de 15 estados** + specs con foto etiquetada por campo),
    Logística Guías SRI (49 dígitos + etiquetas de bulto), Taller MES, BOM & MRP
  - Cliente & catálogo: Clientes CRM (búsqueda, segmentos, ficha, línea de tiempo, link PayPhone),
    Proveedores SRM, Cobros PayPhone, Productos PIM, Fototeca DAM (aprobación + subida + preview)
  - Finanzas & sistema: Contabilidad SRI (KPIs, flujo de caja, facturas 49 dígitos, CSV),
    Accesos de un uso (generador, revocar), Seguridad LOPDP, Ajustes & despliegue
    (stack, OVH zip/GitHub, flujo staging→main, `.gitignore` descargable)

### Diseño y datos
- `index.css`: sistema TALLER UNO — Geomanist (público) / Inter (dash), `font-display: swap`,
  paleta paper/ink/wine(#800000)/pine/oak/steel/brick/moss, modo oscuro `.dark`, animaciones
- `data.ts`: semilla completa + mapa `IMG` (9 fotos remotas; en producción: `public/img/`)
- Ruta: `#/dash` y `#/dash/login` → panel; `#/panel` alias interno

## 2. Comparativa con las 33 tasks de github.com/cadaidea/blthm (rama ac8f5)

| # | Task (fecha/hora EC) | Estado aquí |
|---|---|---|
| 1–6 | Snapshots iniciales del ERP | ✅ portado |
| 7 | "Tenemos algo ahora" (bletia/ legacy Laravel + .env + SQL) | ⛔ no se porta (contiene secretos; es el origen del porting) |
| 8–25 | Construcción de los 13 módulos | ✅ portado (14 pantallas) |
| 26 | Tipografía Geomanist/Inter + fondos oscuros + diseño | ✅ portado (index.css) |
| 27 | date-fns FP (solo commiteó node_modules) | ⛔ ruido — no aporta código de app |
| 28 | .gitignore de build artifacts | ✅ cubierto (botón .gitignore correcto en Ajustes) |
| 29 | **Validación cédula/RUC (Módulo 10/11) + consulta por documento + caché 5 min + modo offline + DOCUMENTACION_CEDULA_RUC.md** | ❌ **FALTA** (se había revertido) → se reimplementa |
| 30 | "Open Preview" + gitignore de Python | ⛔ artefacto del sandbox; gitignore Python incorrecto para Node |
| README | **Contabilidad: partida doble + Formulario 104** | ❌ **FALTA** → se agrega al módulo Contabilidad |

## 3. Decisiones de esta ronda (completadas ✅)
1. ✅ Reintegrada la task 29 (SRI): `utils/sri.ts` + Consulta por documento en CRM (caché 5 min, modo
   offline) + validación en alta de clientes y checkout + `DOCUMENTACION_CEDULA_RUC.md`.
2. ✅ Contabilidad completada: pestañas **Facturas SRI** (por defecto) / **Partida doble** (libro diario
   cuadrado Debe=Haber) / **Formulario 104** (borrador calculado).
3. ✅ Datos semilla con documentos matemáticamente válidos (coherentes con el validador).
4. ✅ Nada de lo documentado en la sección 1 se modificó (roles, auto-Gerencia, imágenes, 14 módulos intactos).

## 4. Ronda de refinamiento de marca (completada ✅)
1. ✅ **Fondo blanco real (#ffffff)** en tienda pública y contenido del dash (tokens `--color-paper`,
   `--color-card`). El modo oscuro del dash conserva su paleta propia. Grano atenuado.
2. ✅ **Eliminada la franja marquee** de materiales (se percibía genérica).
3. ✅ **Fuera el botón/enlaces "Panel interno"** de header, menú móvil y footer. El acceso del equipo
   queda solo como texto discreto `bletia.ec/dash` dentro del popup de cuenta.
4. ✅ **Header con 4 acciones funcionales**: búsqueda global (piezas + diario + secciones), cuenta
   (popup con nombre persistido), mis deseos (drawer con "al carrito") y carrito. Corazón en tarjetas
   y quick view. Todo persiste en localStorage.
5. ✅ **Menús del sitio editables desde el CMS** (columnas "Tienda" y "Empresa" del footer), con
   reordenar/añadir/quitar. Reemplazan el texto de versión en el copyright. Bloque legal con `#legal`.
6. ✅ **Carpeta `referencia/`** (temporal) para que el dueño suba archivos reales y se integren; se
   elimina al terminar. Fuentes Geomanist van en `public/fonts/`, no aquí.

## 5. Ronda de completitud vs. original Laravel (github.com/cadaidea/blthm-2/bletia) (completada ✅)
1. ✅ **Corregido bug de variantes**: las variantes apuntaban a ids `aura/nudo/vela` pero los productos
   son `p1–p7`. Ahora el selector de combinación SÍ se renderiza (Butaca Aura, Sofá Nudo, Silla Vela)
   con PVP distinto por tapiz/acabado y precio dinámico en la ficha.
2. ✅ **Blog avanzado** (categorías, etiquetas `#`, autores con nombre/cargo/bio, minutos de lectura):
   el Diario de la tienda filtra por categoría y muestra autor + cápsulas de etiquetas; el CMS gana
   columnas Autor/Lectura y el editor permite elegir autor y etiquetas.
3. ✅ **Newsletter → Marketing**: el suscriptor del footer se guarda con opt-in doble (nace "Pendiente")
   y aparece en el módulo Marketing · Digest con fuente "Footer web".
4. ✅ **Made to Order (MTO)**: campo `mto` en Mesa Raíz y Aparador Bruma; se muestra como fila de
   especificación y badge oscuro en la ficha.
5. ✅ Build verde (41 módulos). Todo lo anterior permanece intacto.
