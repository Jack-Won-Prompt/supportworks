/**
 * 무조건 차단하는 명령 패턴.
 *
 * 이 목록은 완전한 방어가 아니다 — 셸 우회 수단은 무한하다. 사고 방지용이며,
 * 최종 방어선은 승인 카드와 "신뢰하는 사람만 지시를 등록할 수 있다"는 권한
 * 모델이다. 완전 격리가 필요하면 컨테이너/VM 이 답이다(v4 범위).
 */
export interface BlockRule {
    id: string;
    pattern: RegExp;
    reason: string;
}

export const BLOCK_RULES: BlockRule[] = [
    // ── 원격 저장소 ─────────────────────────────────────────────────────────
    {
        id: 'git-push',
        // `git -C <path> push`, `git -c k=v push`, `git --git-dir=... push` 형태를 모두 잡되,
        // `git commit -m "push 관련 수정"` 처럼 하위 명령이 push 가 아닌 경우는 통과시킨다.
        pattern: /\bgit\s+(?:(?:-[cC]|--\S+)\s+\S+\s+|--\S+=\S+\s+|-\w\s+)*push\b/i,
        reason: '이 작업 지시에서는 원격 push 가 금지되어 있습니다. 커밋까지만 하고 사람이 검토한 뒤 push 합니다.',
    },
    {
        id: 'git-remote-change',
        pattern: /\bgit\s+remote\s+(?:add|set-url|remove|rename)\b/i,
        reason: '원격 저장소 설정 변경은 허용되지 않습니다.',
    },

    // ── 광범위 삭제 ─────────────────────────────────────────────────────────
    {
        id: 'rm-root',
        pattern: /\brm\s+(?:-[a-zA-Z]*\s+)*(?:\/|~|\/\*|\$HOME)(?:\s|$)/,
        reason: '루트·홈 디렉터리 삭제는 허용되지 않습니다.',
    },
    {
        id: 'rm-recursive-force-absolute',
        // 작업 폴더 밖의 절대 경로를 지우려는 시도. 상대 경로는 cwd 고정으로 이미 갇혀 있다.
        pattern: /\brm\s+(?:-[a-zA-Z]*\s+)*(?:[A-Za-z]:[\\/]|\/(?:etc|usr|bin|var|home|Users|Windows|Program))/i,
        reason: '작업 폴더 밖의 경로를 삭제할 수 없습니다.',
    },
    {
        id: 'format-disk',
        pattern: /\b(?:mkfs(?:\.\w+)?|format\s+[a-zA-Z]:|diskpart)\b/i,
        reason: '디스크 포맷 명령은 허용되지 않습니다.',
    },

    // ── 원격 코드 실행 ──────────────────────────────────────────────────────
    {
        id: 'pipe-to-shell',
        pattern: /\b(?:curl|wget)\b[^|]*\|\s*(?:sudo\s+)?(?:ba|z|k|d)?sh\b/i,
        reason: '내려받은 스크립트를 바로 실행하는 것은 허용되지 않습니다. 파일로 받아 내용을 확인한 뒤 실행하세요.',
    },
    {
        id: 'pipe-to-iex',
        pattern: /\b(?:iwr|irm|Invoke-WebRequest|Invoke-RestMethod)\b[^|]*\|\s*(?:iex|Invoke-Expression)\b/i,
        reason: '내려받은 스크립트를 바로 실행하는 것은 허용되지 않습니다.',
    },

    // ── 자격증명 ────────────────────────────────────────────────────────────
    {
        id: 'ssh-keys',
        pattern: /(?:~|\$HOME|%USERPROFILE%)[\\/]\.ssh\b|[\\/]\.ssh[\\/]id_/i,
        reason: 'SSH 키에는 접근할 수 없습니다.',
    },
    {
        // 확장자만 보고 막는다. 경로는 어디든 될 수 있다 — 실제로 이 PC 에는
        // 운영 서버 11대의 .ppk 가 바탕화면 폴더에 있었고, ~/.ssh 만 보던
        // 규칙으로는 하나도 걸리지 않았다.
        //
        // Bash 는 폴더 경계 검사를 받지 않는다(그건 파일 툴 전용이다). 그래서
        // 자동 승인된 Bash 가 지시 한 줄로 개인키를 읽어낼 수 있었다.
        id: 'private-key-file',
        pattern: /\.(?:ppk|pem|p12|pfx|key)\b|BEGIN\s+(?:[A-Z ]+)?PRIVATE\s+KEY/i,
        reason: '개인키 파일에는 접근할 수 없습니다.',
    },
    {
        id: 'cloud-credentials',
        pattern: /(?:~|\$HOME|%USERPROFILE%)[\\/]\.(?:aws|azure|gcloud|kube)\b/i,
        reason: '클라우드 자격증명에는 접근할 수 없습니다.',
    },
    {
        id: 'claude-credentials',
        pattern: /(?:~|\$HOME|%USERPROFILE%)[\\/]\.claude(?:\.json)?\b|\.credentials\.json\b/i,
        reason: 'Claude 자격증명 파일에는 접근할 수 없습니다.',
    },
    {
        id: 'browser-profile',
        pattern: /(?:AppData[\\/]+(?:Local|Roaming)[\\/]+(?:Google|Microsoft|Mozilla)|Library[\\/]+Application Support[\\/]+(?:Google|Firefox)|\.config[\\/]+(?:google-chrome|chromium))/i,
        reason: '브라우저 프로필에는 접근할 수 없습니다.',
    },

    // ── 시스템 ──────────────────────────────────────────────────────────────
    {
        id: 'shutdown',
        pattern: /\b(?:shutdown|reboot|halt|poweroff)\b/i,
        reason: '시스템 전원 명령은 허용되지 않습니다.',
    },
    {
        id: 'registry-write',
        pattern: /\breg\s+(?:add|delete|import)\b/i,
        reason: '레지스트리 변경은 허용되지 않습니다.',
    },
    {
        id: 'user-management',
        pattern: /\b(?:net\s+user|useradd|usermod|userdel|passwd)\b/i,
        reason: '사용자 계정 관리 명령은 허용되지 않습니다.',
    },
];

/**
 * 작업 폴더 안의 .env 는 읽어야 할 때가 있다(설정 확인). 그 밖의 .env 는 막는다.
 * 경로 검사(realpath ROOT 하위)가 1차 방어이고, 이건 명령 문자열 수준의 보조 검사다.
 */
export const ENV_FILE_PATTERN = /(?:^|[\s'"=])(?:[A-Za-z]:[\\/]|\/|~[\\/]|\.\.[\\/])[^\s'"]*\.env\b/;

export function findBlockedRule(command: string): BlockRule | null {
    for (const rule of BLOCK_RULES) {
        if (rule.pattern.test(command)) {
            return rule;
        }
    }

    if (ENV_FILE_PATTERN.test(command)) {
        return {
            id: 'external-env-file',
            pattern: ENV_FILE_PATTERN,
            reason: '작업 폴더 밖의 .env 파일에는 접근할 수 없습니다.',
        };
    }

    return null;
}
