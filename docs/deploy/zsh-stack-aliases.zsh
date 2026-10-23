# PaginiumCMS — zsh helpers for production/demo Docker Compose (replaces typing stack.sh paths).
# Usage on the server (required once per shell, or add to ~/.zshrc):
#   source /var/www/paginiumcms.com/docs/deploy/zsh-stack-aliases.zsh
# Or copy to ~/.config/paginium/stack.zsh and source from ~/.zshrc.
#
# zsh autocorrect may suggest "cmp" for "pcmp" — disable for this session:
#   unsetopt correct correct_all

if [[ -z "${ZSH_VERSION:-}" ]]; then
  echo "PaginiumCMS stack aliases: load this file from zsh (not bash)." >&2
  return 1 2>/dev/null || exit 1
fi

: "${PAGINIUM_STACK_PROD:=/var/lib/docker/compose/paginiumcms}"
: "${PAGINIUM_STACK_DEMO:=/var/lib/docker/compose/paginiumcms-demo}"

_paginium_compose() {
  local stack_dir="$1"
  shift

  local env_file="$stack_dir/.env"
  if [[ ! -r "$env_file" ]]; then
    print -u2 "PaginiumCMS: missing or unreadable $env_file"
    return 1
  fi

  set -a
  # shellcheck disable=SC1090
  source "$env_file"
  set +a

  : "${APP_ROOT:?APP_ROOT missing in stack .env}"
  : "${COMPOSE_PROJECT_NAME:?COMPOSE_PROJECT_NAME missing in stack .env}"

  local -a cmd=(
    docker compose
    -f "$APP_ROOT/docker-compose.yml"
    -f "$stack_dir/docker-compose.prod.yml"
    --project-name "$COMPOSE_PROJECT_NAME"
  )

  local deploy_key="${GITHUB_DEPLOY_KEY_FILE:-/var/lib/paginiumcms/secrets/github_deploy_key}"
  if [[ -f "$deploy_key" ]]; then
    if [[ -f "$stack_dir/docker-compose.deploy-key.yml" ]]; then
      cmd+=(-f "$stack_dir/docker-compose.deploy-key.yml")
    elif [[ -f "$APP_ROOT/docs/deploy/docker-compose.deploy-key.yml" ]]; then
      cmd+=(-f "$APP_ROOT/docs/deploy/docker-compose.deploy-key.yml")
    fi
  fi

  $cmd config --quiet || return 1
  $cmd "$@"
}

# Full names (recommended in scripts: paginium-prod exec …)
paginium-prod() { _paginium_compose "$PAGINIUM_STACK_PROD" "$@"; }
paginium-demo() { _paginium_compose "$PAGINIUM_STACK_DEMO" "$@"; }

# Short aliases
alias pcmp='paginium-prod'
alias pcmd='paginium-demo'

# Common shortcuts (optional)
alias pcmp-up='paginium-prod up -d --build'
alias pcmp-ps='paginium-prod ps'
alias pcmp-logs='paginium-prod logs -f --tail=100'
alias pcmp-php='paginium-prod exec -T -u www-data php php'

alias pcmd-up='paginium-demo up -d --build'
alias pcmd-ps='paginium-demo ps'
alias pcmd-php='paginium-demo exec -T -u www-data php php'
