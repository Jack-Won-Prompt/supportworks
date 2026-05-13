<?php

namespace App\Http\Controllers;

use App\Models\MeetingMinute;
use App\Models\MeetingAttendee;
use App\Models\Project;
use App\Models\User;
use App\Services\DocxWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MeetingMinuteController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = MeetingMinute::companyOf($user)
            ->with(['author', 'project', 'attendees', 'actionItems']);

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
            'total'   => MeetingMinute::companyOf($user)->count(),
            'month'   => MeetingMinute::companyOf($user)->whereMonth('meeting_date', now()->month)->whereYear('meeting_date', now()->year)->count(),
            'general' => MeetingMinute::companyOf($user)->where('type', 'general')->count(),
            'project' => MeetingMinute::companyOf($user)->where('type', 'project')->count(),
        ];

        $teammates = $user->company_group_id
            ? User::where('company_group_id', $user->company_group_id)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('meeting-minutes.index', compact('minutes', 'projects', 'stats', 'teammates'));
    }

    public function create(): View
    {
        $user = auth()->user();
        $projects = Project::whereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->orderBy('name')->get(['id', 'name']);
        $teammates = $user->company_group_id
            ? User::where('company_group_id', $user->company_group_id)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('meeting-minutes.create', compact('projects', 'teammates'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'title'              => 'required|string|max:200',
            'type'               => 'required|in:general,project',
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
        ]);

        $minute = MeetingMinute::create([
            ...$validated,
            'author_id'        => $user->id,
            'company_group_id' => $user->company_group_id,
        ]);

        $this->syncAttendees($minute, $request->input('attendees', []));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('meeting-minutes.show', $minute)
            ->with('success', '회의록이 등록되었습니다.');
    }

    public function show(MeetingMinute $meetingMinute): View
    {
        $this->authorizeMinute($meetingMinute);

        $meetingMinute->load([
            'author', 'project',
            'attendees.user',
            'memos.user', 'memos.actionItems.owner',
            'actionItems.owner',
        ]);

        $user = auth()->user();
        $teammates = $user->company_group_id
            ? User::where('company_group_id', $user->company_group_id)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('meeting-minutes.show', compact('meetingMinute', 'teammates'));
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
        $teammates = $user->company_group_id
            ? User::where('company_group_id', $user->company_group_id)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('meeting-minutes.show_popup', compact('meetingMinute', 'teammates'));
    }

    public function edit(MeetingMinute $meetingMinute): View
    {
        $this->authorizeMinute($meetingMinute, 'author');

        $meetingMinute->load(['attendees.user', 'project']);

        $user = auth()->user();
        $projects = Project::whereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->orderBy('name')->get(['id', 'name']);
        $teammates = $user->company_group_id
            ? User::where('company_group_id', $user->company_group_id)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('meeting-minutes.edit', compact('meetingMinute', 'projects', 'teammates'));
    }

    public function update(Request $request, MeetingMinute $meetingMinute): RedirectResponse|JsonResponse
    {
        $this->authorizeMinute($meetingMinute, 'author');

        $validated = $request->validate([
            'title'              => 'required|string|max:200',
            'type'               => 'required|in:general,project',
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
        ]);

        $meetingMinute->update($validated);
        $meetingMinute->attendees()->delete();
        $this->syncAttendees($meetingMinute, $request->input('attendees', []));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('meeting-minutes.show', $meetingMinute)
            ->with('success', '회의록이 수정되었습니다.');
    }

    public function getJson(MeetingMinute $meetingMinute): JsonResponse
    {
        $this->authorizeMinute($meetingMinute, 'author');
        $meetingMinute->load(['attendees']);

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

    private function authorizeMinute(MeetingMinute $minute, string $role = null): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;

        if ($user->company_group_id && $minute->company_group_id) {
            abort_if($user->company_group_id !== $minute->company_group_id, 403);
        } else {
            abort_if($minute->author_id !== $user->id, 403);
        }

        if ($role === 'author') {
            abort_if($minute->author_id !== $user->id, 403);
        }
    }
}
