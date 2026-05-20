#!/usr/bin/env bash
# Production deploy script for SupportWorks (Laravel).
#
# Designed to run on the production server (Linux). On Windows, use Git Bash
# or WSL — this script will not work in cmd.exe or native PowerShell.
#
# Usage:
#   ./scripts/deploy.sh                          # full deploy from origin/master
#   ./scripts/deploy.sh --dry-run                # simulate: code IS synced, but destructive steps skipped
#   ./scripts/deploy.sh --branch ai-fix/123      # deploy a specific branch (for AI fix flow)
#   ./scripts/deploy.sh --no-rollback            # skip auto-rollback on healthz failure
#
# --dry-run semantics:
#   git fetch / checkout / pull → executed for real (working tree IS updated to origin/master)
#   composer install / mysqldump / artisan migrate / artisan down|up / artisan cache:* /
#   opcache reset / curl healthz → all skipped (printed as [dry])
#   Result: code is synced but caches/opcache are NOT rebuilt — next real deploy completes it.
#
# Env overrides (set before invoking):
#   APP_ROOT          default: current dir
#   BACKUP_DIR        default: /var/backups/supportworks
#   HEALTH_URL        default: http://localhost/healthz
#   PHP_FPM_SOCK      default: /run/php/php8.3-fpm.sock
#   PHP_FPM_SERVICE   default: php8.3-fpm
#
# Exit codes:
#   0  - success (or no changes to deploy)
#   1  - preflight failure (dirty tree, missing deps, etc)
#   2  - dependency install failed (composer / npm)
#   3  - migration failed
#   4  - healthz failed; auto-rollback succeeded — investigate logs
#   5  - healthz failed; rollback ALSO failed — manual intervention required NOW

set -euo pipefail
IFS=$'\n\t'

# ── Config (env overridable) ─────────────────────────────────────────────────
APP_ROOT="${APP_ROOT:-$(pwd)}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/supportworks}"
HEALTH_URL="${HEALTH_URL:-https://localhost/healthz}"
PHP_FPM_SOCK="${PHP_FPM_SOCK:-/run/php/php8.3-fpm.sock}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.3-fpm}"
BRANCH="master"
DRY_RUN=0
NO_ROLLBACK=0
HAS_MIGRATION=0   # set later
BACKUP_FILE=""    # set later

# ── Parse args ───────────────────────────────────────────────────────────────
while [ $# -gt 0 ]; do
    case "$1" in
        --dry-run)     DRY_RUN=1; shift ;;
        --no-rollback) NO_ROLLBACK=1; shift ;;
        --branch)      BRANCH="$2"; shift 2 ;;
        -h|--help)
            sed -n '2,30p' "$0" | sed 's/^# \{0,1\}//'
            exit 0
            ;;
        *) echo "Unknown arg: $1" >&2; exit 1 ;;
    esac
done

# ── Helpers ──────────────────────────────────────────────────────────────────
log()  { printf '[%s] %s\n' "$(date '+%F %T')" "$*"; }
step() { log ">> $*"; }
ok()   { log "   OK $*"; }
warn() { log "   WARN $*"; }
die()  { log "   FATAL $*"; exit "${2:-1}"; }
# run() echoes command then executes (or just echoes in dry-run mode)
run() {
    if [ "$DRY_RUN" -eq 1 ]; then
        log "   [dry] $*"
    else
        log "   $ $*"
        eval "$*"
    fi
}

# Helper: extract MySQL DSN from config/database.php into a temp .my.cnf file.
# This avoids passing the password on the command line (which would leak to ps).
make_mysql_cnf() {
    local target="$1"
    php -r '
        $c = (require "config/database.php")["connections"]["supportworks"];
        $body = sprintf("[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n",
            $c["host"], $c["port"] ?? 3306, $c["username"], $c["password"]);
        file_put_contents($argv[1], $body);
        chmod($argv[1], 0600);
    ' "$target"
}

# ── 0) Preflight ─────────────────────────────────────────────────────────────
step "Preflight"
# In a normal clone .git is a directory; in a git worktree it is a file pointing at the bare repo.
[ -d "$APP_ROOT/.git" ] || [ -f "$APP_ROOT/.git" ] || die "not a git repo: $APP_ROOT"
cd "$APP_ROOT"

# Refuse to deploy with uncommitted local changes (prod folder must be clean)
if [ -n "$(git status --porcelain)" ]; then
    die "uncommitted changes in $APP_ROOT — aborting"
fi
ok "clean working tree on $(git rev-parse --abbrev-ref HEAD) @ $(git rev-parse --short HEAD)"

