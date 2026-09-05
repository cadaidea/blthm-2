# 📁 Carpeta de referencia (TEMPORAL)

Deja aquí los archivos de los que quieras que aprenda / copie para integrarlos al
proyecto BLETIA. Cuando terminemos, **esta carpeta se elimina** (no forma parte del build).

## Qué poner aquí

- **Textos reales**: descripciones de productos, historia de la marca, políticas de
  garantía/envío, textos del diario de taller.
- **Listas de precios** (CSV / Excel / TXT) para sincronizar el PIM.
- **Logos y guías de marca** (SVG / PNG) que quieras usar.
- **Fotos reales de tus muebles** (JPG/PNG) para reemplazar las generadas.
- **Documentos** (facturas de ejemplo, formatos SRI, manuales) que deba replicar.
- **Código o snippets** de otro proyecto que quieras portar.

## Qué NO poner aquí

- ❌ **Tus fuentes Geomanest** (`.woff2`, `.otf`): esas van directo en `public/fonts/`
  con los nombres `Geomanist-Regular.woff2`, `Geomanist-Medium.woff2`, `Geomanist-Bold.woff2`.
- ❌ **Secretos**: claves de PayPhone, firma electrónica SRI (`.p12`), contraseñas,
  archivos `.env`. Eso nunca entra al repositorio.

## ⚠️ Dónde vive esta carpeta

Esta carpeta está en el **workspace del proyecto** (donde se edita el código), **NO** en tu
repo `cadaidea/blthm` de tu PC. Son lugares distintos: tú no puedes soltar archivos aquí
directamente. Para que yo los lea, el intercambio se hace por **tu repo de GitHub (público)**.

## Cómo funciona (flujo real)

1. **En tu PC**, dentro de tu repo `blthm`, crea una carpeta `referencia/` y copia ahí tus archivos:
   ```bash
   cd ruta/a/tu/repo/blthm
   mkdir referencia
   # copia tus archivos dentro de referencia/
   git add referencia/
   git commit -m "referencia: archivos reales para integrar"
   git push
   ```
2. Dime: *"revisa la carpeta referencia de mi repo"*.
3. Los leo desde GitHub, te digo **cuáles voy a usar y cómo**, y los integro al proyecto.
4. Al final, borramos la carpeta del repo: `git rm -r referencia/ && git commit -m "limpieza" && git push`.
