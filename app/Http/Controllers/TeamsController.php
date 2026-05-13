<?php

namespace App\Http\Controllers;

use App\Models\TeamsSetting;
use App\Services\TeamsService;
use Illuminate\Http\Request;

class TeamsController extends Controller
{
    private function service(): TeamsService
    {
        return new TeamsService(TeamsSetting::current());
    }

    // ── 메인 화면 ──────────────────────────────────────────────

    public function index()
    {
        $setting = TeamsSetting::current();
        return view('teams.index', compact('setting'));
    }

    // ── 자격증명 저장 & 인증 ───────────────────────────────────

    public function verify(Request $request)
    {
        $request->validate([
            'tenant_id'     => 'required|string',
            'client_id'     => 'required|string',
            'client_secret' => 'required|string',
        ]);

        $setting = TeamsSetting::current();

        // 새 시크릿이 입력된 경우에만 암호화 저장
        $updates = [
            'tenant_id'   => trim($request->tenant_id),
            'client_id'   => trim($request->client_id),
            'is_verified' => false,
            'access_token'=> null,
        ];

        $secret = trim($request->client_secret);
        // 마스킹 값이 아닌 경우에만 업데이트
        if ($secret !== '••••••••••••••••') {
            $updates['client_secret'] = encrypt($secret);
        }

        $setting->update($updates);

        try {
            $org = (new TeamsService($setting->fresh()))->verify();
            return response()->json([
                'ok'   => true,
                'org'  => $org['displayName'] ?? $org['id'] ?? 'Microsoft 365 조직',
            ]);
        } catch (\Throwable $e) {
            $setting->update(['is_verified' => false]);
            \App\Models\SystemErrorLog::record($e, 'warning');
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    // ── Teams / 채널 목록 ──────────────────────────────────────

    public function teams()
    {
        try {
            return response()->json(['ok' => true, 'data' => $this->service()->listTeams()]);
        } catch (\Throwable $e) {
            \App\Models\SystemErrorLog::record($e, 'warning');
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function channels(string $teamId)
    {
        try {
            return response()->json(['ok' => true, 'data' => $this->service()->listChannels($teamId)]);
        } catch (\Throwable $e) {
            \App\Models\SystemErrorLog::record($e, 'warning');
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    // ── 채널 메시지 전송 ───────────────────────────────────────

    public function sendMessage(Request $request)
    {
        $request->validate([
            'team_id'    => 'required|string',
            'channel_id' => 'required|string',
            'message'    => 'required|string|max:5000',
        ]);

        try {
            $result = $this->service()->sendChannelMessage(
                $request->team_id,
                $request->channel_id,
                $request->message
            );
            return response()->json(['ok' => true, 'id' => $result['id'] ?? null]);
        } catch (\Throwable $e) {
            \App\Models\SystemErrorLog::record($e, 'warning');
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    // ── 채팅 생성 ─────────────────────────────────────────────

    public function createChat(Request $request)
    {
        $request->validate([
            'member_ids'   => 'required|array|min:1',
            'member_ids.*' => 'required|string',
            'topic'        => 'nullable|string|max:200',
        ]);

        try {
            $result = $this->service()->createChat($request->member_ids, $request->topic ?? '');
            return response()->json(['ok' => true, 'chat' => $result]);
        } catch (\Throwable $e) {
            \App\Models\SystemErrorLog::record($e, 'warning');
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    // ── 사용자 검색 ───────────────────────────────────────────

    public function searchUsers(Request $request)
    {
        $request->validate(['q' => 'required|string|min:2']);

        try {
            $users = $this->service()->searchUsers($request->q);
            return response()->json(['ok' => true, 'data' => $users]);
        } catch (\Throwable $e) {
            \App\Models\SystemErrorLog::record($e, 'warning');
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    // ── SharePoint 사이트 & 드라이브 ──────────────────────────

    public function sites()
    {
        try {
            return response()->json(['ok' => true, 'data' => $this->service()->listSites()]);
        } catch (\Throwable $e) {
            \App\Models\SystemErrorLog::record($e, 'warning');
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function drives(string $siteId)
    {
        try {
            return response()->json(['ok' => true, 'data' => $this->service()->listDrives($siteId)]);
        } catch (\Throwable $e) {
            \App\Models\SystemErrorLog::record($e, 'warning');
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    // ── 파일 업로드 ───────────────────────────────────────────

    public function uploadFile(Request $request)
    {
        $request->validate([
            'site_id'  => 'required|string',
            'drive_id' => 'required|string',
            'file'     => 'required|file|max:51200', // 50MB
        ]);

        $file     = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $content  = file_get_contents($file->getRealPath());
        $mime     = $file->getMimeType() ?: 'application/octet-stream';

        try {
            $result = $this->service()->uploadFile(
                $request->site_id,
                $request->drive_id,
                $fileName,
                $content,
                $mime
            );
            return response()->json([
                'ok'   => true,
                'name' => $result['name'] ?? $fileName,
                'url'  => $result['webUrl'] ?? null,
                'size' => $result['size'] ?? null,
            ]);
        } catch (\Throwable $e) {
            \App\Models\SystemErrorLog::record($e, 'warning');
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
