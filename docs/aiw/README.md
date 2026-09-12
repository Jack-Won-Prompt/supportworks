# AI Works (AI 작업 지시)

SupportWorks 웹에서 작업을 지시하면, 사용자 PC에 설치된 데몬이 그 지시를 받아
**Claude Agent SDK**로 실행하고, 진행 상황과 결과를 실시간으로 웹에 되돌린다.

- 서버는 **지시와 승인을 중개**할 뿐 코드를 직접 만지지 않는다.
- 코드 수정은 **전적으로 사용자 PC의 작업 폴더 안**에서 일어난다.
- 서버와 PC는 HTTPS(REST) + WebSocket(Reverb) 두 경로로 이어진다.

관련 문서: [Reverb 운영 배포 절차](reverb-deploy.md) · [데몬 설치·운영](../../tools/aiw-agent/README.md)

---

## 1. 구조

```
┌─────────────────────────────────────────┐        ┌──────────────────────────────┐
│  브라우저 (supportworks.co.kr)           │        │  작업 PC (사내/개인)           │
│                                          │        │                              │
│  projects/{p}/ai-works/{job}             │        │  aiw-agent 데몬 (Node 20+)    │
│   · 지시 입력 · 로그 · 승인 카드          │        │   ├ JobManager (p-queue)      │
│   · 컨텍스트 게이지 · 비용 게이지          │        │   ├ SessionManager           │
└───────┬──────────────────────▲──────────┘        │   ├ PermissionGate            │
        │ HTTP                 │ WS                │   ├ Sandbox                   │
        │                      │ (private-aiw.job.*)│   └ SdkSessionAdapter         │
        ▼                      │                    │          │                   │
┌──────────────────────────────┴──────────┐        │          ▼                   │
│  Laravel 12                              │        │  @anthropic-ai/              │
│   AiwJobController  (웹 화면)            │        │   claude-agent-sdk           │
│   DaemonController  (데몬 인증/폴링)      │        │          │                   │
│   JobReportController (데몬 보고)         │        │          ▼                   │
│   ├ JobStateMachine  ← 상태 변경 유일 경로│        │  작업 폴더 (local_path)       │
│   ├ ToolPolicy / PermissionService       │        │   · 여기 안에서만 읽고 쓴다    │
│   ├ CostGuard / HandoverService          │        └──────────────────────────────┘
│   └ JobDispatcher                        │                   ▲
│                                          │  WS (private-aiw.agent.{id})
│  Laravel Reverb (127.0.0.1:8080)  ───────┼───────────────────┘
│   ※ 기존 Pusher Cloud와 병행 운용          │
└──────────────────────────────────────────┘
```

### 왜 Reverb와 Pusher를 같이 쓰는가

기존 채팅·협업·문의·분석 세션 8개 채널이 전부 Pusher Cloud에 물려 있다.
AI Works는 로그가 초당 수십 건 흐르므로 Pusher 무료·유료 한도를 빠르게 소진한다.
그래서 AI Works **전용으로만** self-host Reverb를 띄우고, `BROADCAST_CONNECTION`은
`pusher`로 유지한다. 자세한 제약은 [reverb-deploy.md](reverb-deploy.md) 참조.

### 주요 파일

| 역할 | 위치 |
|---|---|
| 상태 전이 규칙 | `app/Enums/AiWork/AiwJobStatus.php` |
| 상태 변경 실행(유일 경로) | `app/Services/AiWork/JobStateMachine.php` |
| 툴 정책(자동 승인 가능 목록) | `app/Services/AiWork/ToolPolicy.php` |
| 승인 요청·결정 | `app/Services/AiWork/PermissionService.php` |
| 비용 상한 | `app/Services/AiWork/CostGuard.php` |
| 인수인계 기록 | `app/Services/AiWork/HandoverService.php` |
| 채널 인가(Reverb 전용) | `routes/channels.php` 하단, `routes/web.php`의 `/aiw/broadcasting/auth` |
| 데몬 | `tools/aiw-agent/` |

---

