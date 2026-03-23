#!/bin/bash
# Script de sincronización FTP para SGV
# Uso: ./sync_ftp.sh [upload|download|sync]

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configuración FTP
FTP_HOST="ftp.digitalcity.cl"
FTP_USER="digital2"
FTP_PASS="Dcity2020...,"
FTP_REMOTE_PATH="/sgv"
LOCAL_PATH="/Applications/XAMPP/xamppfiles/htdocs/sgv"

# Archivos y carpetas a excluir
EXCLUDE_LIST=(
    ".git"
    ".vscode"
    "*.md"
    "*.log"
    ".DS_Store"
    "Thumbs.db"
    "node_modules"
    "ARCHIVOS_NO_UTILIZADOS.md"
    "RESUMEN_PERMISOS.md"
    "CAMBIOS_FINALES.md"
    "SINCRONIZACION_FTP.md"
)

echo -e "${GREEN}🚀 Sincronización FTP - SGV${NC}"
echo "=================================="

# Verificar si lftp está instalado
if ! command -v lftp &> /dev/null; then
    echo -e "${YELLOW}⚠️  lftp no está instalado.${NC}"
    echo "Instálalo con: brew install lftp (Mac) o apt-get install lftp (Linux)"
    exit 1
fi

# Función para construir comando de exclusión
build_exclude() {
    local exclude_cmd=""
    for pattern in "${EXCLUDE_LIST[@]}"; do
        exclude_cmd="$exclude_cmd --exclude-glob=\"$pattern\""
    done
    echo "$exclude_cmd"
}

# Función para subir archivos
upload_files() {
    echo -e "${GREEN}📤 Subiendo archivos al servidor...${NC}"
    EXCLUDE_CMD=$(build_exclude)
    
    lftp -u "$FTP_USER","$FTP_PASS" "$FTP_HOST" <<EOF
set ftp:list-options -a
cd $FTP_REMOTE_PATH
lcd $LOCAL_PATH
mirror -R --delete --verbose \
    --exclude-glob=".git/*" \
    --exclude-glob=".vscode/*" \
    --exclude-glob="*.md" \
    --exclude-glob="*.log" \
    --exclude-glob=".DS_Store" \
    --exclude-glob="Thumbs.db" \
    --exclude-glob="node_modules/*"
quit
EOF
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Archivos subidos exitosamente${NC}"
    else
        echo -e "${RED}❌ Error al subir archivos${NC}"
        exit 1
    fi
}

# Función para descargar archivos
download_files() {
    echo -e "${GREEN}📥 Descargando archivos del servidor...${NC}"
    
    lftp -u "$FTP_USER","$FTP_PASS" "$FTP_HOST" <<EOF
set ftp:list-options -a
cd $FTP_REMOTE_PATH
lcd $LOCAL_PATH
mirror --delete --verbose
quit
EOF
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Archivos descargados exitosamente${NC}"
    else
        echo -e "${RED}❌ Error al descargar archivos${NC}"
        exit 1
    fi
}

# Función para sincronizar (bidireccional - solo muestra diferencias)
sync_files() {
    echo -e "${YELLOW}🔄 Verificando diferencias...${NC}"
    echo "Usa 'upload' para subir o 'download' para descargar"
}

# Procesar argumentos
case "$1" in
    upload)
        upload_files
        ;;
    download)
        download_files
        ;;
    sync)
        sync_files
        ;;
    *)
        echo "Uso: $0 {upload|download|sync}"
        echo ""
        echo "  upload   - Sube archivos locales al servidor"
        echo "  download - Descarga archivos del servidor"
        echo "  sync     - Muestra información de sincronización"
        exit 1
        ;;
esac



