#!/usr/bin/env bash
#
# Genera el .zip instalable del plugin.
#
# WordPress espera que el archivo contenga una carpeta con el slug del plugin,
# así que se comprime `positibe-claude-connector/` desde la raíz del repo.

set -euo pipefail

cd "$(dirname "$0")"

SLUG="positibe-claude-connector"
ZIP="${SLUG}.zip"

if [ ! -f "${SLUG}/${SLUG}.php" ]; then
	echo "No encuentro ${SLUG}/${SLUG}.php. ¿Estás en la raíz del repo?" >&2
	exit 1
fi

VERSION=$(grep -m1 "^ \* Version:" "${SLUG}/${SLUG}.php" | sed 's/.*Version: *//' | tr -d ' \r')

rm -f "${ZIP}"
zip -rq "${ZIP}" "${SLUG}" -x "*.DS_Store" -x "*/.*"

echo "${ZIP} generado, versión ${VERSION}"
echo "Instalable desde Plugins → Añadir nuevo → Subir plugin."