## 2. 상태 전이

```
                    ┌───────────────────────────────────────┐
                    │                                       │
  [queued] ──▶ [dispatched] ──▶ [running] ◀──┬── [waiting_input]
      │             │               │  ▲     │
      │             │               │  └─────┼── [waiting_permission]
      │             │               │        │
      │             │               │  ┌─────┴── [handover]
      │             │               │  │
      │             │               ▼  ▼
      └─────────────┴──────────▶ [completed] / [failed] / [cancelled]   ← 종료(전이 불가)
```

| 상태 | 뜻 | 갈 수 있는 곳 |
|---|---|---|
| `queued` | 지시가 만들어졌고 데몬에 아직 안 갔다 | dispatched, cancelled, failed |
| `dispatched` | 데몬에 전달됐고 세션 시작을 기다린다 | running, cancelled, failed |
| `running` | 세션이 돌고 있다 | waiting_input, waiting_permission, handover, 종료 3종 |
| `waiting_input` | interactive 모드에서 사용자 답변 대기 | running, 종료 3종 |
| `waiting_permission` | 승인 카드를 띄우고 사람의 결정 대기 | running, 종료 3종 |
| `handover` | 컨텍스트가 차서 세션 교체 중 | running, 종료 3종 |
| `completed` / `failed` / `cancelled` | 종료 | 없음 |

두 가지 규칙이 이 표에 안 드러나므로 따로 적는다.

- **같은 상태로의 전이는 no-op으로 허용한다.** 데몬은 `running`을 유지한 채 매 턴
  `context_tokens`/`cost_usd`만 갱신한다. 이걸 막으면 정상 흐름이 409가 된다.
  단, 종료 상태는 닫아 둔다 — 이미 끝난 job을 다시 종료 처리할 이유가 없다.
- **종료 상태에서 되돌아오는 길은 없다.** 이어서 하려면 후속 job을 만든다
  (화면의 `redispatch`가 이 일을 한다).

상태를 바꾸는 코드는 `JobStateMachine`만이다. 컨트롤러·커맨드·이벤트 리스너 어디서도
`$job->status = ...`를 직접 쓰지 않는다. 전이표를 우회하는 경로가 하나라도 생기면
표는 문서가 아니라 거짓말이 된다.

---

## 3. 이벤트

전부 `ShouldBroadcastNow`(큐를 타지 않는다 — 로그는 늦게 도착하면 의미가 없다),
전부 **Reverb 커넥션**, 전부 private 채널.

### `private-aiw.job.{jobId}` — 서버 → 브라우저

인가: **시스템 관리자만**. `routes/channels.php`의 콜백이 `AiwJobPolicy::view` 를 그대로 호출한다 — 화면과 채널이 따로 놀지 않게 한 곳에서만 판단한다.

| 이벤트명 | 클래스 | 언제 |
|---|---|---|
| `job.status-changed` | `JobStatusChanged` | 상태가 바뀔 때마다 |
| `log.appended` | `JobLogAppended` | 데몬이 로그 배치를 올릴 때 |
| `message.appended` | `JobMessageAppended` | assistant 응답·인수인계 요약 |
| `permission.requested` | `PermissionRequested` | 승인 카드를 띄워야 할 때 |

### `private-aiw.agent.{agentId}` — 서버 → 데몬

인가: **채널 콜백이 아니라** `/api/aiw/broadcasting/auth`에서 에이전트 토큰을 확인한 뒤
직접 서명한다. 데몬에는 로그인 사용자가 없어 콜백 방식을 탈 수 없다.

| 이벤트명 | 클래스 | 언제 |
|---|---|---|
| `job.dispatched` | `JobDispatched` | 새 작업을 보낼 때 |
| `job.user-message` | `JobUserMessage` | 사용자가 대화 입력을 보냈을 때 |
| `permission.decided` | `PermissionDecided` | 사람이 허용/거부를 눌렀을 때 |
| `handover.requested` | `HandoverRequested` | 사용자가 "컨텍스트 정리"를 눌렀을 때 |
| `job.cancel-requested` | `JobCancelRequested` | 취소 |
| `job.end-requested` | `JobEndRequested` | 세션 종료 |

