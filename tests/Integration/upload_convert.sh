#!/usr/bin/env bash
# Integration smoke: upload a JPEG via the WS API and assert it becomes WebP.
# Requires: a running, installed Piwigo with the plugin activated; curl, jq.
set -euo pipefail

PWG_URL="${PWG_URL:?set PWG_URL}"
PWG_USER="${PWG_USER:?set PWG_USER}"
PWG_PASS="${PWG_PASS:?set PWG_PASS}"
JAR="$(mktemp)"
IMG="$(mktemp --suffix=.jpg)"

# 64x48 solid JPEG fixture via PHP GD (any JPEG works).
php -r '$i=imagecreatetruecolor(64,48);imagefilledrectangle($i,0,0,64,48,imagecolorallocate($i,200,80,40));imagejpeg($i,"'"$IMG"'",90);'

curl -s -c "$JAR" -d "method=pwg.session.login&username=$PWG_USER&password=$PWG_PASS" \
  "$PWG_URL/ws.php?format=json" >/dev/null

RESP="$(curl -s -b "$JAR" \
  -F "method=pwg.images.addSimple" \
  -F "image=@$IMG;type=image/jpeg" \
  "$PWG_URL/ws.php?format=json")"
echo "addSimple: $RESP"
IMAGE_ID="$(echo "$RESP" | jq -r '.result.image_id')"

# Give the upload hook a moment; then read back the stored path.
INFO="$(curl -s -b "$JAR" -d "method=pwg.images.getInfo&image_id=$IMAGE_ID" "$PWG_URL/ws.php?format=json")"
# element_url is the full-size original URL (ends in .webp after conversion);
# fall back to path. If neither is exposed, verify via the DB instead.
PATH_VALUE="$(echo "$INFO" | jq -r '.result.element_url // .result.path // empty')"
echo "stored path: $PATH_VALUE"

case "$PATH_VALUE" in
  *.webp) echo "PASS: uploaded photo stored as WebP" ;;
  *) echo "FAIL: expected .webp, got '$PATH_VALUE'"; exit 1 ;;
esac

rm -f "$JAR" "$IMG"
