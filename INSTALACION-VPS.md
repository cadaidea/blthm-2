# 🚀 BLETIA - Guía de Instalación en VPS OVH Cloud

## 📋 Requisitos Previos

- VPS OVH Cloud con Ubuntu 22.04 o superior
- Acceso root al servidor
- Dominio bletia.ec apuntando a la IP del VPS (54.39.21.79)
- CloudPanel instalado (opcional, pero recomendado)

## 🔧 Instalación Paso a Paso

### PASO 1: Subir archivos al VPS

Desde tu PC, sube todos los archivos del proyecto al VPS:

```bash
# Opción 1: Usar SCP (recomendado)
scp -r ./* root@54.39.21.79:/opt/bletia/

# Opción 2: Usar FileZilla o WinSCP
# Conecta a: 54.39.21.79
# Usuario: root
# Directorio destino: /opt/bletia/
```

### PASO 2: Hacer ejecutables los scripts

```bash
ssh root@54.39.21.79
cd /opt/bletia
chmod +x *.sh
```

### PASO 3: Ejecutar instalación del sistema

```bash
bash install-vps.sh
```

Este script instalará:
- PostgreSQL 15
- Node.js 20 LTS
- PM2 (gestor de procesos)
- Nginx
- Certbot (SSL)
- Firewall (UFW)

### PASO 4: Configurar la aplicación

```bash
bash setup-app.sh
```

Este script:
- Instala dependencias del backend y frontend
- Crea la estructura de base de datos
- Genera datos iniciales (admin, productos de ejemplo)
- Compila el frontend

### PASO 5: Configurar Nginx

```bash
bash setup-nginx.sh
```

Este script:
- Crea la configuración de Nginx
- Configura el proxy para el backend
- Reinicia Nginx

### PASO 6: Iniciar la aplicación

```bash
bash bletia-ctl.sh start
```

### PASO 7: Activar SSL (HTTPS)

```bash
certbot --nginx -d bletia.ec -d www.bletia.ec
```

Sigue las instrucciones de Certbot para activar el certificado SSL gratuito.

## ✅ Verificación

Abre tu navegador y visita:
- **Tienda**: https://bletia.ec
- **Panel**: https://bletia.ec/#/dash

Credenciales de administrador:
- **Email**: admin@bletia.ec
- **Password**: admin123

⚠️ **IMPORTANTE**: Cambia la contraseña inmediatamente después del primer login.

## 🔧 Comandos Útiles

### Controlar la aplicación

```bash
# Iniciar
bash bletia-ctl.sh start

# Detener
bash bletia-ctl.sh stop

# Reiniciar
bash bletia-ctl.sh restart

# Ver estado
bash bletia-ctl.sh status

# Ver logs en tiempo real
bash bletia-ctl.sh logs
```

### Actualizar la aplicación

Cuando haya una nueva versión:

```bash
# 1. Sube los nuevos archivos al VPS
scp -r ./* root@54.39.21.79:/opt/bletia/

# 2. Ejecuta el script de actualización
ssh root@54.39.21.79
cd /opt/bletia
bash update.sh
```

El script de actualización:
- Crea backup automático de la base de datos
- Detiene la aplicación
- Actualiza dependencias
- Aplica migraciones de base de datos
- Compila el frontend
- Reinicia la aplicación

### Ver logs

```bash
# Logs del backend
pm2 logs bletia-backend

# Logs de Nginx
tail -f /var/log/nginx/error.log

# Logs de PostgreSQL
tail -f /var/log/postgresql/postgresql-15-main.log
```

### Backup manual

```bash
# Backup de la base de datos
pg_dump -U bletia bletia_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Restaurar backup
psql -U bletia bletia_db < backup_20240120_120000.sql
```

## 🛡️ Seguridad

### Firewall

El firewall ya está configurado para permitir:
- Puerto 22 (SSH)
- Puerto 80 (HTTP)
- Puerto 443 (HTTPS)
- Puerto 3000 (Backend - solo localhost)

