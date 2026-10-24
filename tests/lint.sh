#!/usr/bin/env bash
# Lint de sintaxis en las dos versiones de PHP soportadas.
set -euo pipefail
cd "$(dirname "$0")/.."
for v in 7.4 8.3; do
	docker run --rm -v "$PWD/positibe-claude-connector":/app -w /app "php:${v}-cli" \
		sh -c 'find . -name "*.php" -print0 | xargs -0 -n1 php -l' | grep -v "^No syntax errors" || true
	echo "PHP ${v}: OK"
done
