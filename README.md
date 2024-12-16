# Symfony PHP Backend

## Descripción

Este proyecto es el backend principal desarrollado con Symfony y PHP, utilizando MySQL como sistema de gestión de bases de datos. Gestiona las operaciones CRUD de empleados y se encarga de la lógica de negocio de la aplicación.

## Tabla de Contenidos

- [Tecnologías Utilizadas](#tecnologías-utilizadas)
- [Prerequisitos](#prerequisitos)
- [Instalación](#instalación-configuración)
- [Tests](#tests)

## Tecnologías-Utilizadas

- **Framework:** Symfony 5.4
- **Lenguaje:** PHP 8.1
- **Base de Datos:** MySQL
- **ORM:** Doctrine
- **Servidor Web:** Apache/Nginx
- **Herramientas de Desarrollo:** Composer, PHPUnit

## Prerequisitos

Antes de comenzar, asegúrate de tener instalado lo siguiente en tu sistema:

- **PHP:** >= 8.1
- **Composer:** Administrador de dependencias para PHP
- **MySQL:** >= 5.7
- **Servidor Web:** Apache o Nginx
- **Git:** Para clonar el repositorio

## instalación-configuración

1. **Clonar el Repositorio**

   ```bash
   git clone https://github.com/torvictorvic/unow-symfony-backend
   cd symfony-backend

2. **Instalar Dependencias con Composer**
   ```bash
   composer install

3. **Configurar Variables de Entorno**
   ```bash
   cp .env.example .env.local

4. **Configuración de JWT**
   Para manejar la autenticación con JWT, es necesario generar un par de claves pública y privada. Seguir estos pasos:

   **Generar las Claves:**

    Abrir una terminal en la raíz del proyecto y ejecuta los siguientes comandos para generar las claves `private.pem` y `public.pem`:

    ```bash
    mkdir -p config/jwt
    openssl genrsa -out config/jwt/private.pem -aes256 4096
    openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
    ```

    - **Nota:** Durante la generación de `private.pem`, se pedirá una contraseña. Se debe guardar la contraseña en un lugar seguro, ya que será necesaria para la configuración del proyecto.

    **Configurar Variables de Entorno:**

    La informacion relaciona al JWT se debe colocar en el .env.local

    ```env
    # JWT Keys
    JWT_PASSPHRASE=tu_contraseña_de_private.pem
    JWT_PUBLIC_KEY_PATH=%kernel.project_dir%/config/jwt/public.pem
    JWT_PRIVATE_KEY_PATH=%kernel.project_dir%/config/jwt/private.pem
    ```

    - **Descripción de Variables:**
        - `JWT_PASSPHRASE`: La contraseña que estableciste al generar `private.pem`.
        - `JWT_PUBLIC_KEY_PATH`: Ruta al archivo `public.pem`.
        - `JWT_PRIVATE_KEY_PATH`: Ruta al archivo `private.pem`.

    **Actualizar la Configuración de Symfony:**

    Se debe asegurar la configuración en la parte de seguridad del Symfony (`config/packages/security.yaml` o similar) esté utilizando estas variables de entorno para la configuración de JWT. Un ejemplo básico sería:

    ```yaml
    # config/packages/lexik_jwt_authentication.yaml

    lexik_jwt_authentication:
        secret_key:       '%env(resolve:JWT_PRIVATE_KEY_PATH)%'
        public_key:       '%env(resolve:JWT_PUBLIC_KEY_PATH)%'
        pass_phrase:      '%env(JWT_PASSPHRASE)%'
        token_ttl:        3600
    ```

   **Verificar dependencias para JWT (si aún no lo has hecho):**

    Si aún no has instalado el bundle de LexikJWTAuthentication, hazlo ejecutando:

    ```bash
    composer require lexik/jwt-authentication-bundle
    ```

6. **Dependiendo la version del php, ejecuta el siguiente comando para crear la base de datos**
   ```bash
   php8.1 bin/console doctrine:database:create

7. **Aplicar las migraciones**
   ```bash
   php8.1 bin/console doctrine:database:create

8. **Aplicar las migraciones**
   ```bash
   php8.1 bin/console doctrine:migrations:migrate



## tests

1. **Correr Test unitarios**
   ```bash
   php8.1 vendor/bin/phpunit
