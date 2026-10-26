#!/bin/sh
set -eu

docker compose build

port=8080
while [ "$port" -le 65535 ]; do
    if ! python3 - "$port" <<'PY'
import socket
import sys

sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
try:
    sock.bind(("0.0.0.0", int(sys.argv[1])))
except OSError:
    raise SystemExit(1)
finally:
    sock.close()
PY
    then
        port=$((port + 1))
        continue
    fi

    if output=$(APP_PORT="$port" docker compose up -d 2>&1); then
        [ -z "$output" ] || printf '%s\n' "$output"
        printf 'Demarrage du projet sur http://localhost:%s\n' "$port"
        stop_stack() {
            trap - INT TERM
            docker compose stop
            exit 0
        }
        trap stop_stack INT TERM
        docker compose logs --follow
        exit $?
    else
        status=$?
        printf '%s\n' "$output" >&2
        case "$output" in
            *"port is already allocated"*|*"address already in use"*|*"failed to bind host port"*)
                printf 'Le port %s est indisponible pour Docker; essai du suivant.\n' "$port" >&2
                port=$((port + 1))
                ;;
            *)
                exit "$status"
                ;;
        esac
    fi
done

printf '%s\n' "Aucun port disponible entre 8080 et 65535." >&2
exit 1
