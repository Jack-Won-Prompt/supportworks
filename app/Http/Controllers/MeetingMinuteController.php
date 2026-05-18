<?php

namespace App\Http\Controllers;

use App\Mail\MeetingScheduledMail;
use App\Models\AiSetting;
use App\Models\MeetingActionItem;
use App\Models\MeetingMinute;
use App\Models\MeetingAttendee;
use App\Models\Project;
use App\Models\SystemErrorLog;
use App\Models\User;
use App\Services\AiOrchestrator;
use App\Services\DocxWriter;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class MeetingMinuteController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = MeetingMinute::companyOf($user)
            ->with(['author', 'project', 'attendees', 'actionItems']);

        if ($request->filled('status') && in_array($request->status, ['scheduled', 'completed'], true)) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('meeting_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('meeting_date', '<=', $request->date_to);
        }

        $minutes = $query->latest('meeting_date')->paginate(15)->withQueryString();

        $projects = Project::whereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->orderBy('name')->get(['id', 'name']);

        $stats = [
            'total'     => MeetingMinute::companyOf($user)->count(),
            'month'     => MeetingMinute::companyOf($user)->whereMonth('meeting_date', now()->month)->whereYear('meeting_date', now()->year)->count(),
            'scheduled' => MeetingMinute::companyOf($user)->where('status', 'scheduled')->where('meeting_date', '>=', now())->count(),
            'general'   => MeetingMinute::companyOf($user)->where('type', 'general')->count(),
            'project'   => MeetingMinute::companyOf($user)->where('type', 'project')->count(),
        ];

        $teammates = $this->projectTeammates($user);

        return view('meeting-minutes.index', compact('minutes', 'projects', 'stats', 'teammates'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'title'              => 'required|string|max:200',
            'type'               => 'nullable|in:general,project',
            'project_id'         => 'nullable|exists:projects,id',
            'project_code'       => 'nullable|string|max:100',
            'weekly_department'  => 'nullable|string|max:100',
            'meeting_date'       => 'required|date',
            'location'           => 'nullable|string|max:200',
            'agenda'             => 'nullable|string',
            'discussion'         => 'nullable|string',
            'decisions'          => 'nullable|string',
            'attendees'          => 'nullable|array',
            'attendees.*.user_id'=> 'nullable|exists:users,id',
            'attendees.*.name'   => 'nullable|string|max:100',
            'action_items'              => 'nullable|array',
            'action_items.*.id'         => 'nullable|integer',
            'action_items.*.title'      => 'nullable|string|max:200',
            'action_items.*.owner_id'   => 'nullable|exists:users,id',
            'action_items.*.due_date'   => 'nullable|date',
            'action_items.*.priority'   => 'nullable|in:high,medium,low',
        ]);

        $minute = MeetingMinute::create([
            ...$validated,
            'type'             => $request->filled('project_id') ? 'project' : 'general',
            'status'           => 'completed',
            'author_id'        => $user->id,
            'company_group_id' => $user->company_group_id,
        ]);

        $this->syncAttendees($minute, $request->input('attendees', []));
        $this->syncActionItems($minute, $request->input('action_items', []));
        $this->notifyMeetingAttendees($minute, false);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('meeting-minutes.show', $minute)
            ->with('success', '회의록이 등록되었습니다.');
    }

    /**
     * 회의 예정(스케줄) 등록 — 회의록 본문은 비워둔 상태로 status='scheduled' 저장.
     */
    public function storeSchedule(Request $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'title'              => 'required|string|max:200',
            'type'               => 'nullable|in:general,project',
            'project_id'         => 'nullable|exists:projects,id',
            'project_code'       => 'nullable|string|max:100',
            'weekly_department'  => 'nullable|string|max:100',
            'meeting_date'       => 'required|date',
            'location'           => 'nullable|string|max:200',
            'agenda'             => 'nullable|string',
            'attendees'          => 'nullable|array',
            'attendees.*.user_id'=> 'nullable|exists:users,id',
            'attendees.*.name'   => 'nullable|string|max:100',
        ]);

        $minute = MeetingMinute::create([
            ...$validated,
            'type'             => $request->filled('project_id') ? 'project' : 'general',
            'status'           => 'scheduled',
            'author_id'        => $user->id,
            'company_group_id' => $user->company_group_id,
        ]);

        $this->syncAttendees($minute, $request->input('attendees', []));
        $this->notifyMeetingAttendees($minute, false);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'id' => $minute->id]);
        }

        return redirect()->route('meeting-minutes.index')
            ->with('success', '회의 일정이 등록되었습니다.');
    }

    public function show(MeetingMinute $meetingMinute): View
    {
        $this->authorizeMinute($meetingMinute);

        $meetingMinute->load([
            'author', 'project',
            'attendees.user',
            'memos.user', 'memos.actionItems.owner',
            'actionItems.owner',
            'recordings.user',
        ]);

        $user = auth()->user();
        $teammates = $this->projectTeammates($user);

        return view('meeting-minutes.show', compact('meetingMinute', 'teammates'));
    }

    /** 회의 녹음 오디오 스트리밍 (인증된 사용자) */
    public function recordingAudio(MeetingMinute $meetingMinute, \App\Models\MeetingRecording $recording)
    {
        $this->authorizeMinute($meetingMinute);
        abort_if($recording->meeting_minute_id !== $meetingMinute->id, 404);
        abort_unless(\Illuminate\Support\Facades\Storage::exists($recording->file_path), 404);

        return \Illuminate\Support\Facades\Storage::response(
            $recording->file_path,
            $recording->original_name ?? basename($recording->file_path),
            ['Content-Type' => $recording->mime_type ?? 'audio/mp4']
        );
    }

    /** 회의 녹음 파일 다운로드 */
    public function recordingDownload(MeetingMinute $meetingMinute, \App\Models\MeetingRecording $recording)
    {
        $this->authorizeMinute($meetingMinute);
        abort_if($recording->meeting_minute_id !== $meetingMinute->id, 404);
        abort_unless(\Illuminate\Support\Facades\Storage::exists($recording->file_path), 404);

        return \Illuminate\Support\Facades\Storage::download(
            $recording->file_path,
            $recording->original_name ?? basename($recording->file_path)
        );
    }

    public function showPopup(MeetingMinute $meetingMinute): View
    {
        $this->authorizeMinute($meetingMinute);

        $meetingMinute->load([
            'author', 'project',
            'attendees.user',
            'memos.user', 'memos.actionItems.owner',
            'actionItems.owner',
        ]);

        $user = auth()->user();
        $teammates = $this->projectTeammates($user);

        return view('meeting-minutes.show_popup', compact('meetingMinute', 'teammates'));
    }

    public function update(Request $request, MeetingMinute $meetingMinute): RedirectResponse|JsonResponse
    {
        $this->authorizeMinute($meetingMinute, 'author');

        $validated = $request->validate([
            'title'              => 'required|string|max:200',
            'status'             => 'nullable|in:scheduled,completed',
            'type'               => 'nullable|in:general,project',
            'project_id'         => 'nullable|exists:projects,id',
            'project_code'       => 'nullable|string|max:100',
            'weekly_department'  => 'nullable|string|max:100',
            'meeting_date'       => 'required|date',
            'location'           => 'nullable|string|max:200',
            'agenda'             => 'nullable|string',
            'discussion'         => 'nullable|string',
            'decisions'          => 'nullable|string',
            'attendees'          => 'nullable|array',
            'attendees.*.user_id'=> 'nullable|exists:users,id',
            'attendees.*.name'   => 'nullable|string|max:100',
            'action_items'              => 'nullable|array',
            'action_items.*.id'         => 'nullable|integer',
            'action_items.*.title'      => 'nullable|string|max:200',
            'action_items.*.owner_id'   => 'nullable|exists:users,id',
            'action_items.*.due_date'   => 'nullable|date',
            'action_items.*.priority'   => 'nullable|in:high,medium,low',
        ]);

        // 예정 회의에 본문(논의/결정)을 채워 저장하면 자동으로 완료 상태로 전환
        if (!array_key_exists('status', $validated) && $meetingMinute->status === 'scheduled') {
            if (filled($validated['discussion'] ?? null) || filled($validated['decisions'] ?? null)) {
                $validated['status'] = 'completed';
            }
        }

        $validated['type'] = $request->filled('project_id') ? 'project' : 'general';

        $meetingMinute->update($validated);
        $meetingMinute->attendees()->delete();
        $this->syncAttendees($meetingMinute, $request->input('attendees', []));
        $this->syncActionItems($meetingMinute, $request->input('action_items', []));
        $this->notifyMeetingAttendees($meetingMinute, true);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('meeting-minutes.show', $meetingMinute)
            ->with('success', '회의록이 수정되었습니다.');
    }

    public function getJson(MeetingMinute $meetingMinute): JsonResponse
    {
        $this->authorizeMinute($meetingMinute, 'author');
        $meetingMinute->load(['attendees', 'actionItems']);

        return response()->json([
            'id'                 => $meetingMinute->id,
            'title'              => $meetingMinute->title,
            'type'               => $meetingMinute->type,
            'project_id'         => $meetingMinute->project_id,
            'project_code'       => $meetingMinute->project_code,
            'weekly_department'  => $meetingMinute->weekly_department,
            'meeting_date'       => $meetingMinute->meeting_date->format('Y-m-d\TH:i'),
            'location'           => $meetingMinute->location,
            'agenda'             => $meetingMinute->agenda,
            'discussion'         => $meetingMinute->discussion,
            'decisions'          => $meetingMinute->decisions,
            'attendees'          => $meetingMinute->attendees->map(fn($a) => [
                'user_id' => $a->user_id,
                'name'    => $a->name,
            ]),
            'action_items'       => $meetingMinute->actionItems->map(fn($ai) => [
                'id'         => $ai->id,
                'title'      => $ai->title,
                'owner_id'   => $ai->owner_id,
                'due_date'   => optional($ai->due_date)->format('Y-m-d'),
                'priority'   => $ai->priority,
            ]),
        ]);
    }

    public function destroy(MeetingMinute $meetingMinute): RedirectResponse
    {
        $this->authorizeMinute($meetingMinute, 'author');
        $meetingMinute->delete();

        return redirect()->route('meeting-minutes.index')
            ->with('success', '회의록이 삭제되었습니다.');
    }

    public function downloadDocx(MeetingMinute $meetingMinute): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorizeMinute($meetingMinute);

        $meetingMinute->load([
            'author', 'project', 'attendees.user',
            'memos.user', 'actionItems.owner',
        ]);

        $writer = new DocxWriter();

        // 제목
        $writer->addTitle($meetingMinute->title);

        // 기본 정보
        $meta = $meetingMinute->meeting_date->format('Y년 m월 d일 (D) H:i');
        if ($meetingMinute->location)       $meta .= '  |  ' . $meetingMinute->location;
        if ($meetingMinute->weekly_department) $meta .= '  |  ' . $meetingMinute->weekly_department;
        $meta .= '  |  작성자: ' . $meetingMinute->author->name;
        $writer->addMeta($meta);

        if ($meetingMinute->project)        $writer->addMeta('프로젝트: ' . $meetingMinute->project->name);
        if ($meetingMinute->project_code)   $writer->addMeta('프로젝트 코드: ' . $meetingMinute->project_code);

        $writer->addEmpty();

        // 참석자
        if ($meetingMinute->attendees->count()) {
            $writer->addHeading('참석자', 2);
            $names = $meetingMinute->attendees->pluck('name')->filter()->join(', ');
            $writer->addText($names);
        }

        // 주요 안건
        if ($meetingMinute->agenda) {
            $writer->addHeading('주요 안건', 1);
            foreach (preg_split('/\r?\n/', trim($meetingMinute->agenda)) as $line) {
                $line = trim($line);
                if ($line !== '') $writer->addBullet($line);
            }
        }

        // 논의 내용
        if ($meetingMinute->discussion) {
            $writer->addHeading('논의 내용', 1);
            $writer->addMarkdown($meetingMinute->discussion);
        }

        // 결정 사항
        if ($meetingMinute->decisions) {
            $writer->addHeading('결정 사항', 1);
            $writer->addMarkdown($meetingMinute->decisions);
        }

        // 웍스 요약
        if ($meetingMinute->ai_summary) {
            $writer->addHeading('웍스 요약', 1);
            $writer->addMarkdown($meetingMinute->ai_summary);
        }

        // Action Items
        if ($meetingMinute->actionItems->count()) {
            $writer->addHeading('Action Items', 1);
            $headers = ['작업명', '담당자', '기한', '우선순위', '상태'];
            $rows = $meetingMinute->actionItems->map(fn($item) => [
                $item->title,
                $item->owner_display,
                $item->due_date?->format('Y.m.d') ?? '-',
                $item->priority_label,
                $item->status_label,
            ])->toArray();
            $writer->addTable($headers, $rows, [4.5, 2, 2, 1.5, 1.5]);
        }

        // 메모
        if ($meetingMinute->memos->count()) {
            $writer->addHeading('메모', 1);
            foreach ($meetingMinute->memos as $memo) {
                $writer->addText($memo->user->name . '  ' . $memo->created_at->format('m월 d일 H:i'), true);
                $writer->addMarkdown($memo->content);
                $writer->addEmpty();
            }
        }

        $tmpPath = storage_path('app/temp/minutes-' . now()->format('YmdHis') . uniqid() . '.docx');
        $writer->save($tmpPath);

        $safeName = preg_replace('/[^\w\s\-가-힣]/u', '', $meetingMinute->title);
        $downloadName = '회의록_' . $safeName . '_' . $meetingMinute->meeting_date->format('Ymd') . '.docx';

        return response()->download($tmpPath, $downloadName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    private function syncAttendees(MeetingMinute $minute, array $attendees): void
    {
        foreach ($attendees as $row) {
            $userId = $row['user_id'] ?? null;
            $name   = $row['name'] ?? null;
            if (!$userId && !$name) continue;

            if ($userId) {
                $user = User::find($userId);
                $name = $user?->name ?? $name;
            }

            MeetingAttendee::create([
                'minute_id' => $minute->id,
                'user_id'   => $userId ?: null,
                'name'      => $name,
            ]);
        }
    }

    /**
     * Action Item 동기화 — 행 id 기준 upsert. 폼에서 제거된 기존 항목은 삭제한다.
     * (메모 연동 등 id가 유지되는 항목을 보존하기 위해 delete+recreate 대신 upsert 사용)
     */
    private function syncActionItems(MeetingMinute $minute, array $items): void
    {
        $keptIds = [];

        foreach ($items as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') continue;

            $priority = $row['priority'] ?? 'medium';
            if (!in_array($priority, ['high', 'medium', 'low'], true)) {
                $priority = 'medium';
            }

            $data = [
                'title'    => $title,
                'owner_id' => !empty($row['owner_id']) ? (int) $row['owner_id'] : null,
                'due_date' => !empty($row['due_date']) ? $row['due_date'] : null,
                'priority' => $priority,
            ];

            $id = $row['id'] ?? null;
            $existing = $id ? $minute->actionItems()->whereKey($id)->first() : null;

            if ($existing) {
                $existing->update($data);
                $keptIds[] = $existing->id;
            } else {
                $created = MeetingActionItem::create([
                    ...$data,
                    'minute_id' => $minute->id,
                    'status'    => 'pending',
                ]);
                $keptIds[] = $created->id;
            }
        }

        $minute->actionItems()->whereNotIn('id', $keptIds ?: [0])->delete();
    }

    /**
     * 회의 참석자 후보 — 현재 사용자가 속한 모든 프로젝트의 구성원 전원(자신 포함, name+email).
     */
    private function projectTeammates(User $user): \Illuminate\Support\Collection
    {
        $projectIds = $user->projects()->pluck('projects.id');
        if ($projectIds->isEmpty()) {
            return collect();
        }

        return User::whereHas('projects', fn($q) => $q->whereIn('projects.id', $projectIds))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /**
     * 회의 일정 등록/수정 시 참석자에게 이메일(.ics 캘린더 초대 첨부) + SMS 발송.
     * 미래 회의만 대상이며, 응답 지연 방지를 위해 요청 종료 후(terminating) 발송.
     */
    private function notifyMeetingAttendees(MeetingMinute $minute, bool $isUpdate): void
    {
        if (!$minute->meeting_date || $minute->meeting_date->isPast()) {
            return;
        }

        $minuteId = $minute->id;
        app()->terminating(function () use ($minuteId, $isUpdate) {
            set_time_limit(0);
            try {
                $this->dispatchMeetingNotifications($minuteId, $isUpdate);
            } catch (\Throwable $e) {
                SystemErrorLog::record($e, 'warning');
            }
        });
    }

    private function dispatchMeetingNotifications(int $minuteId, bool $isUpdate): void
    {
        $minute = MeetingMinute::with(['attendees.user', 'author'])->find($minuteId);
        if (!$minute) return;

        $ics       = $this->buildMeetingIcs($minute);
        $dateLabel = $minute->meeting_date->format('Y-m-d (D) H:i');

        $recipients = $minute->attendees
            ->map(fn($a) => $a->user)
            ->filter(fn($u) => $u !== null)
            ->unique('id');

        foreach ($recipients as $user) {
            // 1) 이메일 발송 (.ics 첨부)
            if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::to($user->email)
                        ->send(new MeetingScheduledMail($minute, $user->name, $isUpdate, $ics));
                } catch (\Throwable $e) {
                    SystemErrorLog::record($e, 'warning');
                }
            }

            // 2) SMS 발송 — 이메일 성공 여부와 무관하게 휴대폰이 있으면 발송
            if (!empty($user->phone)) {
                $prefix = $isUpdate ? '회의 일정이 변경되었습니다' : '회의 일정이 등록되었습니다';
                $sms    = "[SupportWorks] {$prefix}.\n{$minute->title}\n일시: {$dateLabel}"
                        . ($minute->location ? "\n장소: {$minute->location}" : '');
                try {
                    SmsService::send($user->phone, $sms, $user->name);
                } catch (\Throwable) {
                }
            }
        }
    }

    /**
     * iCalendar(.ics) VEVENT 문자열 생성 — 받는 사람이 캘린더에 바로 등록 가능.
     */
    private function buildMeetingIcs(MeetingMinute $minute): string
    {
        $toUtc = fn(\DateTimeInterface $dt) => (clone $dt)
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Ymd\THis\Z');
        $esc = fn(?string $s) => str_replace(
            ["\\", "\r\n", "\n", ",", ";"],
            ["\\\\", "\\n", "\\n", "\\,", "\\;"],
            (string) $s
        );

        $start = $minute->meeting_date;
        $end   = (clone $start)->addHour();
        $desc  = trim((string) ($minute->agenda ?? ''));

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//SupportWorks//Meeting//KO',
            'CALSCALE:GREGORIAN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            "UID:meeting-{$minute->id}@supportworks",
            'SEQUENCE:' . ($minute->updated_at?->timestamp ?? time()),
            'DTSTAMP:' . $toUtc(now()),
            'DTSTART:' . $toUtc($start),
            'DTEND:'   . $toUtc($end),
            'SUMMARY:' . $esc($minute->title),
        ];
        if ($minute->location) {
            $lines[] = 'LOCATION:' . $esc($minute->location);
        }
        if ($desc !== '') {
            $lines[] = 'DESCRIPTION:' . $esc($desc);
        }
        if ($minute->author && filter_var($minute->author->email, FILTER_VALIDATE_EMAIL)) {
            $lines[] = 'ORGANIZER;CN=' . $esc($minute->author->name) . ':mailto:' . $minute->author->email;
        }
        $lines[] = 'STATUS:CONFIRMED';
        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * 회의록 입력 영역(안건·논의 내용·결정 사항) 웍스 정제.
     * 논의사항 정제와 동일한 방식 — AiOrchestrator 로 텍스트를 다듬어 반환한다.
     */
    public function refine(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:50000',
            'field'   => 'nullable|string|in:agenda,discussion,decisions',
        ]);

        $aiSetting = AiSetting::current();
        if (!$aiSetting->anthropicKey() && !$aiSetting->openaiKey()) {
            return response()->json(['ok' => false, 'message' => '웍스 API 키가 설정되지 않았습니다.'], 503);
        }

        $original   = trim((string) $request->content);
        $fieldGuide = match ($request->input('field')) {
            'agenda'     => '이것은 회의 안건(Agenda)입니다. 다룰 주제를 간결한 항목 목록으로 정리하세요.',
            'discussion' => '이것은 회의의 논의 내용(Discussion)입니다. 오간 논의를 주제별로 명확하게 정리하세요.',
            'decisions'  => '이것은 회의의 결정 사항(Decisions)입니다. 확정된 결정을 명확한 항목 목록으로 정리하세요.',
            default      => '이것은 회의록의 한 항목입니다.',
        };

        $systemPrompt = implode("\n", [
            "당신은 IT 프로젝트 회의록 정제기입니다.",
            "사용자가 빠르게 적은 회의 메모를, 나중에 읽는 사람이 이해하기 쉽게 다듬어 주세요.",
            "",
            $fieldGuide,
            "",
            "지침:",
            "- 원문의 사실·결정·요청은 절대 변경 금지. 원문에 없는 내용 추가 금지.",
            "- 모호한 부분은 '확인 필요'로 표시.",
            "- 한국어, 정중한 실무 문체.",
            "- 항목이 여러 개면 '- ' 불릿 목록으로 정리. 메모가 짧으면 억지로 늘리지 마세요.",
            "- 결과 텍스트만 반환 (HTML 태그·코드펜스·메타 문구 금지).",
        ]);
        $userPrompt = "다음 회의 메모를 다듬어 주세요.\n\n원문:\n{$original}";

        try {
            $orchestrator = new AiOrchestrator(
                $aiSetting->anthropicKey(),
                $aiSetting->openaiKey(),
            );
            $result  = $orchestrator->chatRawFast(
                [['role' => 'user', 'content' => $userPrompt]],
                $systemPrompt
            );
            $refined = trim($result['text'] ?? '');
            $refined = preg_replace('/^```(?:[a-z]+)?\s*|\s*```$/i', '', $refined);
            $refined = trim($refined);

            if ($refined === '') {
                return response()->json(['ok' => false, 'message' => '정제 결과가 비어 있습니다.']);
            }
            return response()->json(['ok' => true, 'refined' => $refined]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e, 'warning');
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function authorizeMinute(MeetingMinute $minute, string $role = null): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;

        // 'author' 권한(수정·삭제)은 작성자 본인만
        if ($role === 'author') {
            abort_if($minute->author_id !== $user->id, 403);
            return;
        }

        // 열람 권한: 같은 회사 · 작성자 · 참석자(계정 연결)
        $sameCompany = $user->company_group_id
            && $minute->company_group_id
            && $user->company_group_id === $minute->company_group_id;
        $isAuthor   = $minute->author_id === $user->id;
        $isAttendee = $minute->attendees()->where('user_id', $user->id)->exists();

        abort_unless($sameCompany || $isAuthor || $isAttendee, 403);
    }
}
