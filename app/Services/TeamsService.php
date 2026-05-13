<?php

namespace App\Services;

use App\Models\TeamsSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TeamsService
{
    private const GRAPH = 'https://graph.microsoft.com/v1.0';
    private const LOGIN = 'https://login.microsoftonline.com';

    private TeamsSetting $setting;
    private string $token;

    public function __construct(TeamsSetting $setting)
    {
        $this->setting = $setting;
    }

    // ── 인증 토큰 취득 ─────────────────────────────────────────

    public function fetchToken(): void
    {
        $res = Http::withOptions(['verify' => false])
            ->asForm()
            ->post(self::LOGIN . "/{$this->setting->tenant_id}/oauth2/v2.0/token", [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->setting->client_id,
                'client_secret' => $this->setting->getDecryptedSecret(),
                'scope'         => 'https://graph.microsoft.com/.default',
            ]);

        if (!$res->successful()) {
            $err = $res->json('error_description') ?? $res->body();
            throw new \RuntimeException("토큰 발급 실패: {$err}");
        }

        $data      = $res->json();
        $expiresAt = now()->addSeconds($data['expires_in'] - 60);

        $this->setting->update([
            'access_token'    => encrypt($data['access_token']),
            'token_expires_at'=> $expiresAt,
            'is_verified'     => true,
        ]);

        $this->token = $data['access_token'];
    }

    private function token(): string
    {
        if (isset($this->token)) return $this->token;

        if ($this->setting->isTokenValid()) {
            return $this->token = $this->setting->getDecryptedToken();
        }

        $this->fetchToken();
        return $this->token;
    }

    // ── 공통 Graph API 호출 ────────────────────────────────────

    private function get(string $path, array $query = []): array
    {
        $res = Http::withOptions(['verify' => false])
            ->withToken($this->token())
            ->get(self::GRAPH . $path, $query);

        if (!$res->successful()) {
            $msg = $res->json('error.message') ?? $res->body();
            throw new \RuntimeException($msg);
        }

        return $res->json();
    }

    private function post(string $path, array $body): array
    {
        $res = Http::withOptions(['verify' => false])
            ->withToken($this->token())
            ->post(self::GRAPH . $path, $body);

        if (!$res->successful()) {
            $msg = $res->json('error.message') ?? $res->body();
            throw new \RuntimeException($msg);
        }

        return $res->json();
    }

    private function put(string $path, string $content, string $mime): array
    {
        $res = Http::withOptions(['verify' => false])
            ->withToken($this->token())
            ->withHeaders(['Content-Type' => $mime])
            ->withBody($content, $mime)
            ->put(self::GRAPH . $path);

        if (!$res->successful()) {
            $msg = $res->json('error.message') ?? $res->body();
            throw new \RuntimeException($msg);
        }

        return $res->json();
    }

    // ── 인증 확인 ──────────────────────────────────────────────

    public function verify(): array
    {
        $this->fetchToken();
        $org = $this->get('/organization');
        return $org['value'][0] ?? $org;
    }

    // ── Teams 목록 ─────────────────────────────────────────────

    public function listTeams(): array
    {
        $res = $this->get('/groups', [
            '$filter' => "resourceProvisioningOptions/Any(x:x eq 'Team')",
            '$select' => 'id,displayName,description',
            '$top'    => 50,
        ]);
        return $res['value'] ?? [];
    }

    // ── 채널 목록 ─────────────────────────────────────────────

    public function listChannels(string $teamId): array
    {
        $res = $this->get("/teams/{$teamId}/channels");
        return $res['value'] ?? [];
    }

    // ── 채널 메시지 전송 ───────────────────────────────────────

    public function sendChannelMessage(string $teamId, string $channelId, string $text): array
    {
        return $this->post("/teams/{$teamId}/channels/{$channelId}/messages", [
            'body' => ['contentType' => 'html', 'content' => nl2br(e($text))],
        ]);
    }

    // ── 채팅 생성 ─────────────────────────────────────────────

    public function createChat(array $memberIds, string $topic = ''): array
    {
        $members = array_map(fn($id) => [
            '@odata.type'     => '#microsoft.graph.aadUserConversationMember',
            'roles'           => ['owner'],
            'user@odata.bind' => "https://graph.microsoft.com/v1.0/users('{$id}')",
        ], $memberIds);

        $body = [
            'chatType' => count($memberIds) > 2 ? 'group' : 'oneOnOne',
            'members'  => $members,
        ];

        if ($topic && count($memberIds) > 2) {
            $body['topic'] = $topic;
        }

        return $this->post('/chats', $body);
    }

    // ── 사용자 검색 ───────────────────────────────────────────

    public function searchUsers(string $query): array
    {
        $res = Http::withOptions(['verify' => false])
            ->withToken($this->token())
            ->withHeaders(['ConsistencyLevel' => 'eventual'])
            ->get(self::GRAPH . '/users', [
                '$search'  => "\"displayName:{$query}\" OR \"mail:{$query}\"",
                '$select'  => 'id,displayName,mail,jobTitle,department,userPrincipalName',
                '$top'     => 20,
                '$orderby' => 'displayName',
            ]);

        if (!$res->successful()) {
            $msg = $res->json('error.message') ?? $res->body();
            throw new \RuntimeException($msg);
        }

        return $res->json('value') ?? [];
    }

    // ── SharePoint 사이트 목록 ─────────────────────────────────

    public function listSites(): array
    {
        $res = $this->get('/sites', ['search' => '*', '$top' => 20]);
        return $res['value'] ?? [];
    }

    // ── SharePoint 드라이브 목록 ───────────────────────────────

    public function listDrives(string $siteId): array
    {
        $res = $this->get("/sites/{$siteId}/drives");
        return $res['value'] ?? [];
    }

    // ── 파일 업로드 ───────────────────────────────────────────

    public function uploadFile(string $siteId, string $driveId, string $fileName, string $content, string $mime): array
    {
        return $this->put(
            "/sites/{$siteId}/drives/{$driveId}/root:/{$fileName}:/content",
            $content,
            $mime
        );
    }
}
