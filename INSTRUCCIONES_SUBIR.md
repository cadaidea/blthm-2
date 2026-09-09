# Instrucciones para subir cambios a producción

## Problema resuelto
La tienda ahora muestra productos de fallback cuando la API no está disponible.

## Pasos para subir los cambios

### 1. Descargar los archivos compilados
Los archivos compilados están en la carpeta `dist/`:
- `dist/index.html`
- `dist/assets/index-K2T-B7hS.css`
- `dist/assets/index-DL5Xwkbe.js`

### 2. Subir al servidor
Sube estos archivos a tu servidor en `/home/bletiaec/htdocs/www.bletia.ec/`:

```bash
# Desde tu PC, sube los archivos
scp dist/index.html root@54.39.21.79:/home/bletiaec/htdocs/www.bletia.ec/
scp dist/assets/* root@54.39.21.79:/home/bletiaec/htdocs/www.bletia.ec/assets/
```

### 3. Recargar Nginx
```bash
sudo systemctl reload nginx
```

### 4. Verificar
Abre https://bletia.ec en una ventana de incógnito (Ctrl+Shift+N) para ver los cambios sin caché.

Deberías ver los productos de fallback (Butaca Aura, Sofá Nudo, Mesa Raíz, etc.) aunque la API no esté corriendo.

## Próximos pasos

Cuando el backend esté corriendo en el servidor:
1. Configura la variable `VITE_API_URL` en el archivo `.env` del servidor
2. Recompila con `npm run build`
3. Sube los archivos nuevamente
4. La tienda usará los productos de la base de datos PostgreSQL

## Verificación

Abre https://bletia.ec y verifica:
- ✅ Se muestran los productos
- ✅ Se pueden agregar al carrito
- ✅ Se pueden agregar a deseos
- ✅ Se puede hacer checkout

Si ves los productos, ¡todo está funcionando correctamente!
