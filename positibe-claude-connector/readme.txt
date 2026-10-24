=== Positibe Claude Connector ===
Contributors: positibelabs
Tags: claude, mcp, ai, rest api, woocommerce
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Conecta el sitio con Claude por MCP y por REST directa, con un único usuario, una contraseña de aplicación y un registro de cada cambio.

== Description ==

Expone la REST API completa del sitio como abilities de WordPress, de modo que el plugin MCP Adapter las publica para Claude Desktop, Cowork y Claude Code. La misma contraseña de aplicación sirve para llamar a la REST API directamente.

* `positibe-claude/site-context`: versiones, tema, plugins, WooCommerce y rol del usuario.
* `positibe-claude/list-routes`: descubre las rutas REST disponibles.
* `positibe-claude/rest-request`: cualquier petición REST como el usuario autenticado.
* `positibe-claude/upload-media`: sube imágenes desde una URL o en base64, con alt text e imagen destacada.

Los permisos no se relajan: cada ruta comprueba las capacidades del usuario. Herramientas → Claude muestra el estado, la configuración lista para copiar y las últimas escrituras.

== Changelog ==

= 1.0.0 =
* Primera versión.
