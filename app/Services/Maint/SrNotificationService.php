<?php

namespace App\Services\Maint;

use App\Mail\MaintRequestNoteNotificationMail;
use App\Models\CompanyGroup;
use App\Models\Maint\MaintRequest;
use App\Models\Maint\MaintRequestNote;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * SR 비고(노트) 작성 시 알림 발송.
 *
 * 웹·모바일 공통으로 호출 — 동일한 대상자에게 이메일(기존)과 FCM 푸시(추가)를
 * 동시에 발송한다. 대상자 결정 규칙은 웹 MaintRequestController 의 기존 로직과 동일.
 *
 * 대상자 규칙(웹 동일):
 *   - 콜로(요청자측) 비고만 알림 대상. 링크 비고는 알림 없음.
 *   - 새 콜로 비고 → 링크더랩 admin + SR 담당자(assignee)
 *   - 답글(작성자가 admin/SR 담당자) → SR 등록자(coloUser)
 *   - 본인 제외 / 중복 제거
 */
class SrNotificationService
{
    /**
     * @param User|null $poster 작성자 (인증된 사용자)
     */
    public static function notifyNoteAdded(MaintRequest $sr, MaintRequestNote $note, ?User $poster): void
    {
        if ($note->note_type !== 'colo') return;
        if (!$poster) return;

        $isAgent = $poster->isAdmin() || (bool) ($poster->is_sr_agent ?? false);

        $sr->loadMissing(['coloUser', 'assignee', 'companyGroup']);

        /** @var array<int, User> $recipients */
        $recipients = [];
        $event      = '비고';

        if ($note->parent_id) {
            // 답글 — SR 담당자/admin 이 단 경우에만 요청자에게 통지
            if (!$isAgent) return;
            $event = '답글';
            if ($requester = self::resolveRequester($sr)) {
                $recipients[] = $requester;
            }
        } else {
            // 새 콜로 비고 — 링크더랩 admin + SR 담당자(assignee)
            $recipients = array_merge(
                self::resolveLinkthelabAdmins(),
                array_filter([self::resolveSrAgent($sr)])
            );
        }

        // 본인 제외 + 중복 제거 (id 기준)
        $byId = [];
        foreach ($recipients as $u) {
            if ($u && $u->id !== $poster->id) {
                $byId[$u->id] = $u;
            }
        }
        $recipients = array_values($byId);
        if (empty($recipients)) return;

        // 1) 이메일 — 유효한 이메일만 추려서 발송 (웹 로직 보존)
        self::sendEmail($sr, $note, $recipients, $event, $poster);

        // 2) FCM — 모든 수신자에게 발송
        self::sendFcm($sr, $note, $recipients, $event, $poster);
    }

    private static function sendEmail(
        MaintRequest $sr,
        MaintRequestNote $note,
        array $recipients,
        string $event,
        User $poster,
    ): void {
        $self = strtolower((string) ($poster->email ?? ''));
        $emails = [];
        foreach ($recipients as $u) {
            $em = (string) ($u->email ?? '');
            if (!filter_var($em, FILTER_VALIDATE_EMAIL)) continue;
            if (strtolower($em) === $self) continue;
            $emails[] = $em;
        }
        $emails = array_values(array_unique($emails));
        if (empty($emails)) return;

        try {
            Mail::send(new MaintRequestNoteNotificationMail($sr, $note, $emails, $event, $poster->name ?? ''));
        } catch (\Throwable $e) {
            Log::warning('MaintRequestNote 이메일 알림 실패: ' . $e->getMessage(), [
                'note_id' => $note->id, 'sr_id' => $sr->id,
            ]);
        }
    }

    private static function sendFcm(
        MaintRequest $sr,
        MaintRequestNote $note,
        array $recipients,
        string $event,
        User $poster,
    ): void {
        $userIds = array_values(array_unique(array_map(fn (User $u) => (int) $u->id, $recipients)));
        if (empty($userIds)) return;

        $title = "SR #{$sr->id} {$event}";
        $bodySrc = trim((string) $note->body);
        $body = mb_substr($bodySrc, 0, 100);
        if (mb_strlen($bodySrc) > 100) $body .= '…';

        FcmService::notifyUsers($userIds, $title, $body, [
            'type'    => 'maint_request_note',
            'sr_id'   => (string) $sr->id,
            'note_id' => (string) $note->id,
            'event'   => $event,
        ]);
    }

    /**
     * @return array<int, User>
     */
    private static function resolveLinkthelabAdmins(): array
    {
        $linkthelabId = CompanyGroup::where('name', '링크더랩')->value('id');
        if (!$linkthelabId) return [];
        return User::where('company_group_id', $linkthelabId)
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->get()
            ->all();
    }

    private static function resolveSrAgent(MaintRequest $sr): ?User
    {
        if (!$sr->assignee) return null;
        return User::where('is_sr_agent', true)
            ->where('name', $sr->assignee->name)
            ->whereNotNull('email')
            ->first();
    }

    private static function resolveRequester(MaintRequest $sr): ?User
    {
        if (!$sr->coloUser || !$sr->company_group_id) return null;
        return User::where('company_group_id', $sr->company_group_id)
            ->where('name', $sr->coloUser->name)
            ->whereNotNull('email')
            ->first();
    }
}
