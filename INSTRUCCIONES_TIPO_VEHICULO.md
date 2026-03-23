# Agregar Campo Tipo de Vehículo

## Descripción
Esta actualización agrega un nuevo campo "Tipo de Vehículo" al formulario de nueva visita, permitiendo registrar el tipo de vehículo con el que ingresa el visitante (auto, camioneta, camión, moto, etc.).

## Cambios Realizados

### 1. Base de Datos
- **Archivo**: `migracion_agregar_tipo_vehiculo.sql`
- **Cambio**: Agrega la columna `tipo_vehiculo` a la tabla `visitas`

### 2. Archivos PHP Actualizados
- `nueva_visita.php` - Formulario principal de nueva visita
- `nueva_visita_optimizada.php` - Formulario optimizado de nueva visita
- `classes/Visita.php` - Clase de modelo de visitas

### 3. Formulario
Se agregó un nuevo campo de selección (dropdown) con las siguientes opciones:
- Auto
- Camioneta
- Camión
- Moto
- Furgón
- Van
- Otro

## Instrucciones de Instalación

### Paso 1: Ejecutar la Migración SQL

#### Opción A: Desde phpMyAdmin
1. Abrir phpMyAdmin en el navegador: http://localhost/phpmyadmin
2. Seleccionar la base de datos `sgv`
3. Ir a la pestaña "SQL"
4. Copiar y pegar el contenido del archivo `migracion_agregar_tipo_vehiculo.sql`
5. Hacer clic en "Continuar" para ejecutar

#### Opción B: Desde la línea de comandos
```bash
# Desde el directorio del proyecto
mysql -u root -p sgv < migracion_agregar_tipo_vehiculo.sql
```

#### Opción C: Desde la terminal de MySQL
```bash
# Conectarse a MySQL
mysql -u root -p

# Seleccionar la base de datos
USE sgv;

# Ejecutar el ALTER TABLE
ALTER TABLE visitas 
ADD COLUMN tipo_vehiculo VARCHAR(50) NULL COMMENT 'Tipo de vehículo (auto, camioneta, camión, moto, etc.)' AFTER patente;

# Verificar
DESCRIBE visitas;
```

### Paso 2: Verificar los Cambios
Después de ejecutar la migración, puedes verificar que la columna se agregó correctamente:

```sql
DESCRIBE visitas;
```

Deberías ver la nueva columna `tipo_vehiculo` después de la columna `patente`.

### Paso 3: Probar el Formulario
1. Acceder al sistema SGV
2. Ir a "Visitas" > "Nueva Visita"
3. Completar el formulario
4. Verificar que el campo "Tipo de Vehículo" aparece junto al campo "Patente del Vehículo"
5. Seleccionar un tipo de vehículo y guardar la visita

## Notas Importantes

- El campo "Tipo de Vehículo" es **OPCIONAL**
- Se ubica junto al campo de "Patente del Vehículo"
- Los cambios son compatibles con versiones anteriores
- Si la migración falla por columna duplicada, significa que ya fue ejecutada anteriormente

## Rollback (Deshacer Cambios)

Si necesitas revertir los cambios, ejecuta:

```sql
ALTER TABLE visitas DROP COLUMN tipo_vehiculo;
```

## Soporte

Si encuentras algún problema durante la instalación, verifica:
1. Que XAMPP esté ejecutándose correctamente
2. Que la base de datos `sgv` exista
3. Que tengas permisos de administrador en la base de datos
4. Que no haya errores en los archivos PHP actualizados

