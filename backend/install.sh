#!/bin/bash
echo "🔧 Instalando sistema de cine..."

# Verificar PHP
php -v | grep "PHP 8.3" || { echo "❌ Se requiere PHP 8.3"; exit 1; }

# Instalar dependencias
composer install

# Crear directorios necesarios
mkdir -p storage/uploads/posters
mkdir -p storage/uploads/payments
mkdir -p storage/qrcodes
mkdir -p logs

# Permisos
chmod 755 storage storage/uploads storage/uploads/posters storage/uploads/payments storage/qrcodes logs
chmod 644 .env

echo "✅ Instalación completada"
echo "📝 No olvides:"
echo "   - Configurar la base de datos en .env"
echo "   - Ejecutar el script SQL de la estructura"
echo "   - Configurar Nginx"
