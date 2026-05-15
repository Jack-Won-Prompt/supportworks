<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Jobs\TranscribeMeetingRecordingJob;
use App\Models\MeetingMinute;
use App\Models\MeetingRecording;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MeetingRecordingController extends Controller
{
    /** GET /meeting-recordings */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $recordings = MeetingRecording::with(['project:id,name', 'meetingMinute:id,title'])
            ->where('user_id', $user->id)
            ->latest('recorded_at')
            ->latest('id')
            ->get();

        return response()->json($recordings->map(fn($r) => $this->resource($r)));
    }

    /** GET /meeting-recordings/{recording} */
    public function show(Request $request, MeetingRecording $recording): JsonResponse
    {
        abort_if($recording->user_id !== $request->user()->id, 403);
        $recording->load(['project:id,name', 'meetingMinute:id,title']);

        return response()->json([
            ...$this->resource($recording),
            'transcription'          => $recording->transcription,
            'transcription_segments' => $recording->transcription_segments,
            'summary'                => $recording->summary,
            'file_url'               => Storage::url($recording->file_path),
            'error_message'          => $recording->error_message,
        ]);
    }

    /** POST /meeting-recordings - 회의 녹음 파일 업로드 */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'audio'             => 'required|file|max:204800|mimes:m4a,mp3,mp4,aac,wav,ogg,webm', // 200MB
            'title'             => 'nullable|string|max:255',
            'duration_seconds'  => 'nullable|integer|min:0',
            'project_id'        => 'nullable|exists:projects,id',
            'meeting_minute_id' => 'nullable|exists:meeting_minutes,id',
            'recorded_at'       => 'nullable|date',
        ]);

        $user = $request->user();
        $file = $request->file('audio');

        // Stored privately: storage/app/meeting-recordings/{user_id}/...
        $path = $file->store("meeting-recordings/{$user->id}");

        $recording = MeetingRecording::create([
            'user_id'           => $user->id,
            'project_id'        => $request->project_id,
            'meeting_minute_id' => $request->meeting_minute_id,
            'title'             => $request->title ?? '회의 녹음 ' . now()->format('Y-m-d H:i'),
            'file_path'         => $path,
            'original_name'     => $file->getClientOriginalName(),
            'mime_type'         => $file->getMimeType(),
            'file_size'         => $file->getSize(),
            'duration_seconds'  => (int) $request->input('duration_seconds', 0),
            'status'            => 'uploaded',
            'recorded_at'       => $request->recorded_at ?? now(),
        ]);

        // 자동 STT + 회의록 생성 작업 큐 등록
        TranscribeMeetingRecordingJob::dispatch($recording->id);

        return response()->json($this->resource($recording), 201);
    }

    /** POST /meeting-recordings/{recording}/retry-transcription - 실패/재시도 */
    public function retryTranscription(Request $request, MeetingRecording $recording): JsonResponse
    {
        abort_if($recording->user_id !== $request->user()->id, 403);

        $recording->update([
            'status'        => 'uploaded',
            'error_message' => null,
        ]);
        TranscribeMeetingRecordingJob::dispatch($recording->id);

        return response()->json($this->resource($recording));
    }

    /** PATCH /meeting-recordings/{recording}/content - transcription/summary 수동 편집 */
    public function updateContent(Request $request, MeetingRecording $recording): JsonResponse
    {
        abort_if($recording->user_id !== $request->user()->id, 403);

        $request->validate([
            'transcription' => 'nullable|string',
            'summary'       => 'nullable|string',
        ]);

        $recording->update($request->only(['transcription', 'summary']));

        return response()->json($this->resource($recording));
    }

    /**
     * POST /meeting-recordings/{recording}/convert-to-minute
     * 자동 생성된 회의록(summary) → 정식 MeetingMinute 레코드로 변환
     */
    public function convertToMinute(Request $request, MeetingRecording $recording): JsonResponse
    {
        abort_if($recording->user_id !== $request->user()->id, 403);

        if (empty($recording->summary)) {
            return response()->json(['message' => '회의록 요약이 아직 생성되지 않았습니다.'], 422);
        }

        $request->validate([
            'title'        => 'nullable|string|max:255',
            'project_id'   => 'nullable|exists:projects,id',
            'meeting_date' => 'nullable|date',
            'location'     => 'nullable|string|max:255',
            'type'         => 'nullable|in:general,project',
        ]);

        $user = $request->user();
        $summary = $recording->summary;
        $sections = $this->splitSummarySections($summary);

        $minute = DB::transaction(function () use ($request, $recording, $user, $sections, $summary) {
            $minute = MeetingMinute::create([
                'title'             => $request->title ?? $recording->title ?? '회의록',
                'status'            => 'draft',
                'type'              => $request->type ?? ($recording->project_id ? 'project' : 'general'),
                'project_id'        => $request->project_id ?? $recording->project_id,
                'meeting_date'      => $request->meeting_date ?? optional($recording->recorded_at)->toDateString() ?? now()->toDateString(),
                'location'          => $request->location,
                'author_id'         => $user->id,
                'company_group_id'  => $user->company_group_id,
                'agenda'            => $sections['agenda'],
                'discussion'        => $sections['discussion'],
                'decisions'         => $sections['decisions'],
                'ai_summary'        => $summary,
            ]);

            $recording->update(['meeting_minute_id' => $minute->id]);
            return $minute;
        });

        return response()->json([
            'message'        => '회의록이 생성되었습니다.',
            'meeting_minute' => ['id' => $minute->id, 'title' => $minute->title],
            'recording'      => $this->resource($recording->fresh()),
        ], 201);
    }

    /**
     * AI summary 마크다운에서 섹션을 추출
     */
    private function splitSummarySections(string $markdown): array
    {
        $patterns = [
            'agenda'     => '/##\s*회의\s*요약|##\s*안건|##\s*Agenda/i',
            'discussion' => '/##\s*주요\s*논의|##\s*논의\s*내용|##\s*Discussion/i',
            'decisions'  => '/##\s*결정\s*사항|##\s*Decisions|##\s*Action\s*Items|##\s*액션\s*아이템/i',
        ];

        $lines = preg_split('/\r?\n/', $markdown);
        $sections = ['agenda' => '', 'discussion' => '', 'decisions' => ''];
        $current = null;

        foreach ($lines as $line) {
            $matched = null;
            foreach ($patterns as $key => $rx) {
                if (preg_match($rx, $line)) { $matched = $key; break; }
            }
            if ($matched !== null) {
                $current = $matched;
                continue;
            }
            if ($current !== null) {
                $sections[$current] .= $line . "\n";
            }
        }

        return array_map(
            fn($v) => trim($v) === '' ? null : trim($v),
            $sections
        );
    }

    /** PATCH /meeting-recordings/{recording} - 제목/연결 메타 수정 */
    public function update(Request $request, MeetingRecording $recording): JsonResponse
    {
        abort_if($recording->user_id !== $request->user()->id, 403);

        $request->validate([
            'title'             => 'nullable|string|max:255',
            'project_id'        => 'nullable|exists:projects,id',
            'meeting_minute_id' => 'nullable|exists:meeting_minutes,id',
        ]);

        $recording->update($request->only(['title', 'project_id', 'meeting_minute_id']));

        return response()->json($this->resource($recording));
    }

    /** DELETE /meeting-recordings/{recording} */
    public function destroy(Request $request, MeetingRecording $recording): JsonResponse
    {
        abort_if($recording->user_id !== $request->user()->id, 403);

        // 파일도 삭제
        if ($recording->file_path && Storage::exists($recording->file_path)) {
            Storage::delete($recording->file_path);
        }
        $recording->delete();

        return response()->json(['message' => '녹음이 삭제되었습니다.']);
    }

    /** GET /meeting-recordings/{recording}/download - 인증된 사용자만 파일 스트림 */
    public function download(Request $request, MeetingRecording $recording)
    {
        abort_if($recording->user_id !== $request->user()->id, 403);
        abort_unless(Storage::exists($recording->file_path), 404);

        return Storage::download(
            $recording->file_path,
            $recording->original_name ?? basename($recording->file_path),
            ['Content-Type' => $recording->mime_type ?? 'application/octet-stream']
        );
    }

    private function resource(MeetingRecording $r): array
    {
        return [
            'id'                 => $r->id,
            'title'              => $r->title,
            'status'             => $r->status,
            'status_label'       => $r->status_label,
            'duration_seconds'   => $r->duration_seconds,
            'duration_label'     => $r->formatted_duration,
            'file_size'          => $r->file_size,
            'file_size_label'    => $r->formatted_size,
            'mime_type'          => $r->mime_type,
            'project'            => $r->project ? ['id' => $r->project->id, 'name' => $r->project->name] : null,
            'meeting_minute'     => $r->meetingMinute ? ['id' => $r->meetingMinute->id, 'title' => $r->meetingMinute->title] : null,
            'has_transcription'  => filled($r->transcription),
            'has_summary'        => filled($r->summary),
            'recorded_at'        => $r->recorded_at,
            'created_at'         => $r->created_at,
        ];
    }
}