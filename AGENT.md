# AGENT.md

Instrucciones para un agente de IA que vaya a trabajar en este repositorio.

## Qué es esto

Plugin de WordPress que conecta el sitio con Claude por MCP (vía el plugin MCP Adapter) y por REST directa. La carpeta `positibe-claude-connector/` **es** el plugin: su contenido se comprime tal cual y se instala.

**Lee `plans/SPEC.md` antes de tocar nada.** Si tu cambio contradice el spec, actualiza el spec en el mismo commit.

## Estructura

```
positibe-claude-connector/            El plugin
├── positibe-claude-connector.php     Cabecera, constantes, arranque
├── includes/
│   ├── class-pcc-abilities.php       Registro y ejecución de las abilities
│   ├── class-pcc-log.php             Registro de escrituras (MCP y REST)
│   └── class-pcc-admin.php           Herramientas → Claude
├── readme.txt
└── uninstall.php

plans/SPEC.md                          Especificación y decisiones
skill/wordpress-site/SKILL.md              Skill de Claude para usar el sitio
tests/lint.sh                          Lint en PHP 7.4 y 8.3 con Docker
build.sh                               Genera el .zip instalable
```

## Reglas que no se negocian

1. **Nunca relajar permisos.** `rest-request` delega en las comprobaciones de cada ruta REST. No añadir `current_user_can` más laxos ni ejecutar nada como otro usuario.
2. **Sin dependencias ni compilación.** Nada de Composer ni npm en el plugin. MCP Adapter es una dependencia blanda: sin él, la REST directa debe seguir funcionando.
3. **Nada de credenciales en el repo.** `.env` está en `.gitignore`. Los ejemplos usan `USUARIO` y `CONTRASEÑA_DE_APLICACIÓN`.
4. **Sintaxis compatible con PHP 7.4.** Nada de `match`, nullsafe, argumentos nombrados ni propiedades promovidas.
5. **Toda escritura queda registrada una sola vez.** Las abilities anotan con origen `mcp` y activan `PCC_Log::$inside_ability` para que el hook REST no duplique.

## Convenciones

- Cadenas de interfaz y comentarios en español, con text domain `positibe-claude-connector`. Las descripciones de las abilities van en inglés: las lee el modelo, no el usuario.
- WordPress Coding Standards. Prefijos `PCC_` y `pcc_`.
- Los comentarios explican el porqué.

## Al terminar una tarea

1. `tests/lint.sh` en verde.
2. Probar en un WordPress real: `positibe-claude/site-context` y una escritura con `rest-request` que aparezca en el registro.
3. Versión subida en la cabecera, `PCC_VERSION` y `Stable tag`, más una entrada en el changelog.
