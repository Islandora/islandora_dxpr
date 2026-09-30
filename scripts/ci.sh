#!/usr/bin/env bash

set -euo pipefail

ci_repository_root=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)
ci_image=${CI_IMAGE:-ghcr.io/islandora/ci:11.4-php8.4}
ci_browser_image=selenium/standalone-chromium:4.41.0@sha256:36474a4c56765ec3d04dc4cbac57a021e0d88e89fdb5dca297a50f1100279c1a

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

ci_network="islandora-dxpr-ci-${RANDOM}-${RANDOM}"
ci_browser="${ci_network}-browser"
ci_drupal="${ci_network}-drupal"
cleanup() {
  docker rm -f "$ci_drupal" "$ci_browser" >/dev/null 2>&1 || true
  docker network rm "$ci_network" >/dev/null 2>&1 || true
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

docker network create "$ci_network" >/dev/null
docker run --detach --rm \
  --name "$ci_browser" \
  --network "$ci_network" --network-alias browser \
  --shm-size 2g \
  --entrypoint chromedriver \
  "$ci_browser_image" --port=9515 --allowed-ips= --allowed-origins='*' >/dev/null

ci_browser_ready=0
for ((attempt = 0; attempt < 60; attempt++)); do
  if docker exec "$ci_browser" curl --fail --silent http://localhost:9515/status \
    | grep -q '"ready":[[:space:]]*true'; then
    ci_browser_ready=1
    break
  fi
  sleep 1
done
if (( ! ci_browser_ready )); then
  docker logs "$ci_browser"
  exit 1
fi

docker run --rm \
  --name "$ci_drupal" \
  --hostname drupal \
  --network "$ci_network" --network-alias drupal \
  --volume "$ci_repository_root:/var/www/drupal/web/themes/contrib/islandora_dxpr:ro" \
  --env ENABLE_MODULES=islandora_dxpr \
  --env TEST_SUITE=functional,functional-javascript \
  --env COMPOSER_POLICY_ADVISORIES_BLOCK=0 \
  --env COMPOSER_NO_AUDIT=1 \
  --env 'MINK_DRIVER_ARGS_WEBDRIVER=["chrome", {"browserName":"chrome", "goog:chromeOptions":{"args":["--headless", "--no-sandbox", "--disable-dev-shm-usage"]}}, "http://browser:9515"]' \
  "$ci_image"