### 이벤트를 놓쳐도 멈추지 않는다

WebSocket은 끊긴다. 그래서 데몬은 **모든 상태성 응답에 실린 제어 플래그**
(`cancel_requested`, `cost_over_limit`)로 따라잡고, 승인을 기다리는 동안에는
`/api/aiw/jobs/{job}/inbox`를 `INBOX_POLL_SEC`(기본 30초)마다 폴링한다.
Reverb가 통째로 죽어도 작업은 느려질 뿐 잘못되지는 않는다.

---

## 4. 보안 모델

### 4.1 층

```
   ①  에이전트 인증        토큰(SHA-256 해시로만 저장) + IP 허용목록
   ②  폴더 경계           local_path 매핑. 데몬이 cwd를 고정하고 밖은 거부
   ③  샌드박스 규칙        sandbox.rules.ts — 걸리면 서버에 묻지도 않고 거부
   ④  승인 게이트         PreToolUse 훅 → 사람이 허용/거부
   ⑤  SDK 격리           settingSources: [] — PC에 쌓인 허용 규칙을 읽지 않는다
   ⑥  비용·컨텍스트 상한   CostGuard / 인수인계
```

**순서가 중요하다.** ③이 ④보다 먼저다. 샌드박스에 걸리는 것들은
사람이 실수로 [허용]을 눌러도 통과하지 못해야 하는 것들이다.

### 4.2 절대 규칙

- **`permission_mode=default`는 목록과 무관하게 모든 툴이 승인을 거친다.**
  매번 사람이 확인해야 하는 작업은 이 모드로 등록한다.
- **`acceptEdits`의 자동 승인 범위는 `config('aiw.auto_approvable')`가 정한다.**
  현재 `Read`/`Edit`/`Write`/**`Bash`**/`Glob`/`Grep`이다. 서버(`ToolPolicy`)와
  데몬(`PermissionGate`)이 같은 목록을 각자 들고 있고, 양쪽 모두 테스트로 고정돼 있다.
  Bash 포함이 무엇을 뜻하는지는 4.4절 1번 항목을 볼 것.
- **서버에 승인을 물을 수 없으면 거부한다.** 네트워크 오류 시 통과가 아니라 차단이다.
- **`settingSources: []`를 풀지 않는다.** 이 값을 비워 두지 않으면 그 PC의
  `~/.claude/settings.json`에 쌓인 허용 규칙이 적용되어 `canUseTool`이 아예 호출되지
  않고 샌드박스가 통째로 우회된다. 실측으로 확인한 사실이다.
  그래서 `CLAUDE.md`는 SDK에 맡기지 않고 `project-context.ts`가 직접 읽어 프롬프트에 넣는다.
- **승인 게이트는 `PreToolUse` 훅에 있다.** `canUseTool` 콜백만으로는 부족하다 —
  권한 흐름이 "프롬프트까지 내려오는" 경우에만 호출되기 때문이다. `canUseTool`도
  남겨 두지만 그것은 2차 방어선이다.

### 4.3 샌드박스가 막는 것

`tools/aiw-agent/src/sandbox.rules.ts`에 규칙과 이유가 함께 있다. 요약하면:

| 분류 | 예 |
|---|---|
| 원격 반영 | `git push`, `git remote set-url` |
| 광범위 삭제 | `rm -rf /`, `rm -rf ~`, 절대경로 강제 삭제 |
| 내려받은 스크립트 실행 | `curl … \| sh`, `iwr … \| iex` |
| 자격증명 접근 | `~/.ssh/*`, `~/.aws/credentials`, `~/.claude.json` |
| 작업 폴더 밖 `.env` | `cat ../secret/.env` |
| 시스템 변경 | `shutdown`, `reg add`, `net user` |

