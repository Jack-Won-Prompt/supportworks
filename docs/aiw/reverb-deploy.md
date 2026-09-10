# AI Works — Reverb 운영 배포 절차

대상: `www.supportworks.co.kr` (`ubuntu@15.165.77.147`, `~/www/supportworks`)

> **핵심 전제: 기본 브로드캐스터는 계속 `pusher`다.** Reverb는 AI Works 전용으로만 쓴다.
> `.env`의 `BROADCAST_CONNECTION=pusher`를 **절대 바꾸지 않는다.** 채팅·협업·문의·분석 세션이 전부 Pusher Cloud에 물려 있고, Reverb는 self-host라 프로세스가 죽으면 그 기능들까지 함께 멈춘다.
>
> 같은 이유로 **`php artisan reverb:install`을 실행하지 않는다.** 이 명령의 `updateBroadcastingDriver()`가 `BROADCAST_CONNECTION`을 `reverb`로 덮어쓰며, `--no-interaction`에서는 확인 프롬프트가 기본값 `true`로 처리되어 그대로 실행된다. 필요한 작업은 아래 두 가지뿐이고 이미 저장소에 반영돼 있다.
> - `config/reverb.php` 발행 (`vendor:publish --tag=reverb-config`)
> - `.env` / `.env.example`의 `REVERB_*` 변수

---

## 0. 이 서버의 제약 (사전 확인 결과, 2026-09-10)

| 항목 | 값 | 함의 |
|---|---|---|
| 웹서버 | Nginx 1.24.0, php-fpm 8.3 (`/run/php/php8.3-fpm.sock`) | Nginx 문법 사용 |
| 프로세스 관리 | **Supervisor 미설치. systemd 사용** | Reverb·스케줄러 모두 systemd 유닛으로 만든다 |
| 기존 유닛 | `supportworks-queue.service` (queue:work) | 새 유닛의 템플릿 |
| 방화벽 | **ufw 비활성** — AWS 보안그룹이 유일한 차단 수단 | Reverb를 `127.0.0.1`에 바인딩해 외부 노출을 원천 차단 |
| 자원 | **RAM 957MB / vCPU 1**, swap 2GB | **9개 사이트 공동 호스팅**(korsafety, leefriends, mangoshop, medisell, moons, ndn, sam, smartlogis, supportworks). Reverb 상주 프로세스가 이들과 메모리를 경합한다 |
| PHP 버전 | 서버 8.3.6 / 로컬 8.5.3 | `composer.json`의 `config.platform.php = 8.3.6` 핀을 유지해야 서버에서 `composer install`이 된다 |

---

## 1. 배포 순서

**코드를 먼저 올려도 안전하다.** `routes/channels.php`의 등록 블록은 `REVERB_APP_KEY`·`SECRET`·`APP_ID` 세 값이 모두 채워졌을 때만 실행된다. 값이 없으면 등록을 건너뛰고 시간당 1회 경고만 남긴다.

> 이 가드가 없으면 `git pull` 직후(= `.env` 편집 전) **사이트 전체가 500**이 된다. `channels.php`는 `booted()`에서 매 부팅 `require`되고, `Broadcast::connection('reverb')`가 그 자리에서 `new Pusher(key, secret, app_id)`까지 가는데, 미설정 시 `null`이 non-nullable `string` 파라미터로 넘어가 TypeError가 나기 때문이다. **가드를 제거하지 말 것.**

```bash
cd ~/www/supportworks
git pull --ff-only origin master
composer install --optimize-autoloader --no-interaction   # --no-dev 금지 (dev 패키지 설치 상태 유지)
```

`config/database.php`는 서버에 의도적 로컬 수정(DB host)이 있다. **`git stash`를 쓰지 말 것** — pop 없이 stash하면 운영 DB 호스트가 저장소값으로 되돌아간다.

## 2. `.env` 설정

```
# 기본 브로드캐스터는 그대로 둔다
BROADCAST_CONNECTION=pusher

REVERB_APP_ID=<운영 전용 값>
REVERB_APP_KEY=<운영 전용 값>
REVERB_APP_SECRET=<운영 전용 값>
REVERB_HOST=www.supportworks.co.kr
REVERB_PORT=443
REVERB_SCHEME=https

# 서버 바인딩: nginx가 같은 호스트에서 프록시하므로 루프백으로 충분하다.
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

**운영 값은 로컬 개발용과 다르게 생성한다.** 예: `php -r "echo bin2hex(random_bytes(10));"`

`VITE_*`는 빌드 시점에 번들에 박히므로 값을 바꾸면 `npm run build`를 다시 해야 한다.

## 3. Reverb systemd 유닛

`/etc/systemd/system/supportworks-reverb.service`

```ini
[Unit]
Description=SupportWorks Reverb WebSocket Server (AI Works)
After=network.target

