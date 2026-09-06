# Fuentes Geomanist — BLETIA

Coloca aquí tus archivos licenciados con **exactamente** estos nombres:

| Archivo          | Cubre (font-weight) | Dónde se usa                     |
|------------------|---------------------|----------------------------------|
| `Geomanist-Regular.woff2` | 300 – 400   | Textos públicos, párrafos        |
| `Geomanist-Medium.woff2`  | 400 – 500   | Títulos de tienda, navegación    |
| `Geomanist-Bold.woff2`    | 500 – 700   | Hero, encabezados fuertes        |

## Reglas

1. **Solo .woff2** (si tienes .otf/.ttf, conviértelos con fonttools/transfonter).
2. Al estar aquí, Vite los copia solos a `dist/fonts/` en cada `npm run build`:
   viajan dentro del archivo oficial sin hacer nada más.
3. **No los subas al repo público de GitHub** (son licenciados); van solo en el
   proyecto que compilas y en el VPS.
4. Mientras no estén, el sitio no se rompe: usa el respaldo (Outfit/Inter) gracias
   a `font-display: swap`.
5. El panel (dash) usa **Inter de Google** — no necesita archivos.
