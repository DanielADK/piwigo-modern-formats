#!/usr/bin/env bash
set -euo pipefail
# git archive applies .gitattributes export-ignore and prefixes every entry,
# so the zip extracts straight into plugins/modern_formats/.
git archive --format=zip --prefix=modern_formats/ -o modern_formats.zip HEAD
echo "Built modern_formats.zip"
