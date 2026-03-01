#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
#  ArtiCMS — Script de correction des permissions
#  À lancer UNE FOIS avant l'installation, en tant que root ou via sudo :
#
#    sudo bash /var/www/html/ArtiCMS-1.0.0/fix-perms.sh
#
#  Ce script :
#    1. Change le propriétaire des fichiers vers www-data (utilisateur Apache)
#    2. Applique les bonnes permissions (755 dirs, 644 fichiers)
#    3. Rend les répertoires critiques accessibles en écriture par Apache
# ─────────────────────────────────────────────────────────────────────────────

set -e

ROOT="$(cd "$(dirname "$0")" && pwd)"
WEB_USER="${1:-www-data}"

echo ""
echo "  🎨 ArtiCMS — Correction des permissions"
echo "  Dossier : $ROOT"
echo "  Utilisateur web : $WEB_USER"
echo ""

# ── 1. Propriété générale ────────────────────────────────────────────────────
echo "  [1/4] Changement de propriétaire → $WEB_USER ..."
chown -R "$WEB_USER":"$WEB_USER" "$ROOT"

# ── 2. Permissions standards ─────────────────────────────────────────────────
echo "  [2/4] Permissions standards (755 dirs / 644 files) ..."
find "$ROOT" -type d -exec chmod 755 {} \;
find "$ROOT" -type f -exec chmod 644 {} \;

# ── 3. Scripts exécutables ───────────────────────────────────────────────────
echo "  [3/4] Scripts exécutables ..."
chmod +x "$ROOT/fix-perms.sh"

# ── 4. Répertoires accessibles en écriture par le serveur web ────────────────
echo "  [4/4] Répertoires écriture Apache ..."
chmod 775 "$ROOT/install"
chmod 775 "$ROOT/includes"
chmod 775 "$ROOT/uploads"
chmod 775 "$ROOT/storage"

echo ""
echo "  ✅ Permissions corrigées. Tu peux maintenant lancer l'installation !"
echo "     → http://TON_DOMAINE/install/"
echo ""