[Service]
Type=simple
User=ubuntu
Group=www-data
WorkingDirectory=/home/ubuntu/www/supportworks
ExecStartPre=+/bin/bash -c 'touch /home/ubuntu/www/supportworks/storage/logs/reverb.log && chown ubuntu:www-data /home/ubuntu/www/supportworks/storage/logs/reverb.log && chmod 664 /home/ubuntu/www/supportworks/storage/logs/reverb.log'
ExecStart=/usr/bin/php artisan reverb:start --host=127.0.0.1 --port=8080
Restart=always
RestartSec=5
# RAM 957MB 서버에 9개 사이트가 함께 있다. 폭주 시 다른 사이트를 죽이지 않도록 상한을 둔다.
MemoryMax=192M
StandardOutput=append:/home/ubuntu/www/supportworks/storage/logs/reverb.log
StandardError=append:/home/ubuntu/www/supportworks/storage/logs/reverb.log

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now supportworks-reverb
sudo systemctl status supportworks-reverb --no-pager
ss -lnt | grep 8080     # 127.0.0.1:8080 만 떠 있어야 한다 (0.0.0.0 이면 잘못된 것)
```

## 4. 스케줄러 systemd 유닛

**이 서버에는 스케줄러가 없다** (crontab·cron.d·systemd timer 어디에도 `schedule:run` 없음). AI Works의 승인 타임아웃 만료·좀비 job 정리·`aiw:prune`이 전부 여기에 의존하므로 반드시 만든다.

`/etc/systemd/system/supportworks-schedule.service`

```ini
[Unit]
Description=SupportWorks Laravel Scheduler
After=network.target mysql.service

[Service]
Type=simple
User=ubuntu
Group=www-data
WorkingDirectory=/home/ubuntu/www/supportworks
ExecStartPre=+/bin/bash -c 'touch /home/ubuntu/www/supportworks/storage/logs/schedule.log && chown ubuntu:www-data /home/ubuntu/www/supportworks/storage/logs/schedule.log && chmod 664 /home/ubuntu/www/supportworks/storage/logs/schedule.log'
ExecStart=/usr/bin/php artisan schedule:work
Restart=always
RestartSec=5
StandardOutput=append:/home/ubuntu/www/supportworks/storage/logs/schedule.log
StandardError=append:/home/ubuntu/www/supportworks/storage/logs/schedule.log

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now supportworks-schedule
```

> **주의:** 이 서버는 9개 사이트를 호스팅한다. `schedule:work`는 supportworks의 스케줄만 돌린다. 다른 사이트에 스케줄이 필요하면 사이트별 유닛을 따로 만든다.

## 5. Nginx 프록시

`/etc/nginx/sites-enabled/supportworks`의 **443 서버 블록 안**, `location / {}` 앞에 추가한다.

```nginx
    # ── AI Works: Reverb WebSocket ──────────────────────────────
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade    $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host       $host;
        proxy_set_header X-Real-IP  $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout  86400;
        proxy_send_timeout  86400;
    }

    # ── AI Works: Reverb HTTP API (서버 → Reverb 이벤트 발행) ────
    location /apps {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host       $host;
        proxy_set_header X-Real-IP  $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
```

```bash
sudo nginx -t && sudo systemctl reload nginx
```

> `/app`·`/apps`는 Pusher 프로토콜이 쓰는 고정 경로다. 이 경로를 쓰는 애플리케이션 라우트가 없는지 확인했다(현재 없음). 나중에 충돌하면 `REVERB_SERVER_PATH`로 접두사를 붙일 수 있다.

## 6. 검증

```bash
# 1) 앱이 정상 부팅하는가
php artisan --version

# 2) Reverb 가 루프백에만 떠 있는가
ss -lnt | grep 8080

# 3) 외부에서 8080 이 닫혀 있는가 (로컬 PC 에서)
#    → 연결이 되면 안 된다
# 4) 채널 인가가 200 인가 (403 이면 channels.php 의 reverb 등록 누락)
php artisan test --filter=ReverbAuthTest

# 5) 기존 Pusher 채널(채팅)이 여전히 동작하는가  ← 반드시 확인
```

`tests/Feature/AiWork/ReverbAuthTest.php`가 4번을 회귀 테스트로 고정한다. `REVERB_*`가 없는 환경에서는 자동으로 skip된다.

## 7. 장애 대응

| 증상 | 원인 | 조치 |
|---|---|---|
| 전 페이지 500, artisan도 실행 불가 | `channels.php`의 가드를 제거한 상태에서 `REVERB_*` 미설정 | 가드를 복원하거나 `.env`에 세 값을 채운다 |
| AI Works 구독만 403 | 채널이 reverb 커넥션에 등록되지 않음 | `routes/channels.php`의 `Broadcast::connection('reverb')->channel(...)` 확인. `laravel.log`에 "reverb 채널 등록을 건너뜀" 경고가 있으면 `.env` 문제 |
| WebSocket 연결이 즉시 끊김 | 서명 불일치 | 브라우저가 `/broadcasting/auth`(pusher secret)를 쓰고 있지 않은지 확인. `/aiw/broadcasting/auth`여야 한다 |
| Reverb 반복 재시작 | `MemoryMax` 초과 | `journalctl -u supportworks-reverb -n 50`. 상한을 올리기 전에 서버 여유 메모리를 먼저 본다 |
| 채팅·협업까지 멈춤 | 누군가 `BROADCAST_CONNECTION`을 `reverb`로 바꿈 | `pusher`로 되돌리고 `php artisan config:clear` |

## 8. 모니터링

```bash
sudo systemctl status supportworks-reverb supportworks-schedule supportworks-queue --no-pager
tail -f storage/logs/reverb.log
free -h            # 9개 사이트 공동 호스팅이라 여유 메모리를 주기적으로 본다
```
