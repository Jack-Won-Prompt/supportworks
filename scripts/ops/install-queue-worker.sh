#!/usr/bin/env bash
# SupportWorks queue worker — Ubuntu systemd 설치 스크립트.
#
# 운영(Ubuntu)에서 1회 실행. 서비스 파일을 /etc/systemd/system/ 으로 복사하고
# daemon-reload → enable → start 까지 자동.
#
# 사용:
#   sudo ./scripts/ops/install-queue-worker.sh
#
# 옵션 환경변수:
#   APP_ROOT          서비스 안의 WorkingDirectory 가 가리킬 절대 경로
#                     (default: /var/www/supportworks)
#                     install 시점에 서비스 파일 안의 경로를 sed 로 치환.
#   QUEUE_USER        서비스 실행 유저 (default: ubuntu)

set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "FATAL: must run as root (use sudo)" >&2
    exit 1
fi

APP_ROOT="${APP_ROOT:-/var/www/supportworks}"
QUEUE_USER="${QUEUE_USER:-ubuntu}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC="$SCRIPT_DIR/supportworks-queue.service"
DST="/etc/systemd/system/supportworks-queue.service"

[ -f "$SRC" ] || { echo "FATAL: service file not found: $SRC" >&2; exit 2; }
[ -d "$APP_ROOT" ] || { echo "FATAL: APP_ROOT not found: $APP_ROOT" >&2; exit 2; }
[ -f "$APP_ROOT/artisan" ] || { echo "FATAL: artisan not found in $APP_ROOT" >&2; exit 2; }

echo ">> Install $DST"
# WorkingDirectory / EnvironmentFile / ExecStart 의 /var/www/supportworks 를 $APP_ROOT 로 치환
sed \
    -e "s|/var/www/supportworks|$APP_ROOT|g" \
    -e "s|^User=ubuntu$|User=$QUEUE_USER|" \
    -e "s|^Group=ubuntu$|Group=$QUEUE_USER|" \
    "$SRC" > "$DST"
chmod 0644 "$DST"

echo ">> systemctl daemon-reload"
systemctl daemon-reload

echo ">> systemctl enable supportworks-queue"
systemctl enable supportworks-queue >/dev/null

echo ">> systemctl restart supportworks-queue"
# enable --now 는 시작 실패를 silent 로 넘기는 경우가 있어 분리 실행 + 상태 확인.
if ! systemctl restart supportworks-queue; then
    echo "" >&2
    echo "✗ supportworks-queue 시작 실패." >&2
    echo "  systemctl status supportworks-queue --no-pager" >&2
    echo "  journalctl -u supportworks-queue -n 50 --no-pager" >&2
    systemctl status supportworks-queue --no-pager || true
    exit 3
fi

# active 상태 검증 (시작 직후 즉시 종료된 경우 잡기)
sleep 1
if ! systemctl is-active --quiet supportworks-queue; then
    echo "" >&2
    echo "✗ supportworks-queue 시작 후 즉시 비활성. 로그 확인:" >&2
    journalctl -u supportworks-queue -n 30 --no-pager || true
    exit 4
fi

echo ""
echo "✓ supportworks-queue installed and ACTIVE."
echo ""
echo "  Status:  systemctl status supportworks-queue"
echo "  Logs:    journalctl -u supportworks-queue -f --since '5 min ago'"
echo "  Restart: php artisan queue:restart   (graceful, deploy.sh 가 자동 호출)"