# Required CLI tools (mysqldump optional — only needed if migration runs)
for tool in git composer php curl; do
    command -v "$tool" >/dev/null 2>&1 || die "missing required tool: $tool"
done
ok "required tools present (git/composer/php/curl)"

# Remember previous HEAD for rollback
PREV_HEAD=$(git rev-parse HEAD)
ok "prev HEAD: $PREV_HEAD"

# ── 1) Fetch + checkout target ───────────────────────────────────────────────
# fetch/checkout/pull 은 dry-run 에서도 실제 실행. 후속 단계 (composer/npm/migration
# diff 감지, cache/healthz 시뮬레이션) 가 의미를 가지려면 working tree 가 실제 origin
# 과 동기화돼야 함. 운영 코드는 갱신되지만 캐시 재빌드·opcache reset 은 dry-run 에서
# 여전히 skip 되므로 실제 traffic 변화는 다음 실제 배포에서 완성됨.
step "Fetch origin and bring $BRANCH up to date (always real, even in --dry-run)"
log "   $ git fetch origin --prune"
git fetch origin --prune                || die "git fetch failed" 1
log "   $ git checkout $BRANCH"
git checkout "$BRANCH"                  || die "git checkout failed" 1
log "   $ git pull --ff-only origin $BRANCH"
git pull --ff-only origin "$BRANCH"     || die "git pull failed" 1

NEW_HEAD=$(git rev-parse HEAD)
if [ "$PREV_HEAD" = "$NEW_HEAD" ]; then
    ok "already at $NEW_HEAD — nothing to deploy"
    exit 0
fi
ok "moved to $NEW_HEAD"

# ── 2) Detect what changed ───────────────────────────────────────────────────
step "Detect changes between $PREV_HEAD..$NEW_HEAD"
CHANGED=$(git diff --name-only "$PREV_HEAD" "$NEW_HEAD" || true)
[ -n "$CHANGED" ] && echo "$CHANGED" | sed 's/^/   /'

HAS_COMPOSER=0
HAS_NPM=0
if echo "$CHANGED" | grep -q '^composer\.\(json\|lock\)$'; then HAS_COMPOSER=1; fi
if echo "$CHANGED" | grep -qE '^(package(-lock)?\.json|vite\.config|resources/(css|js)/)'; then HAS_NPM=1; fi
if echo "$CHANGED" | grep -q '^database/migrations/'; then HAS_MIGRATION=1; fi

ok "composer=$HAS_COMPOSER  npm=$HAS_NPM  migration=$HAS_MIGRATION"

# ── 3) DB backup (only if migration present) ─────────────────────────────────
if [ "$HAS_MIGRATION" -eq 1 ]; then
    step "DB backup (migration detected)"
    if ! command -v mysqldump >/dev/null 2>&1; then
        die "mysqldump not found but migration present — install mariadb-client / mysql-client" 1
    fi
    mkdir -p "$BACKUP_DIR"
    BACKUP_FILE="$BACKUP_DIR/pre-deploy-$(date +%Y%m%d-%H%M%S).sql"
    CNF=$(mktemp /tmp/.my.cnf.XXXXXX)
    if [ "$DRY_RUN" -eq 1 ]; then
        log "   [dry] would mysqldump → $BACKUP_FILE"
    else
        make_mysql_cnf "$CNF"
        # Database name extracted from same config
        DB_NAME=$(php -r '$c=(require "config/database.php")["connections"]["supportworks"]; echo $c["database"];')
        mysqldump --defaults-file="$CNF" --single-transaction --routines "$DB_NAME" > "$BACKUP_FILE"
        rm -f "$CNF"
        ok "backup: $BACKUP_FILE ($(du -h "$BACKUP_FILE" | cut -f1))"
    fi
fi

# ── 4) Dependency installs ───────────────────────────────────────────────────
if [ "$HAS_COMPOSER" -eq 1 ]; then
    step "composer install --no-dev"
    run "composer install --no-dev --optimize-autoloader --no-interaction --no-progress" || die "composer install failed" 2
fi

if [ "$HAS_NPM" -eq 1 ]; then
    if ! command -v npm >/dev/null 2>&1; then
        die "npm not found but frontend changed — install Node.js" 2
    fi
    step "npm ci && npm run build"
    run "npm ci" || die "npm ci failed" 2
    run "npm run build" || die "npm build failed" 2
fi

# ── 5) Maintenance mode (only if migration) ──────────────────────────────────
if [ "$HAS_MIGRATION" -eq 1 ]; then
    step "Enter maintenance mode"
    run "php artisan down --retry=15"
