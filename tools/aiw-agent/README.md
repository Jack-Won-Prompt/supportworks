# AI Works 작업 PC 데몬

> **이 데몬은 운영 서버·운영 DB 접근 권한이 있는 PC 에 설치하지 마세요.**
>
> 데몬은 웹에서 등록한 지시를 이 PC 에서 Claude Code 로 실행합니다. 즉 웹 화면이
> 이 PC 의 파일과 명령에 닿습니다. 토큰이 유출되면 이 PC 를 장악당하는 것과
> 같습니다. 개인 개발 PC 에만 설치하세요.

SupportWorks 의 "AI 작업 지시" 화면에서 등록한 지시를 받아 Claude Agent SDK 로
실행하고, 진행 로그·대화·결과를 서버에 실시간으로 보고합니다.

## 요구사항

- Node.js 20 이상
- Claude Code 가 동작하는 환경 (`ANTHROPIC_API_KEY`)
- Windows: Git Bash — Claude Code 의 Bash 툴이 이를 요구합니다
- git (브랜치 분리를 쓸 경우)

## 설치

```bash
cd tools/aiw-agent
npm ci
cp .env.example .env    # 값 채우기
npm run build
npm start
```

`.env` 의 `SW_AGENT_TOKEN` 은 웹의 **설정 › AI 작업 PC** 에서 작업 PC 를 등록할 때
**한 번만** 표시됩니다. 다시 볼 수 없고 재발급만 가능합니다.

`REVERB_*` 는 서버 `.env` 와 같은 값을 씁니다. `agent_id` 는 하트비트 응답으로
자동으로 받으므로 따로 설정하지 않습니다.

## 동작

```
서버(Reverb) ──job.dispatched──▶ 데몬 ──▶ Claude Agent SDK
     ▲                             │
     └──── /api/aiw/* 로 보고 ──────┘
```

- **폴더 단위 직렬 실행** — 브랜치 충돌은 같은 워킹트리 안에서만 일어나므로,
  직렬 기준은 PC 가 아니라 `local_path` 입니다. 다른 프로젝트끼리는 동시에 돕니다.
  `MAX_PARALLEL_JOBS`(기본 2)는 PC 전체 상한입니다.
- **WebSocket 을 신뢰하지 않습니다** — 재접속할 때마다 `/jobs/pending` 과 활성
  job 의 `/inbox` 를 다시 훑어 누락을 메웁니다. 이벤트는 빠른 길, 폴링은 정확한 길입니다.
- **컨텍스트 인수인계** — job 의 `context_limit_tokens` 대비
  `CONTEXT_HANDOVER_RATIO`(기본 0.6)에 도달하면 **턴 경계에서** 인수인계 문서를
  쓰게 하고 세션을 교체합니다. 툴 실행 중에는 끊지 않습니다. 교체 중 도착한
  사용자 메시지는 보류했다가 새 세션에 순서대로 주입합니다.

### 컨텍스트 크기 계산

`context_tokens` 는 **그 턴 마지막 assistant 메시지의**
`input_tokens + cache_read_input_tokens + cache_creation_input_tokens` 입니다.
턴별로 **덮어씁니다**. 입력 토큰은 턴마다 전체 컨텍스트가 재전송되므로 합산하면
실제의 수 배로 부풀어 인수인계가 조기에·반복적으로 터집니다. 비용(`cost_usd`)만
누적값입니다.

## 보안

방어는 세 겹입니다. 어느 하나도 단독으로는 충분하지 않습니다.

### 1. 샌드박스 (`src/sandbox.ts`, `src/sandbox.rules.ts`)

프롬프트 문장이 아니라 **코드로** 막습니다.

- **경로**: job 시작 시 `realpath(local_path)` 를 ROOT 로 고정하고, 파일 툴의 경로
  인자를 `realpath` 후 ROOT 하위인지 검사합니다(심볼릭 링크 이탈 차단). 존재하지
  않는 새 파일은 존재하는 조상을 기준으로 판정하므로 정상적인 파일 생성은 막히지 않습니다.
- **명령**: `git push`, 원격 설정 변경, 광범위 삭제, 파이프 실행(`curl | sh`),
  자격증명 접근(`~/.ssh`, `~/.aws`, `~/.claude.json`, 브라우저 프로필), 시스템 명령
  (`shutdown`, `reg add`, `net user`)을 **승인 여부와 무관하게** 거부합니다.