### Cambiar contraseñas

**Base de datos:**
```bash
sudo -u postgres psql
ALTER USER bletia WITH PASSWORD 'nueva_contraseña_segura';
\q

# Actualizar en /opt/bletia/backend/.env
nano /opt/bletia/backend/.env
# Cambiar DATABASE_URL con la nueva contraseña

# Reiniciar backend
bash bletia-ctl.sh restart
```

**Administrador:**
- Inicia sesión en https://bletia.ec/#/dash
- Ve a RRHH → tu usuario
- Cambia la contraseña

## 📊 Monitoreo

### Verificar que todo está funcionando

```bash
# Estado de la aplicación
bash bletia-ctl.sh status

# Verificar que el backend responde
curl http://localhost:3000/health

# Verificar que PostgreSQL está corriendo
systemctl status postgresql

# Verificar que Nginx está corriendo
systemctl status nginx
```

### Backups automáticos

Crea un cron job para backups diarios:

```bash
crontab -e
```

Agrega esta línea:
```
0 2 * * * /opt/bletia/backup.sh
```

Crea el script de backup:
```bash
nano /opt/bletia/backup.sh
```

Contenido:
```bash
#!/bin/bash
BACKUP_DIR="/root/backups"
mkdir -p $BACKUP_DIR
pg_dump -U bletia bletia_db > $BACKUP_DIR/bletia_db_$(date +%Y%m%d).sql
find $BACKUP_DIR -name "bletia_db_*.sql" -mtime +30 -delete
```

Hazlo ejecutable:
```bash
chmod +x /opt/bletia/backup.sh
```

## 🆘 Solución de Problemas

### El sitio no carga

```bash
# Verificar que Nginx está corriendo
systemctl status nginx

# Verificar logs de Nginx
tail -50 /var/log/nginx/error.log

# Verificar que el backend está corriendo
bash bletia-ctl.sh status
```

### Error de conexión a base de datos

```bash
# Verificar que PostgreSQL está corriendo
systemctl status postgresql

# Verificar credenciales
cat /opt/bletia/backend/.env | grep DATABASE_URL

# Probar conexión
psql -U bletia -d bletia_db -h localhost
```

### El backend no responde

```bash
# Reiniciar backend
bash bletia-ctl.sh restart

# Ver logs
bash bletia-ctl.sh logs

# Verificar puerto 3000
netstat -tlnp | grep 3000
```

### Error 502 Bad Gateway

```bash
# Verificar que el backend está corriendo
bash bletia-ctl.sh status

# Si no está corriendo, iniciarlo
bash bletia-ctl.sh start

# Verificar configuración de Nginx
nginx -t
systemctl restart nginx
```

## 📝 Notas Importantes

1. **No uses GitHub**: Este proyecto está configurado para funcionar sin GitHub. Todos los archivos se suben directamente al VPS.

2. **Backups**: Los backups automáticos se guardan en `/root/backups/`. Revisa regularmente que se estén creando.

3. **Actualizaciones**: Cuando subas una nueva versión, siempre usa `update.sh` en lugar de reiniciar manualmente.

4. **Seguridad**: Cambia las contraseñas por defecto inmediatamente después de la instalación.

5. **Monitoreo**: Revisa los logs regularmente para detectar problemas temprano.

## 📞 Soporte

Si tienes problemas:
1. Revisa los logs: `bash bletia-ctl.sh logs`
2. Verifica el estado: `bash bletia-ctl.sh status`
3. Consulta la sección de solución de problemas arriba

## 🎉 ¡Listo!

Tu tienda BLETIA está ahora en producción. Recuerda:
- Cambiar la contraseña del administrador
- Configurar backups automáticos
- Monitorear regularmente los logs
- Mantener el sistema actualizado

¡Éxito con tu tienda! 🛍️
