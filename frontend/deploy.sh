#!/usr/bin/env bash
#
# Build the Content Management frontend.
#
# This project's source folder and the web-served folder are the same
# directory, so nginx is configured to serve the dist/ subfolder (see
# deploy/nginx-frontend.conf). That means deploying is simply:
#
#   git pull && bash frontend/deploy.sh
#
# index.html in this folder is the Vite *source* entry and must stay as-is.
# Never copy the built index.html over it.

set -euo pipefail
cd "$(dirname "$0")"

# Guard against the classic mistake: a built index.html (which references
# /contentmanagement/frontend/assets/...) overwriting the Vite source entry.
if grep -q '/contentmanagement/frontend/assets/' index.html 2>/dev/null; then
  echo "index.html looks like a BUILT file, not the Vite source entry. Restoring it..."
  git checkout -- index.html
fi

npm ci
npm run build

echo "Built -> $(pwd)/dist  (served at /contentmanagement/frontend/)"
