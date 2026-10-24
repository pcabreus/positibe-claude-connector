---
name: wordpress-site
description: Gestiona un sitio WordPress y WooCommerce con Positibe Claude Connector sin entrar en wp-admin. Úsala siempre que el usuario pida cambiar o consultar páginas, landings, entradas, imágenes, productos, pedidos, menús, diseño, SEO, ajustes o plugins de su web, aunque no diga WordPress.
---

# Gestión de un sitio WordPress

El sitio tiene instalado **Positibe Claude Connector** y **MCP Adapter**. Claude usa un usuario dedicado, autenticado con una contraseña de aplicación. Su rol es el límite real de lo que puedes hacer.

## Elegir la vía

Usa la primera que esté disponible:

1. **MCP.** Si tienes herramientas de un servidor MCP de WordPress (discover, get info, execute ability), úsalas. Las abilities útiles:
   - `positibe-claude/site-context`: llámala al empezar.
   - `positibe-claude/list-routes`: descubre rutas por namespace.
   - `positibe-claude/rest-request`: cualquier petición REST.
   - `positibe-claude/upload-media`: subir imágenes desde URL o base64.
   - `woocommerce/*` y `rank-math/*`: acciones nativas de esos plugins.
2. **REST directa.** Si tienes una terminal que llega al sitio y existen `WP_URL`, `WP_USER` y `WP_APP_PASSWORD` en el entorno o en un `.env`:
   ```bash
   curl -s -u "$WP_USER:$WP_APP_PASSWORD" "$WP_URL/wp-json/wp/v2/pages?per_page=5"
   ```
   Para subir un archivo local: `curl -u ... -H "Content-Disposition: attachment; filename=foto.webp" --data-binary @foto.webp "$WP_URL/wp-json/wp/v2/media"`.
3. **Ninguna.** Dilo y explica cómo conectar: Herramientas → Claude en el wp-admin tiene la configuración lista para copiar. No pidas la contraseña en el chat.

Nunca escribas credenciales en archivos versionados, en mensajes ni en URLs.

## Mapa de rutas

| Qué | Ruta |
|---|---|
| Páginas, entradas | `/wp/v2/pages`, `/wp/v2/posts` |
| Medios | `/wp/v2/media` |
| Menús | `/wp/v2/menus`, `/wp/v2/menu-items`, `/wp/v2/navigation` |
| Diseño (tema de bloques) | `/wp/v2/templates`, `/wp/v2/template-parts`, `/wp/v2/global-styles` |
| Ajustes | `/wp/v2/settings` |
| Plugins | `/wp/v2/plugins` |
| Productos, pedidos, clientes, cupones | `/wc/v3/products`, `/wc/v3/orders`, `/wc/v3/customers`, `/wc/v3/coupons` |
| Ajustes y envíos de WooCommerce | `/wc/v3/settings`, `/wc/v3/shipping/zones` |
| SEO | `rank-math/*` por MCP, o `/rankmath/v1/*` |

Si una ruta no está aquí, búscala con `list-routes` antes de suponer que no existe.

## Reglas

- **Lee antes de escribir.** Obtén el estado actual con `context=edit` y modifica sobre él. Al editar contenido de bloques, conserva el marcado `<!-- wp:... -->`.
- **Borrador primero.** Crea páginas, entradas y productos con `status: draft` y da el enlace de vista previa. Publica solo cuando el usuario lo diga.
- **Confirma antes de** borrar, desactivar plugins, cambiar ajustes generales, cambiar precios o stock en bloque, o tocar plantillas y estilos globales. Di exactamente qué vas a cambiar.
- **Pedidos y clientes son datos personales.** Muestra solo lo necesario.
- **Imágenes:** WebP, nombre de archivo descriptivo en el idioma del sitio y alt text siempre.
- **Rediseños grandes** del tema van al repositorio del tema, no a cambios en vivo por la API.
- Al terminar, resume lo que cambió con enlaces. Cada escritura queda en el registro de Herramientas → Claude.

## Personalizar

Copia esta skill, cámbiale el nombre y añade lo propio del sitio: dominio, nombre del servidor MCP, usuario de Claude, tono de marca, estructura de páginas y cualquier regla del negocio. No pongas contraseñas.
