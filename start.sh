#!/bin/bash
set -e

cd "$(dirname "$0")"

PHP_BIN=""
for candidate in "/opt/lampp/bin/php" "/usr/bin/php" "/usr/local/bin/php" "/bin/php"; do
    if [ -x "$candidate" ]; then
        PHP_BIN="$candidate"
        break
    fi
done

if [ -z "$PHP_BIN" ]; then
    echo "No se encontró PHP en el sistema. Instala PHP o verifica tu instalación de XAMPP."
    exit 1
fi

"$PHP_BIN" -S 127.0.0.1:8000 -t .