명령 검사는 **툴 이름이 아니라 입력 모양**으로 한다(`input.command`가 문자열이면 검사).
`Bash`라는 이름에만 걸면 같은 일을 하는 다른 툴이 생겼을 때 조용히 뚫린다.

경로 검사는 별도로 `local_path` 밖을 모두 거부한다.

### 4.4 이 모델의 한계 — 반드시 읽을 것

이 시스템은 **신뢰할 수 있는 사람이 신뢰할 수 있는 저장소에서** 쓰는 것을 전제한다.
아래는 막지 못한다.

1. **`acceptEdits`에서는 사람이 명령을 보는 지점이 없다.** Bash가 자동 승인 목록에
   있으므로(운영자 결정, 2026-09-11), 이 모드로 등록된 job은 임의 명령을 확인 없이
   실행한다. 남는 방어선은 샌드박스 차단 목록과 작업 폴더 경계뿐이고, 둘 다 셸 우회를
   전부 막지는 못한다. 확인이 필요한 작업은 `default` 모드로 등록한다.
   그리고 승인을 받더라도 **명령 안의 코드까지 검사되지는 않는다** — `npm test`를
   허용하면 그 스크립트가 무엇이든 실행된다. 샌드박스는 명령 문자열을 보지,
   그 명령이 실행할 코드를 보지 않는다.
2. **작업 폴더 안의 파괴.** 폴더 경계는 밖을 지키지, 안을 지키지 않는다.
   커밋되지 않은 작업물은 날아갈 수 있다. `use_branch`를 켜서 작업 브랜치에서 돌리고,
   중요한 변경은 시작 전에 커밋해 둔다.
3. **프롬프트 인젝션.** 저장소 안의 파일이나 웹에서 읽은 내용이 Claude에게
   지시처럼 작용할 수 있다. 승인 게이트가 실행 직전에 사람에게 묻는 것이 이 경우의
   마지막 방어선이므로, **승인 카드를 습관적으로 누르지 않는 것**이 실제 보안의 핵심이다.
4. **데몬 PC 자체의 권한.** 데몬은 그 PC 사용자 권한으로 돈다.
   → **운영 서버나 운영 DB에 접근 가능한 PC에는 설치하지 않는다.**
   이 경고는 설정 화면과 데몬 README에도 같이 있다.
5. **비용 상한은 사후적이다.** 턴이 끝나야 집계되므로 한 턴 안에서 상한을 넘을 수 있다.
   상한은 폭주를 멈추는 장치이지 정확한 지갑이 아니다.

### 4.5 비용 표기

데몬은 두 가지 방식으로 인증될 수 있고, 표기가 달라진다.

| `auth_mode` | 인증 | 게이지 이름 | 의미 |
|---|---|---|---|
| `api_key` | `ANTHROPIC_API_KEY` | **비용** | 키 소유 계정에 실제 청구되는 금액 |
| `subscription` | 그 PC의 Claude Code 로그인 | **예상 사용량** | 실제 청구액이 아닌 추정치 |

현재 운영은 `subscription`이다(`ANTHROPIC_API_KEY`를 쓰지 않는다).
`AiwAgent::costLabel()`이 이 판단을 한 곳에서 한다.

---

## 5. 운영 절차

### 5.1 작업 PC 등록

1. `설정 → 작업 PC`에서 PC를 추가한다.
2. **토큰은 이때 한 번만 보인다.** SHA-256 해시만 저장되므로 다시 볼 수 없다.
   잃어버리면 재발급한다. 토큰을 채팅·이슈·문서에 붙여 넣지 않는다.
3. 프로젝트 ↔ 로컬 폴더 매핑을 추가한다. 이 매핑에 없는 폴더는 쓸 수 없다.
4. PC에서 데몬을 설치·기동한다 → [tools/aiw-agent/README.md](../../tools/aiw-agent/README.md)

### 5.2 작업 지시

1. `프로젝트 → AI 작업`에서 새 지시를 만든다.
2. 모드를 고른다.
   - `batch`: 지시 하나를 끝까지 수행하고 완료한다.
   - `interactive`: 턴마다 사용자 입력을 기다린다.
