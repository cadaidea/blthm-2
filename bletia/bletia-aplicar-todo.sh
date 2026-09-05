#!/usr/bin/env bash
set -u
cd ~/domains/bletia.ec/bletia || { echo "No existe el proyecto"; exit 1; }
PHP=/opt/alt/php84/usr/bin/lsphp
TS=$(date +%Y%m%d-%H%M%S)

echo ">> Backup en _bak-$TS.tgz"
tar czf "_bak-$TS.tgz" app/Filament resources/views public/css/tienda.css \
  app/Http/Controllers/TiendaController.php app/Models/Articulo.php app/Models/Categoria.php 2>/dev/null

echo ">> Extrayendo zips"
rm -rf _stage; mkdir -p _stage
unzip -o bletia-categorias-home-v1.0.0.zip   -d _stage/cat-home        >/dev/null
unzip -o bletia-home-blog-v1.0.0.zip          -d _stage/home-blog       >/dev/null
unzip -o bletia-cookies-v1.0.0.zip            -d _stage/cookies         >/dev/null
unzip -o bletia-articulo-header-v1.0.0.zip    -d _stage/articulo-header >/dev/null
unzip -o bletia-card-hover-v1.0.0.zip         -d _stage/card-hover      >/dev/null
unzip -o bletia-footer-v1.0.0.zip             -d _stage/footer          >/dev/null
unzip -o bletia-menu-lujo-v1.0.0.zip          -d _stage/menu-lujo       >/dev/null
unzip -o bletia-categorias-svg-v1.0.0.zip     -d _stage/svg             >/dev/null

echo ">> Copiando archivos"
for d in cat-home home-blog cookies articulo-header card-hover footer; do
  for t in app resources database; do
    [ -d "_stage/$d/$t" ] && cp -rf "_stage/$d/$t" ./
  done
done

echo ">> Patch Categoria (fillable imagen)"
grep -q "'imagen'" app/Models/Categoria.php || \
  sed -i "0,/protected \$fillable = \[/s//protected \$fillable = [\n        'imagen',/" app/Models/Categoria.php

echo ">> Patch Articulo (imagen_cabecera)"
grep -q "imagen_cabecera" app/Models/Articulo.php || {
  sed -i "s/'imagen', 'bloques',/'imagen', 'imagen_cabecera', 'bloques',/" app/Models/Articulo.php
  sed -i "s/'activo' => 'boolean',/'activo' => 'boolean', 'imagen_cabecera' => 'boolean',/" app/Models/Articulo.php
}

echo ">> Patch ArticuloResource (Toggle)"
grep -q "imagen_cabecera" app/Filament/Resources/ArticuloResource.php || {
  awk '1;/->imageEditor\(\)->columnSpanFull\(\),/{print "                Forms\\Components\\Toggle::make(\"imagen_cabecera\")->label(\"Usar la imagen como cabecera (hero a pantalla completa, header transparente)\")->default(false)->columnSpanFull(),"}' app/Filament/Resources/ArticuloResource.php > /tmp/ar.php && mv /tmp/ar.php app/Filament/Resources/ArticuloResource.php
}

echo ">> Patch TiendaController (posts)"
grep -q 'Articulo::publicado()' app/Http/Controllers/TiendaController.php || {
  awk '
  /\$posts = Articulo::where\(.activo./ {
    print "        $posts = Articulo::publicado()->with(\"categoria\")->orderByDesc(\"publicado_at\")->orderByDesc(\"id\")->take(6)->get();";
    if ($0 !~ /;/) skip=1; next }
  skip==1 { if ($0 ~ /;/) skip=0; next }
  { print }' app/Http/Controllers/TiendaController.php > /tmp/tc.php && mv /tmp/tc.php app/Http/Controllers/TiendaController.php
}

echo ">> Insertar posts en home.blade"
grep -q "tienda.partials.home-posts" resources/views/tienda/home.blade.php || \
  sed -i 's#@endsection#@include("tienda.partials.home-posts", ["posts" => $posts ?? collect()])\n@endsection#' resources/views/tienda/home.blade.php

echo ">> Insertar cookies antes de </body>"
for f in $(grep -rl '</body>' resources/views --include='*.blade.php' | grep -v '/filament/'); do
  grep -q "tienda.partials.cookies" "$f" || sed -i "s#</body>#    @include('tienda.partials.cookies')\n</body>#" "$f"
done

echo ">> SVG a storage"
mkdir -p storage/app/public/categorias
cp -f _stage/svg/*.svg storage/app/public/categorias/ 2>/dev/null
$PHP artisan storage:link 2>/dev/null
[ -e ../public_html/storage ] || ln -s ../bletia/storage/app/public ../public_html/storage

echo ">> Migraciones"
$PHP artisan migrate --force

echo ">> Append CSS"
app_css() { grep -q "$2" public/css/tienda.css || cat "$1" >> public/css/tienda.css; }
app_css _stage/cat-home/assets/tienda-home-bloques.css 't-home-bloques'
app_css _stage/home-blog/assets/home-posts.css         't-home-posts'
app_css _stage/articulo-header/assets/articulo-hero.css 't-art-hero'
app_css _stage/menu-lujo/menu-lujo.css                  'bletia-menu-lujo'
app_css _stage/card-hover/assets/card-hover.css         'bletia-card-hover'
app_css _stage/footer/assets/footer.css                 'bletia-footer v1.0.0'

echo ">> Copiar a public_html"
cp public/css/* ../public_html/css/ 2>/dev/null

echo ">> Limpiar caché"
find storage/framework/views -name "*.php" -delete
$PHP artisan optimize:clear
$PHP artisan view:clear

rm -rf _stage
echo "=== LISTO. Aplicados #2..#8. Backup: _bak-$TS.tgz ==="
