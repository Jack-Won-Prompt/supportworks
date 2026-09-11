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
- Windows: Git Bash — Claude Code 의 Bash 툴이 이를 요구합니다 (`SW_SHELL`)
- git (브랜치 분리를 쓸 경우)

**Claude Code 를 따로 설치할 필요는 없습니다.** `@anthropic-ai/claude-agent-sdk` 가
optionalDependencies 로 실행 파일(`claude.exe`, 약 210MB)을 함께 내려받습니다.
`npm ci` 가 느리고 `node_modules` 가 큰 이유입니다. VS Code 의 Claude Code 확장이나
전역 `claude` 설치와는 무관하게 동작합니다.

### 인증 — 두 가지 방식

| 방식 | 설정 | 과금 | 화면 표기 |
|---|---|---|---|
| 구독 로그인 | `ANTHROPIC_API_KEY` **를 비워 둔다** | 이 PC 의 Claude Code 로그인 계정 | **예상 사용량** (추정치) |
| API 키 | `ANTHROPIC_API_KEY=sk-...` | 키 소유 계정에 실제 청구 | **비용** (실제 금액) |

어느 쪽인지 기동 로그 첫 줄에 남습니다. 데몬은 하트비트의 `capabilities.auth_mode`
로 이를 서버에 알리고, 서버는 그에 맞춰 게이지 이름을 바꿉니다.

> **현재 운영은 구독 로그인 방식입니다.** 따라서 화면의 사용량 숫자는 **추정치이며
> 실제 청구액이 아닙니다.** 이 PC 에서 `claude` 에 한 번 로그인해 두면 됩니다.

## 설치

```bash
cd tools/aiw-agent
npm ci
cp .env.example .env    # 값 채우기
npm run build
npm start
```

`.env` 의 `SW_AGENT_TOKEN` 은 웹의 **관리자 › 담당자** 에서 담당자를 등록할 때
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
2. `acceptEdits` 흉내 — 서버 `config('aiw.auto_approvable')` 와 같은 목록만 자동 승인.
   현재 `Read/Edit/Write/`**`Bash`**`/Glob/Grep` 입니다.
3. 서버 승인 요청 — 사람의 결정을 기다립니다. 서버에 물을 수 없으면 **거부**합니다.

> **`acceptEdits` 는 이제 임의 명령까지 자동 승인합니다**(운영자 결정, 2026-09-11).
> 이 모드에서 사람이 명령을 보는 지점은 없고, 남는 방어선은 1번의 샌드박스와
> 작업 폴더 경계뿐입니다. 확인이 필요한 작업은 `permission_mode=default` 로
> 등록하세요 — 그쪽은 이 목록과 무관하게 모든 툴이 승인을 거칩니다.

#### 게이트를 SDK 에 연결하는 방법 — 실측으로 고친 부분

초기 구현은 `canUseTool` 콜백만 썼고, **게이트가 통째로 동작하지 않았습니다.**
실제 연결 테스트에서 `permission_mode: default` + `allowed_tools: ['Read']` 인데도
`git status; git diff` 가 승인 없이 실행됐습니다. 원인이 둘이었습니다.

1. `settingSources` 를 지정하지 않아 이 PC 의 `~/.claude/settings.json` 에 쌓인
   허용 규칙이 적용됐습니다. 승인 흐름이 아예 발생하지 않으니 콜백도 불리지 않습니다.
2. `canUseTool` 은 권한 흐름이 "프롬프트까지 내려오는" 경우에만 호출됩니다.
   허용으로 판정되면 조용히 지나갑니다.

그래서 현재는 이렇게 둡니다. **어느 한 줄도 편의를 위해 바꾸지 마세요.**

```ts
permissionMode: 'default',
settingSources: [],        // ← 파일시스템 설정을 읽지 않는다(격리)
allowedTools: [],          // ← 여기 넣은 툴은 게이트를 건너뛴다
disallowedTools: disallowed,
hooks: { PreToolUse: [ /* ← 실제 게이트는 여기 */ ] },
canUseTool: async (...) => { /* 2차 방어선 */ },
```

`settingSources: []` 의 대가로 **`CLAUDE.md` 도 자동 로드되지 않습니다.**
격리를 푸는 대신 `src/project-context.ts` 가 `CLAUDE.md`(없으면 `.claude/CLAUDE.md`)를
직접 읽어 프롬프트에 넣습니다. 16KB 를 넘으면 앞부분만 싣고 잘렸음을 알립니다.
세션을 교체해도 매번 다시 넣습니다 — 새 세션은 이전 맥락을 물려받지 않기 때문입니다.

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

웹의 **관리자 › 담당자 › 토큰 재발급**. 기존 토큰은 즉시 무효가 되므로
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
npm test         # 빌드 후 dist/**/*.test.js 실행
```

고정해 둔 것: 샌드박스 차단 목록, 승인 게이트(자동 승인 목록 경계와
통신 실패 시 거부), 토큰 집계 산식(**덮어쓰기**), 인수인계 트리거 판정·문서 검증·
데몬 폴백, 입력 큐 잠금/보류/재개, `CLAUDE.md` 주입.

> **`npm test` 의 글로브를 `node --test dist` 로 바꾸지 마세요.** Node 가 디렉터리를
> `dist/index.js` 로 해석해 테스트 대신 **진짜 데몬을 운영 서버에 붙여 기동합니다.**
> (실제로 한 번 그렇게 떴습니다.)

테스트는 `src/test-env.ts` 를 먼저 임포트해 필수 환경변수를 고정합니다. 이걸 빼면
운영자의 실제 `.env` 를 읽어 PC 마다 결과가 달라집니다.

## 알려진 제약

- **CLI 폴백 미구현.** 명세는 `@anthropic-ai/claude-agent-sdk` 설치 불가 시
  `claude -p --output-format stream-json` 을 child_process 로 띄우는 어댑터를
  대안으로 두었습니다. 현재 SDK 가 정상 설치되므로 만들지 않았습니다.
  `SessionAdapter` 인터페이스는 그대로라 필요해지면 추가할 수 있습니다. 다만 그
  어댑터는 승인 게이트를 지원하지 못하므로(`supportsPermissions = false`),
  `permission_mode=default` job 은 아예 받을 수 없습니다.
- **같은 폴더 동시 실행 불가.** `git worktree` 가 필요하며 v4 범위입니다.
- **IPv6 CIDR 미지원.** 허용 IP 목록의 CIDR 표기는 IPv4 만 계산합니다.