3. 허용 툴과 `permission_mode`를 고른다. `Bash`가 포함되면 모드와 무관하게 매번 묻는다.
4. 실행 중에는 로그·컨텍스트 게이지·비용 게이지가 실시간으로 갱신된다.

### 5.3 컨텍스트 정리(인수인계)

컨텍스트가 한도의 `CONTEXT_HANDOVER_RATIO`(기본 60%)에 닿으면 데몬이 자동으로,
또는 사용자가 버튼을 눌러 수동으로 세션을 교체한다.

교체는 **턴 경계에서만** 일어난다(툴 실행 중에 끊지 않는다). 절차는:

1. 현재 세션에 고정 7개 섹션짜리 인수인계 문서를 쓰게 한다
   (`docs/aiw/handover/job-{id}-{n}.md`). 최대 2회 시도.
2. 형식을 검증한다. 미달이면 **데몬이 최소 요약을 대신 쓴다**
   (`<!-- daemon-generated: true -->` 표기가 붙는다 — 품질을 오해하지 말 것).
3. 세션을 닫고 새 세션을 연다. `resume`을 쓰지 않고 **문서로만** 맥락을 잇는다.
4. 교체 중 도착한 사용자 메시지는 보류했다가 순서대로 새 세션에 주입한다.

배치 모드에서 작업이 끝났다면 인수인계보다 **완료가 우선**이다.

### 5.4 멈추는 방법 — 자동·수동

지시 한 줄이 작업 PC 의 소스를 고치므로, **어떻게 멈추고 어디로 되돌아가는지**가
기능의 일부다.

| 장치 | 무엇이 재나 | 기본값 | 끌 수 있나 |
|---|---|---|---|
| 비용 상한 | 누적 환산 금액 | 프로젝트 설정값 | 끌 수 있다("제한 없음") |
| 작업 시간 | **실행 시간 누적** (사람을 기다린 시간 제외) | `JOB_TIMEOUT_SEC` 30분 | 0 이면 끈다 |
| 무응답 | 실행 중인데 출력이 없는 시간 | `IDLE_TIMEOUT_SEC` 5분 | 0 이면 끈다 |
| 총 수명 | job 시작부터의 총 경과(대기 포함) | `SESSION_MAX_SEC` 2시간 | 0 이면 끈다 |
| 취소 버튼 | — | — | 사람이 누른다 |

사람의 입력이나 승인을 기다리는 동안은 작업 시간·무응답을 세지 않는다. 담당자가
한참 뒤에 답하는 것은 폭주가 아니다. 구현은
`tools/aiw-agent/src/time-limits.ts`(시간 계산은 순수 함수로 분리해 시험한다).

**중단되면 작업 폴더를 되돌린다.** 브랜치 분리를 켠 작업에 한해:

1. 중단 시점의 변경을 작업 브랜치(`aiw/job-{id}`)에 커밋으로 **남긴다**
2. 기본 브랜치로 체크아웃한다 → 작업 폴더가 수정 이전 상태가 된다
3. 무엇을 되돌렸고 어디에 남겼는지 `role=system` 메시지로 대화창에 알린다

`reset --hard` 도 `clean` 도 쓰지 않는다 — 버리는 것이 아니라 옮겨 두는 것이다.
브랜치 분리를 **끈** 작업은 되돌리지 않는다. 시작 시점에 폴더가 깨끗했다는 보장이
없어, 되돌리면 사람이 하던 작업까지 지운다(그 사실도 메시지로 알린다).

이 정리가 없던 시절에는 미커밋 변경이 남아 다음 지시가 `dirty_tree` 로 시작조차
못 했고, 네 개 저장소를 사람이 손으로 정리해야 했다.

이미 **올리기(publish)를 눌러 원격에 올라간 변경**과 **실행된 배포**는 이 복구의
대상이 아니다. 그것들은 revert 커밋과 재배포로만 되돌릴 수 있다.

### 5.5 정기 작업 (스케줄러)

