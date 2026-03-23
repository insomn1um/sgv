#!/bin/bash
# Script para sincronizar solo los cambios del sidebar pin
# Uso: ./sync_sidebar_changes.sh

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Configuración FTP
FTP_HOST="ftp.digitalcity.cl"
FTP_USER="digital2"
FTP_PASS="Dcity2020...,"
FTP_REMOTE_PATH="/sgv"

echo -e "${GREEN}🚀 Sincronizando cambios del Sidebar Pin${NC}"
echo "=========================================="

# Verificar si lftp está instalado
if ! command -v lftp &> /dev/null; then
    echo -e "${YELLOW}⚠️  lftp no está instalado.${NC}"
    echo "Instálalo con: brew install lftp (Mac)"
    exit 1
fi

# Lista de archivos a subir
FILES=(
    "js/sidebar-pin.js"
    "includes/sidebar.css"
    "dashboard.php"
    "visitas.php"
    "trabajadores.php"
    "ver_trabajador.php"
    "nueva_visita.php"
    "usuarios.php"
    "auditoria.php"
    "crear_usuario.php"
    "editar_empresa.php"
    "editar_usuario.php"
    "empresas.php"
    "gestion_empresas.php"
    "ingreso_qr.php"
    "nueva_empresa.php"
    "nuevo_trabajador.php"
    "reportes.php"
    "ver_empresa.php"
    "ver_visita.php"
)

# Verificar que los archivos existan localmente
echo -e "${YELLOW}📋 Verificando archivos locales...${NC}"
MISSING_FILES=()
for file in "${FILES[@]}"; do
    if [ ! -f "$file" ]; then
        MISSING_FILES+=("$file")
    fi
done

if [ ${#MISSING_FILES[@]} -gt 0 ]; then
    echo -e "${RED}❌ Archivos faltantes:${NC}"
    for file in "${MISSING_FILES[@]}"; do
        echo "  - $file"
    done
    exit 1
fi

echo -e "${GREEN}✅ Todos los archivos encontrados${NC}"
echo ""

# Construir comandos FTP
echo -e "${YELLOW}📤 Subiendo archivos...${NC}"

lftp -u "$FTP_USER","$FTP_PASS" "$FTP_HOST" <<EOF
set ftp:list-options -a
cd $FTP_REMOTE_PATH
$(for file in "${FILES[@]}"; do
    echo "put $file"
done)
quit
EOF

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✅ Archivos subidos exitosamente${NC}"
    echo ""
    echo "📋 Archivos sincronizados:"
    for file in "${FILES[@]}"; do
        echo "  ✓ $file"
    done
    echo ""
    echo -e "${YELLOW}🔍 Verifica en: http://sgv.digitalcity.cl/sgv/dashboard.php${NC}"
else
    echo -e "${RED}❌ Error al subir archivos${NC}"
    exit 1
fi

