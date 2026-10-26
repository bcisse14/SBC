#!/usr/bin/env sh
set -eu

TARGETS="/home/bafs/SBC/backend/src /home/bafs/SBC/backend/config /home/bafs/SBC/backend/migrations /home/bafs/SBC/backend/templates /home/bafs/SBC/frontend/src /home/bafs/SBC/README.md /home/bafs/SBC/TODO_CONTENU.md /home/bafs/SBC/DIRECTION_ARTISTIQUE.md"

if command -v rg >/dev/null 2>&1; then
  if rg -n '[–—]' $TARGETS; then
    echo "Erreur: tiret cadratin ou demi-cadratin detecte."
    exit 1
  fi
else
  if grep -RIn '[–—]' $TARGETS; then
    echo "Erreur: tiret cadratin ou demi-cadratin detecte."
    exit 1
  fi
fi

echo "OK: aucun tiret cadratin detecte."