- **작업 디렉터리**: Bash 를 실행하는 것은 데몬이 아니라 Claude Code 입니다.
  cwd 는 SDK `cwd` 옵션으로 고정하고, Windows 에서는 `SW_SHELL` 을
  `CLAUDE_CODE_GIT_BASH_PATH` 로 자식 프로세스에 넘깁니다.

**이 목록은 완전한 방어가 아닙니다.** 셸 우회 수단은 무한합니다. 사고 방지용이며,
완전 격리가 필요하면 컨테이너/VM 이 답입니다.

### 2. 승인 게이트 (`src/permissions.ts`)

모든 툴 호출이 예외 없이 한 지점을 통과합니다. 순서가 중요합니다:

1. 샌드박스 — 걸리면 서버에 묻지도 않고 거부. 사람이 실수로 [허용]을 눌러도
   통과하지 못해야 하는 것들입니다.
2. `acceptEdits` 흉내 — `Read/Edit/Write/Glob/Grep` 만 자동 승인.
   **Bash 는 절대 포함되지 않습니다.**
3. 서버 승인 요청 — 사람의 결정을 기다립니다.

> **SDK 옵션에 주의**: `allowedTools` 를 쓰지 않고 빈 배열로 둡니다. 거기에 툴을
> 넣으면 그 툴들이 `canUseTool` 을 건너뛰어 위 검사가 통째로 무력화됩니다.
> 사용 가능 툴의 제한은 `disallowedTools`(컨텍스트에서 제거)로 합니다.
> `permissionMode` 도 항상 `'default'` 입니다.

### 3. 권한 모델

최종 방어선입니다. **지시를 등록할 수 있는 사람 = 이 PC 에서 직접 명령을 실행해도
되는 사람** 이어야 합니다. 승인 카드가 있어도 사람이 습관적으로 [허용]을 누르면
무력화됩니다.

## 운영

### PM2 등록

```bash
npm i -g pm2
pm2 start dist/index.js --name aiw-agent
pm2 save
pm2 startup      # Windows 는 pm2-windows-startup 사용
```

### 토큰 재발급

웹의 **설정 › AI 작업 PC › 토큰 재발급**. 기존 토큰은 즉시 무효가 되므로
`.env` 를 고치고 데몬을 재시작해야 합니다. 만료 D-7 이내면 목록에 경고가 뜹니다.

### 로그

- 콘솔 — 데몬 자체 로그
- `logs/job-{id}.jsonl` — stream-json 원문 전체. 서버로 보내는 `raw` 는 4KB 로
  잘리므로 사후 조사에 쓸 전문은 여기에만 있습니다.

### 재기동

세션은 프로세스와 함께 사라집니다. `RESUME_ON_RESTART=true` 면 서버가 내려준
`resume_session_id` 로 이어붙이기를 시도하고, 실패하면 해당 job 은 실패 처리됩니다.
사용자는 **후속 지시**로 맥락을 이어갈 수 있습니다.

`claude --resume <session_id>` 로 VS Code 터미널에서 데몬이 돌린 세션을 직접 열 수
있지만, **데몬이 그 세션을 유지 중일 때는 하지 마세요.**

## 개발

```bash
npm run dev      # tsx watch
npm run build
npm test         # 샌드박스 차단 규칙 단위 테스트
```

## 알려진 제약

- **CLI 폴백 미구현.** 명세는 `@anthropic-ai/claude-agent-sdk` 설치 불가 시
  `claude -p --output-format stream-json` 을 child_process 로 띄우는 어댑터를
  대안으로 두었습니다. 현재 SDK 가 정상 설치되므로 만들지 않았습니다.
  `SessionAdapter` 인터페이스는 그대로라 필요해지면 추가할 수 있고, 그 경우
  승인 게이트는 지원되지 않으므로 Bash 를 제외한 acceptEdits 전용으로만 써야 합니다.
- **같은 폴더 동시 실행 불가.** `git worktree` 가 필요하며 v4 범위입니다.
- **IPv6 CIDR 미지원.** 허용 IP 목록의 CIDR 표기는 IPv4 만 계산합니다.
