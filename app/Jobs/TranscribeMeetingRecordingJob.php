<?php

namespace App\Jobs;

use App\Models\MeetingRecording;
use App\Services\OpenAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TranscribeMeetingRecordingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 900; // 15분

    public function __construct(public int $recordingId)
    {
    }

    public function handle(): void
    {
        $recording = MeetingRecording::find($this->recordingId);
        if (!$recording) {
            Log::warning("TranscribeJob: recording {$this->recordingId} not found");
            return;
        }

        $apiKey = config('services.openai.key');
        if (empty($apiKey)) {
            $recording->update([
                'status'        => 'failed',
                'error_message' => 'OPENAI_API_KEY 가 설정되지 않았습니다.',
            ]);
            return;
        }
        $openai = new OpenAiService($apiKey);

        try {
            $recording->update(['status' => 'transcribing']);

            $absolutePath = Storage::path($recording->file_path);
            if (!file_exists($absolutePath)) {
                throw new \RuntimeException("파일이 존재하지 않습니다: {$absolutePath}");
            }

            // 1) Whisper로 녹취록 생성
            $result = $openai->transcribeAudio($absolutePath, 'ko', true);
            $transcript = trim($result['text'] ?? '');

            if ($transcript === '') {
                throw new \RuntimeException('녹취록이 비어 있습니다.');
            }

            $recording->update([
                'transcription'          => $transcript,
                'transcription_segments' => $result['segments'],
                'status'                 => 'transcribed',
            ]);

            // 2) 회의록 자동 작성
            $recording->update(['status' => 'summarizing']);
            $summary = $openai->generateMeetingMinutes($transcript, $recording->title);

            $recording->update([
                'summary' => trim($summary),
                'status'  => 'completed',
            ]);
        } catch (\Throwable $e) {
            Log::error('TranscribeJob failed', [
                'recording_id' => $this->recordingId,
                'error'        => $e->getMessage(),
            ]);
            $recording->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $recording = MeetingRecording::find($this->recordingId);
        if ($recording) {
            $recording->update([
                'status'        => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}