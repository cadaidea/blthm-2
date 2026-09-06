# BLETIA · Guía de despliegue en OVH + CloudPanel

Sistema de despliegue **sin caída, con rollback y sin pérdida de datos**.
VPS: `vps-9cb251e8` · `54.39.21.79` · Ubuntu · 2 núcleos / 4 GB.

---

## Arquitectura

```
/home/ubuntu/bletia/
├── repo/               ← clon de GitHub (rama web)
├── releases/           ← cada despliegue = copia compilada con fecha
│   ├── 20260220-1030/
│   └── 20260221-0900/
├── shared/fonts/       ← tus Geomanest .woff2 (fuera del repo, por licencia)
├── backups/            ← respaldo automático ANTES de cada despliegue
├── current             ← enlace a la versión en línea
└── deploy.conf         ← DOC_ROOT (htdocs de CloudPanel) + rama + retención
```

CloudPanel (nginx) sirve la carpeta `htdocs` que tú indiques en `deploy.conf`.
El script compila y **sincroniza** ese release hacia `htdocs`. Nunca toca
`shared/`, `backups/` ni tus datos: solo reemplaza código.

---

## FASE 0 · Publicar el proyecto en GitHub (ya lo hiciste ✅)

El proyecto se publica en **`cadaidea/blthm-2`** (PR #1), en la rama **`web`**.
Esa rama queda separada de `main` (tu código Laravel original), para no mezclarlos.

```bash
git init
git checkout -b web
git add .
git commit -m "feat: BLETIA web v1.0.0 — tienda + panel"
git remote add origin https://github.com/cadaidea/blthm-2.git
git push -u origin web
```

> Producción lee la rama configurada en `deploy.conf` (por defecto `web`).
> El PR #1 es tu punto de publicación: lo que mergees a `web` es lo que se despliega.

---

## FASE 1 · Preparar el VPS (una sola vez)

Entra por SSH como siempre: `ssh ubuntu@54.39.21.79`

```bash
# Sube la carpeta deploy/ del proyecto al VPS (desde tu PC):
scp -r deploy ubuntu@54.39.21.79:/home/ubuntu/bletia-deploy
# En el VPS:
mkdir -p /home/ubuntu/bletia && cp -r /home/ubuntu/bletia-deploy/*.sh /home/ubuntu/bletia/
cd /home/ubuntu/bletia
bash provision.sh
```

`provision.sh` instala Node 22, clona el repo (rama `web`) y crea la estructura.

**Después, a mano (2 cosas):**

1. Edita `/home/ubuntu/bletia/deploy.conf` y pon en `DOC_ROOT` la ruta que
   CloudPanel muestra en **tu sitio → Settings → Document Root**
   (ej. `/home/ubuntu/htdocs`).
2. Copia tus 3 fuentes a `/home/ubuntu/bletia/shared/fonts/`:
   `Geomanist-Regular.woff2`, `Geomanist-Medium.woff2`, `Geomanist-Bold.woff2`.
   (Van ahí, NO al repo: están licenciadas.)

---

## FASE 2 · Primer despliegue

```bash
cd /home/ubuntu/bletia
bash deploy.sh
```

Esto: respalda lo que haya en línea → trae la rama `web` → compila → inyecta
las fuentes → publica en `htdocs` → conserva los últimos 5 releases.

---

## FASE 3 · Dominio + HTTPS en CloudPanel

1. **Crear el sitio:** CloudPanel → *Sites → Add Site* → tarjeta **Static Site**
   (NO "PHP Site", que es la que da el error de Laravel) → dominio `bletia.ec`.
2. **DNS:** en tu registrador, registro **A** de `bletia.ec` → `54.39.21.79`.
3. **HTTPS:** CloudPanel → tu sitio → *SSL → Let's Encrypt → Issue and Install*.
4. Verifica: `https://bletia.ec` y `https://bletia.ec/#/dash`.

> Opcional (recomendado): añade en CloudPanel → *Vhost* las directivas de
> `nginx-bletia.conf` (gzip, caché y cabeceras de seguridad).

---

## FASE 4 · Mejoras futuras (sin caída ni pérdida de datos)

Cada vez que quieras publicar una mejora:

```bash
# 1) En tu PC, dentro del proyecto (o mergea el PR a la rama web en GitHub):
git add . && git commit -m "feat: mi mejora"
git push origin web

# 2) En el VPS (jala directo de GitHub, compila y publica):
ssh ubuntu@54.39.21.79
cd /home/ubuntu/bletia && bash deploy.sh
```

**Probar un PR antes de mergear** (sin tocar producción estable):

```bash
# En el VPS: publica el PR #1 tal cual está, para revisarlo
bash deploy.sh origin/pr/1/head
# Si no te convence, vuelves al release anterior:
bash rollback.sh
```

- **Sin caída:** nginx sigue sirviendo mientras se compila; la publicación es
  un `rsync` atómico al final.
- **Sin pérdida de datos:** el script jamás toca `shared/`, `backups/` ni nada
  fuera del código. Antes de publicar, respalda automáticamente lo que está en
  línea en `backups/pre-<fecha>`.
- **Rollback en 10 s:** si algo sale mal, `bash rollback.sh` y vuelve al
  release anterior al instante.

Para desplegar un **tag** específico: `bash deploy.sh v1.2.0`.

---

## ⚠️ Sobre los datos cargados (Fase 1 del producto)

En la v1.0.0, lo que cargas en el **panel** (productos, blog, secciones,
clientes…) se guarda en el navegador (localStorage) y se protege con el botón
**Respaldo & Restauración** del panel. Los **archivos** (fotos, fuentes) viven
en el VPS y sobreviven a todo despliegue.

Cuando quieras que los datos vivan en el servidor (compartidos entre
colaboradores, respaldos automáticos diarios), montamos la **Fase 2**:
API + PostgreSQL en este mismo VPS. Los scripts ya dejan la estructura lista.
