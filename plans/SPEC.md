# SPEC: Positibe Claude Connector

## Objetivo

Que Claude pueda gestionar un sitio WordPress y WooCommerce completo sin entrar en wp-admin, desde cualquier cliente (Claude Desktop, Cowork, Claude Code), con un único juego de credenciales y trazabilidad de cada cambio.

## Decisiones

**Una ability genérica en vez de muchas específicas.** La REST API ya cubre entradas, páginas, medios, menús, plantillas, estilos globales, usuarios, ajustes, plugins, WooCommerce y cualquier plugin con rutas propias. Escribir una ability por caso dejaría siempre huecos y duplicaría lógica. `rest-request` reutiliza la validación y los permisos de cada ruta mediante `rest_do_request()`.

**Abilities extra solo donde la REST se queda corta.** `upload-media` existe porque la ruta de medios exige el binario en el cuerpo, algo incómodo por MCP. `site-context` resume en una llamada lo que costaría varias.

**MCP Adapter como dependencia blanda.** No se declara en `Requires Plugins` porque la vía REST directa no lo necesita.

**Autenticación con contraseñas de aplicación.** Es el mecanismo nativo de WordPress, revocable por separado y válido para ambas vías. El rol del usuario dedicado es el límite: Administrador para acceso total, Gestor de tienda para contenido y tienda.

**Registro solo de escrituras.** Las lecturas no cambian nada y saturarían el buffer. Buffer circular de 300 entradas en una option sin autoload.

## Fuera de alcance

- OAuth. Si se necesita conectar como conector remoto de claude.ai, se valorará en otra versión.
- Ejecución de PHP arbitrario o WP-CLI desde MCP.