fi

# ── 6) Migrate ───────────────────────────────────────────────────────────────
step "php artisan migrate"
if [ "$DRY_RUN" -eq 1 ]; then
    run "php artisan migrate --pretend --no-interaction"
else
    if ! php artisan migrate --force --no-interaction; then
        warn "migrate failed"
        if [ "$HAS_MIGRATION" -eq 1 ]; then
            php artisan up || true  # exit maintenance so admins can investigate
        fi
        exit 3
    fi
    ok "migrate done"
fi

# ── 7) Rebuild caches ────────────────────────────────────────────────────────
step "Rebuild caches"
run "php artisan config:cache"
run "php artisan route:cache"
run "php artisan view:cache"
run "php artisan event:cache"
run "php artisan queue:restart"

# ── 8) opcache reset ─────────────────────────────────────────────────────────
step "Reset opcache"
if command -v cachetool >/dev/null 2>&1 && [ -S "$PHP_FPM_SOCK" ]; then
    run "cachetool opcache:reset --fcgi=$PHP_FPM_SOCK"
elif command -v systemctl >/dev/null 2>&1; then
    warn "cachetool unavailable — falling back to: systemctl reload $PHP_FPM_SERVICE"
    run "systemctl reload $PHP_FPM_SERVICE" || warn "reload failed (continuing)"
else
    warn "no opcache reset mechanism — bytecode may serve stale until php-fpm restarts"
fi

# ── 9) Exit maintenance ──────────────────────────────────────────────────────
if [ "$HAS_MIGRATION" -eq 1 ]; then
    step "Exit maintenance mode"
    run "php artisan up"
fi

# ── 10) Health check (with retries) ──────────────────────────────────────────
step "Health check: $HEALTH_URL"
HEALTH_OK=0
for attempt in 1 2 3; do
    log "   attempt $attempt/3"
    if [ "$DRY_RUN" -eq 1 ]; then
        ok "[dry] would curl $HEALTH_URL"
        HEALTH_OK=1
        break
    fi
    if curl -fsSk --max-time 10 "$HEALTH_URL" > /tmp/healthz.out 2>&1; then
        ok "healthz: $(cat /tmp/healthz.out)"
        HEALTH_OK=1
        rm -f /tmp/healthz.out
        break
    fi
    sleep 3
done

# ── 11) Auto-rollback if healthz failed ──────────────────────────────────────
if [ "$HEALTH_OK" -ne 1 ]; then
    log "============================================================"
    log " HEALTHZ FAILED after deploy to $NEW_HEAD"
    log "============================================================"
    if [ "$NO_ROLLBACK" -eq 1 ]; then
        warn "--no-rollback set; leaving broken state for investigation"
        exit 4
    fi

    log " Auto-rollback: $NEW_HEAD -> $PREV_HEAD"
    run "git reset --hard $PREV_HEAD"

    if [ "$HAS_COMPOSER" -eq 1 ]; then
        run "composer install --no-dev --optimize-autoloader --no-interaction --no-progress" \
            || warn "rollback composer install failed (continuing)"
    fi

    # NOTE: migration rollback is intentionally NOT automated.
    # Backup restore is safer than artisan migrate:rollback for critical paths.
    if [ "$HAS_MIGRATION" -eq 1 ]; then
        warn "DB schema changed by deploy. Code rolled back; DB still on new schema."
        warn "Backup: $BACKUP_FILE"
        warn "Manual restore (only if necessary): mysql --defaults-file=<cnf> $DB_NAME < $BACKUP_FILE"
    fi

    run "php artisan config:cache"
    run "php artisan route:cache"
    run "php artisan view:cache"
    run "php artisan up || true"

    if command -v cachetool >/dev/null 2>&1 && [ -S "$PHP_FPM_SOCK" ]; then
        run "cachetool opcache:reset --fcgi=$PHP_FPM_SOCK"
    fi

    # Verify rollback healthz
    sleep 2
    if curl -fsSk --max-time 10 "$HEALTH_URL" > /dev/null 2>&1; then
        ok "rollback healthz OK"
        exit 4
    else
        log "============================================================"
        log " ROLLBACK HEALTHZ ALSO FAILED — MANUAL INTERVENTION REQUIRED"
        log "============================================================"
        exit 5
    fi
fi

# ── Done ─────────────────────────────────────────────────────────────────────
log "============================================================"
log " Deploy complete: $PREV_HEAD -> $NEW_HEAD"
log "============================================================"
[ "$HAS_MIGRATION" -eq 1 ] && log " Backup: $BACKUP_FILE"
exit 0