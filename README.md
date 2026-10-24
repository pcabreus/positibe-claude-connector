# Positibe Claude Connector

Plugin de WordPress que deja el sitio listo para que Claude lo gestione por cualquier vía: **MCP** desde Claude Desktop, Cowork o Claude Code, y **REST directa** desde una terminal. Un usuario, una contraseña de aplicación y un registro de cada cambio.

## Cómo funciona

WordPress 6.9 trae el Abilities API: un registro de acciones que el sitio sabe hacer. El plugin oficial **MCP Adapter** publica esas acciones como servidor MCP. Este plugin registra cuatro abilities que, juntas, dan acceso a todo lo que expone la REST API:

| Ability | Para qué |
|---|---|
| `positibe-claude/site-context` | Orientarse: versiones, tema, plugins, WooCommerce, rol del usuario |
| `positibe-claude/list-routes` | Descubrir rutas REST por namespace (`wp/v2`, `wc/v3`, `rankmath/v1`...) |
| `positibe-claude/rest-request` | Cualquier petición REST como el usuario autenticado |
| `positibe-claude/upload-media` | Subir imágenes desde URL o base64, con alt text e imagen destacada |

Los permisos no se relajan: cada ruta REST comprueba las capacidades del usuario. **El rol del usuario de Claude es el límite real.**

## Instalación

1. `./build.sh` y sube `positibe-claude-connector.zip` en **Plugins → Añadir nuevo → Subir plugin**.
2. Para la vía MCP, instala también **MCP Adapter** desde el directorio de plugins.
3. Crea un usuario para Claude y, en su perfil, una **contraseña de aplicación**.
4. **Herramientas → Claude** muestra el estado y la configuración lista para copiar en cada cliente.

## Las tres vías

**Claude Desktop y Cowork** (MCP por proxy local, requiere Node.js):

```json
{
  "mcpServers": {
    "mi-sitio": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],
      "env": {
        "WP_API_URL": "https://ejemplo.com/wp-json/mcp/mcp-adapter-default-server",
        "WP_API_USERNAME": "USUARIO",
        "WP_API_PASSWORD": "CONTRASEÑA_DE_APLICACIÓN"
      }
    }
  }
}
```

**Claude Code** (MCP por HTTP, sin npx):

```bash
claude mcp add --scope user --transport http mi-sitio \
  https://ejemplo.com/wp-json/mcp/mcp-adapter-default-server \
  --header "Authorization: Basic $(printf 'USUARIO:CONTRASEÑA' | base64)"
```

**REST directa** (cualquier terminal; credenciales en un `.env` que nunca se versiona):

```bash
curl -s -u "$WP_USER:$WP_APP_PASSWORD" "$WP_URL/wp-json/wp/v2/pages?per_page=5"
```

## Skill para Claude

`skill/wordpress-site/SKILL.md` enseña a Claude a elegir la vía según dónde esté trabajando y fija las reglas de seguridad (borradores primero, confirmar lo destructivo). Es una plantilla: cópiala y añade los datos de tu sitio.

## Registro

Cada escritura, venga por MCP o por REST con contraseña de aplicación, queda anotada (las últimas 300) y se ve en **Herramientas → Claude**.

## Licencia

GPL-2.0-or-later.