| 커맨드 | 주기 | 하는 일 |
|---|---|---|
| `aiw:expire-permissions` | 매분 | 시간 초과된 승인 요청을 만료 처리 |
| `aiw:reap-stale-jobs` | 매분 | 데몬이 사라진 job을 실패 처리 |
| `aiw:prune` | 매일 03:00 | 오래된 로그·메시지 정리 |

세 개 모두 스케줄러가 살아 있어야 동작한다.
`CACHE_STORE=file`인 서버에서는 `routes/console.php`의 `Schedule::useCache('database')`
가드가 필수다 — 파일 스토어의 락은 샤딩 디렉터리를 만들지 않아 `fopen`이 실패하고
`schedule:run` **전체**가 예외로 죽는다(다른 사이트의 스케줄까지 함께).

---

## 6. 장애 대응

| 증상 | 먼저 볼 것 | 조치 |
|---|---|---|
| 지시를 만들었는데 `queued`에서 안 움직인다 | 작업 PC가 온라인인가 (`last_seen_at`) | 데몬 기동 상태 확인. 데몬 로그에 "서버 연결됨"이 있는지 |
| `dispatched`에서 멈춘다 | 데몬 로그 | 대개 `local_path`가 잘못됐다. 경로에 백슬래시 이스케이프 사고가 없는지 확인(슬래시로 저장하는 편이 안전) |
| 화면이 실시간으로 안 갱신된다 | 브라우저 콘솔의 WS 연결 | 403이면 `routes/channels.php`의 reverb 등록 확인. 연결은 되는데 조용하면 `REVERB_*` 변경 후 `npm run build` 누락 |
| 승인 카드가 안 뜬다 | 데몬 버전 | `PreToolUse` 훅이 있는 버전인지 확인. 훅 없이 `canUseTool`만 있으면 게이트가 통째로 무력하다 |
| 툴이 승인 없이 실행됐다 | **즉시 데몬 정지** | `settingSources: []`가 풀렸는지 확인. 이건 사고다 |
| 인수인계가 자꾸 터진다 | 토큰 집계 산식 | `contextTokens`는 **마지막 assistant 메시지 기준**이어야 한다. `result`의 누적 usage를 쓰면 수 배로 부풀어 조기·반복 발동한다 |
| 비용이 이상하다 | `auth_mode` | `subscription`이면 그 값은 추정치다. 4.5절 참조 |
| 작업이 좀비로 남는다 | 스케줄러 | `aiw:reap-stale-jobs`가 도는지. 5.5절 참조 |
| 전 페이지 500 | `REVERB_*` 미설정 + 가드 제거 | `routes/channels.php`의 3값 가드를 복원한다 |

### 로그 위치

| 무엇 | 어디 |
|---|---|
| 서버 애플리케이션 | `storage/logs/laravel.log` |
| Reverb | `storage/logs/reverb.log` |
| 스케줄러 | `storage/logs/schedule.log` |
| 데몬(전체) | 데몬 PC의 `LOG_DIR`(기본 `./logs`) |
| job 단위 원본 | 데몬 PC의 `LOG_DIR/job-{id}.jsonl` — 서버로 올라간 `raw`는 4KB로 잘리므로 전문은 여기에만 있다 |

---

## 7. 테스트

```bash
# 서버
php artisan test --filter=AiWork

# 데몬
cd tools/aiw-agent && npm test
```

데몬 테스트는 `node --test "dist/**/*.test.js"`로 돌린다.
**`node --test dist`로 바꾸지 말 것** — Node가 디렉터리를 `dist/index.js`로 해석해
테스트 대신 **진짜 데몬을 운영 서버에 붙여 기동한다.**

고정해 둔 것: 상태 전이표, 툴 정책(default 는 전부 승인·acceptEdits 는 목록만), 채널 인가(403 회귀),
토큰 집계 산식(덮어쓰기), 인수인계 트리거·문서 검증·폴백, 입력 큐 잠금/보류/재개,
샌드박스 차단 목록, `CLAUDE.md` 주입.
