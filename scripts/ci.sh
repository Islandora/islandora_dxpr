#!/usr/bin/env bash

set -euo pipefail

ci_repository_root=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)
ci_image=${CI_IMAGE:-ghcr.io/islandora/ci:11.4-php8.4@sha256:7a5ceda970ce04a2339e18fe893a23ea8bb3acc366556e6284e699e1aeeb6132}

docker run --rm \
  --volume "$ci_repository_root:/workspace:ro" \
  --workdir /workspace \
  --entrypoint composer \
  "$ci_image" \
  validate --strict --no-check-publish

docker run --rm \
  --volume "$ci_repository_root:/workspace:ro" \
  --entrypoint sh \
  "$ci_image" \
  -c 'find /workspace -type f \( -name "*.yml" -o -name "*.yaml" \) -exec yq eval "." {} \; >/dev/null'

docker run --rm \
  --hostname drupal \
  --volume "$ci_repository_root:/var/www/drupal/web/themes/contrib/islandora_dxpr:ro" \
  --env ENABLE_MODULES=islandora_dxpr \
  --env TEST_SUITE=functional \
  "$ci_image"
