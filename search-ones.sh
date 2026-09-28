#!/usr/bin/env bash
#
# search-ones.sh — Recherche les "(1)" dans les noms de fichiers du projet
#
#   Repère les fichiers/dossiers dont le nom contient "(1)"
#   (doublons type "README (1).md")
#
# Usage :
#   ./search-ones.sh                # recherche "(1)"
#   ./search-ones.sh "(2)"          # recherche un autre motif
#   ./search-ones.sh -d             # supprime les fichiers trouvés (avec confirmation)
#   ./search-ones.sh -d -y          # supprime sans confirmation
#
set -euo pipefail

PATTERN="(1)"
DELETE=false
ASSUME_YES=false

for arg in "$@"; do
    case "$arg" in
        -d|--delete) DELETE=true ;;
        -y|--yes)    ASSUME_YES=true ;;
        -h|--help)   grep '^#' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
        *)           PATTERN="$arg" ;;
    esac
done

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Répertoires exclus
EXCLUDES=(-not -path "*/vendor/*" -not -path "*/.git/*" -not -path "*/.idea/*" -not -path "*/.junie/*")

echo "============================================================"
echo " Recherche de \"$PATTERN\" dans : $ROOT"
echo "============================================================"

# Collecte des chemins (gère les espaces dans les noms)
mapfile -t found < <(find "$ROOT" "${EXCLUDES[@]}" -name "*${PATTERN}*" -print 2>/dev/null | sort)

echo
if (( ${#found[@]} == 0 )); then
    echo "(aucun)"
    exit 0
fi

printf '%s\n' "${found[@]#$ROOT/}"
echo
echo "Total : ${#found[@]} fichier(s)/dossier(s)"

if ! $DELETE; then
    exit 0
fi

# Suppression (avec confirmation sauf -y)
echo
if ! $ASSUME_YES; then
    read -r -p "Supprimer ces ${#found[@]} élément(s) ? [o/N] " answer
    [[ "$answer" =~ ^[oOyY]$ ]] || { echo "Annulé."; exit 0; }
fi

deleted=0
for f in "${found[@]}"; do
    if rm -rf -- "$f"; then
        ((++deleted))
    else
        echo "Échec : $f"
    fi
done

echo "$deleted élément(s) supprimé(s)."
