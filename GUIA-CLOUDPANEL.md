# 🚀 Guía Rápida: BLETIA en CloudPanel

## 📍 Tu Configuración

- **VPS IP**: 54.39.21.79
- **Usuario CloudPanel**: bletiaec
- **Ruta del sitio**: `/home/bletiaec/htdocs/www.bletia.ec/`
- **Dominio**: bletia.ec

## 🎯 Instalación Completa (Paso a Paso)

### PASO 1: Subir archivos al VPS

**En tu PC:**
```bash
# Subir todos los archivos al VPS
./upload-to-vps.sh
```

### PASO 2: Conectarse al VPS

```bash
ssh root@54.39.21.79
```

### PASO 3: Instalar el sistema

```bash
cd /opt/bletia
chmod +x *.sh
./install-vps.sh
```

Este script instala:
- PostgreSQL 15
- Node.js 20 LTS
- PM2 (gestor de procesos)
- Firewall (UFW)

### PASO 4: Configurar la aplicación

```bash
./setup-app.sh
```

Este script:
- Instala dependencias del backend y frontend
- Crea la estructura de base de datos
- Genera datos iniciales (admin, productos de ejemplo)
- Compila el frontend

### PASO 5: Copiar a CloudPanel

```bash
./copy-to-cloudpanel.sh
```

Este script:
- Copia el frontend compilado a `/home/bletiaec/htdocs/www.bletia.ec/`
- Establece los permisos correctos para el usuario `bletiaec`

### PASO 6: Configurar el proxy en CloudPanel

**En CloudPanel (interfaz web):**

1. Ve a **Sites** → **bletia.ec** → **Config**
2. Busca la sección **Nginx** o **Vhost Config**
3. Agrega esta configuración **ANTES** del bloque `location /`:

```nginx
# Backend API
location /api {
    proxy_pass http://127.0.0.1:3000;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection 'upgrade';
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_cache_bypass $http_upgrade;
}

# Health check
location /health {
    proxy_pass http://127.0.0.1:3000/health;
}
```

4. **Guarda los cambios**

### PASO 7: Iniciar el backend

```bash
cd /opt/bletia/backend
pm2 start npm --name bletia-backend -- start
pm2 save
pm2 startup
```

### PASO 8: Activar SSL en CloudPanel

1. En CloudPanel, ve a **Sites** → **bletia.ec** → **SSL/TLS**
2. Clic en **Let's Encrypt**
3. Selecciona `bletia.ec` y `www.bletia.ec`
4. Clic en **Install**

## ✅ Verificación

Abre tu navegador:
- **Tienda**: https://bletia.ec
- **Panel**: https://bletia.ec/#/dash

Credenciales:
- **Email**: admin@bletia.ec
- **Password**: admin123

⚠️ **Cambia la contraseña inmediatamente!**

## 🔄 Actualizaciones Futuras

### Método Rápido (Recomendado)

**En tu PC:**
```bash
./upload-to-vps.sh
```

**En el VPS:**
```bash
cd /opt/bletia
./update.sh
./copy-to-cloudpanel.sh
```

### Método Manual

**En tu PC:**
```bash
# Subir archivos
./upload-to-vps.sh
```

**En el VPS:**
```bash
# 1. Conectarse
ssh root@54.39.21.79

# 2. Ir al directorio
cd /opt/bletia

# 3. Detener backend
pm2 stop bletia-backend

# 4. Actualizar backend
cd backend
npm install
npx prisma generate
npx prisma db push

# 5. Actualizar frontend
cd ..
npm install
npm run build

# 6. Copiar a CloudPanel
./copy-to-cloudpanel.sh

# 7. Reiniciar backend
cd backend
pm2 restart bletia-backend
```

## 🛠️ Comandos Útiles

### Controlar el backend

```bash
# Ver estado
pm2 status

# Ver logs en tiempo real
pm2 logs bletia-backend

# Reiniciar
pm2 restart bletia-backend

# Detener
pm2 stop bletia-backend

# Iniciar
pm2 start bletia-backend
```

### Controlar la aplicación

```bash
# Usando el script
./bletia-ctl.sh start
./bletia-ctl.sh stop
./bletia-ctl.sh restart
./bletia-ctl.sh status
./bletia-ctl.sh logs
```

### Base de datos

```bash
# Conectarse a PostgreSQL
sudo -u postgres psql -U bletia -d bletia_db

# Backup manual
pg_dump -U bletia bletia_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Restaurar backup
psql -U bletia bletia_db < backup_20240120_120000.sql
```

### Logs

```bash
# Backend
pm2 logs bletia-backend

# Nginx (CloudPanel)
tail -f /home/bletiaec/logs/bletia.ec/error.log

# PostgreSQL
tail -f /var/log/postgresql/postgresql-15-main.log
```

## 🆘 Solución de Problemas

### El sitio no carga

```bash
# Verificar que el backend está corriendo
pm2 status

# Verificar que PostgreSQL está corriendo
systemctl status postgresql

# Verificar logs de Nginx
tail -50 /home/bletiaec/logs/bletia.ec/error.log
```

### Error 502 Bad Gateway

```bash
# Reiniciar backend
pm2 restart bletia-backend

# Verificar que el backend responde
curl http://localhost:3000/health

# Verificar configuración de proxy en CloudPanel
```

### Error de conexión a base de datos

```bash
# Verificar credenciales
cat /opt/bletia/backend/.env | grep DATABASE_URL

# Probar conexión
psql -U bletia -d bletia_db -h localhost
```

### Los cambios no se ven en la web

```bash
# Asegúrate de copiar a CloudPanel
./copy-to-cloudpanel.sh

# Limpiar caché del navegador (Ctrl+Shift+R)
```

## 📊 Backups Automáticos

Crea un cron job para backups diarios:

```bash
crontab -e
```

Agrega esta línea:
```
0 2 * * * /opt/bletia/backup.sh
```

## 🔐 Seguridad

### Cambiar contraseña de la base de datos

```bash
# Cambiar en PostgreSQL
sudo -u postgres psql
ALTER USER bletia WITH PASSWORD 'nueva_contraseña_segura';
\q

# Actualizar en .env
nano /opt/bletia/backend/.env
# Cambiar DATABASE_URL con la nueva contraseña

# Reiniciar backend
pm2 restart bletia-backend
```

### Cambiar contraseña del administrador

1. Inicia sesión en https://bletia.ec/#/dash
2. Ve a RRHH → tu usuario
3. Cambia la contraseña

## 📝 Notas Importantes

1. **Ruta correcta**: `/home/bletiaec/htdocs/www.bletia.ec/`
2. **Usuario CloudPanel**: `bletiaec`
3. **Siempre ejecuta** `./copy-to-cloudpanel.sh` después de compilar
4. **Backup antes de actualizar**: El script `update.sh` lo hace automáticamente
5. **No edites archivos en** `/home/bletiaec/htdocs/www.bletia.ec/` directamente
6. **Edita en** `/opt/bletia/` y luego copia con `./copy-to-cloudpanel.sh`

## 🎉 ¡Listo!

Tu tienda BLETIA está ahora en producción con CloudPanel.

### Checklist Final

- [ ] Cambiar contraseña del administrador
- [ ] Configurar backups automáticos
- [ ] Verificar que SSL está activo
- [ ] Probar el flujo completo (crear producto, crear cliente, crear pedido)
- [ ] Configurar monitoreo de logs

¡Éxito con tu tienda! 🛍️
