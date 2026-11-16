#!/usr/bin/env bash
# Runs only from a private release uploaded by the manual page workflow.
set -euo pipefail
umask 077
private_home=$(realpath "${HOME:?Private HOME directory required}")
[[ "$private_home" =~ ^/[a-zA-Z0-9_./-]+$ && "$private_home" != *..* && "$private_home" != */public_html* ]]
export HOME="$private_home"
operation=${1:-dry-run}
release=${2:?Private release path required}
selection=${3:-all}
approved_hash=${4:-}
editor_id=${5:-0}
wp_root=/home/customer/www/staging2.badcreditautoloans.net/public_html
[[ "$release" == "$HOME"/bcal-page-releases/* ]]
[[ "$release" =~ /[0-9]+-[0-9]+$ ]]
[[ ! -L "$release" && -d "$release" && "$(realpath "$release")" == "$release" ]]
[[ -d "$wp_root" && "$(realpath "$wp_root")" == "$wp_root" ]]
[[ "$selection" == all || "$selection" =~ ^[a-z][a-z0-9-]{0,79}$ ]]
case "$operation" in dry-run|apply|inspect-home) ;; *) exit 1 ;; esac
command -v wp >/dev/null
# Regular plugins and themes are skipped; no Rank Math save hooks are loaded.
wp_cmd=(wp --path="$wp_root" --skip-plugins --skip-themes --require="$release/scripts/pages.php")
if [[ "$operation" == inspect-home ]]; then
  "${wp_cmd[@]}" bc-pages inspect 21
  # Check the OLD content-only baseline too before adopting the new full-state baseline.
  wp --path="$wp_root" --skip-plugins --skip-themes eval-file "$release/scripts/import-staging-home.php"
  exit
fi
[[ "$editor_id" =~ ^[1-9][0-9]*$ ]]
wp_cmd+=(--user="$editor_id")
page_args=()
if [[ "$selection" != all ]]; then page_args+=(--page="$selection"); fi
if [[ "$operation" == dry-run ]]; then
  "${wp_cmd[@]}" bc-pages deploy "${page_args[@]}" --plan="$release/approved-plan.json"
else
  [[ "$approved_hash" =~ ^[a-f0-9]{64}$ ]]
  # The importer rebuilds the plan once more under database locks before writing.
  "${wp_cmd[@]}" bc-pages deploy "${page_args[@]}" --plan="$release/approved-plan.json" --plan-sha256="$approved_hash" --apply
fi
