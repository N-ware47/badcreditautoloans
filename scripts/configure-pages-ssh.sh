#!/usr/bin/env bash
set -euo pipefail
umask 077
[[ "${SSH_HOST:-}" == ssh.badcreditautoloans.net ]]
[[ "${SSH_PORT:-}" == 18765 ]]
[[ "${SSH_USER:-}" == u52-d9vb1kwmvkf1 ]]
[[ -n "${SSH_PRIVATE_KEY:-}" ]]
mkdir -p "$HOME/.ssh"
chmod 700 "$HOME/.ssh"
printf '%s\n' "$SSH_PRIVATE_KEY" > "$HOME/.ssh/id_siteground_pages"
chmod 600 "$HOME/.ssh/id_siteground_pages"
printf '%s\n' '[ssh.badcreditautoloans.net]:18765 ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIHguew7s8qU9WCyUvstxqvFrCOmPFrDxILOjIQHRPYyf' > "$HOME/.ssh/known_hosts"
chmod 600 "$HOME/.ssh/known_hosts"
