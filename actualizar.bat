@echo off
chcp 65001 >nul
title BLETIA · Actualizar web
cd /d "%~dp0"

echo.
echo  ======================================================
echo   BLETIA · Publicar cambios en GitHub (paso 1 de 2)
echo  ======================================================
echo.

git add -A
git commit -m "Actualizacion BLETIA" >nul 2>&1
if errorlevel 1 (
  echo   - No hay cambios nuevos que subir.
) else (
  echo   - Cambios guardados. Subiendo a GitHub...
)

git push origin main
if errorlevel 1 (
  echo.
  echo   ERROR: no se pudo subir. Revisa tu conexion o GitHub Desktop.
  pause
  exit /b 1
)

echo.
echo   LISTO. Ya esta en GitHub.
echo.
echo   Ahora entra por SSH a tu VPS y ejecuta:
echo.
echo       cd ~/bletia ^&^& bash repo/deploy/deploy.sh
echo.
pause
