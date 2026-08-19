# Versionado

SVR Laravel Core sigue [Semantic Versioning](https://semver.org/lang/es/): `MAJOR.MINOR.PATCH`.

- `PATCH` corrige errores sin romper compatibilidad.
- `MINOR` agrega funcionalidad compatible.
- `MAJOR` introduce cambios incompatibles.

Mientras el paquete permanezca en versiones `0.x`, la API se considera en desarrollo y un incremento menor puede incluir cambios incompatibles. Revisa las notas de la versión antes de actualizar.

## Restricciones de plataforma

La línea actual admite:

| Laravel | PHP |
| --- | --- |
| 12.x | 8.2 o superior |
| 13.x | 8.3 o superior |

Aunque el paquete declara PHP `^8.2`, Laravel 13 impone PHP 8.3 desde el framework. Composer seleccionará una combinación compatible con el proyecto.

## Actualización recomendada

```bash
composer update svr/laravel-core --with-all-dependencies
composer test
```

Antes de actualizar revisa especialmente los cambios en configuración, macros de esquema, clases base y formato de serialización, ya que forman parte del contrato compartido entre proyectos.
