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

echo ">> systemctl enable --now supportworks-queue"
systemctl enable --now supportworks-queue

echo ""
echo "✓ supportworks-queue installed and started."
echo ""
echo "  Status:  systemctl status supportworks-queue"
echo "  Logs:    journalctl -u supportworks-queue -f --since '5 min ago'"
echo "  Restart: php artisan queue:restart   (graceful, deploy.sh 가 자동 호출)"
