<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Jobs\Agent\ParseAttachedFile;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentArtifactFile;
use App\Services\Agent\Parsers\FileParserResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ToBeAnalysisService
{
    public function __construct(private readonly FileParserResolver $resolver) {}

    public function createOrGetAnalysis(
        int $projectId,
        int $stageId,
        int $userId,
    ): AiAgentArtifact {
        return AiAgentArtifact::upsertForScope(
            projectId: $projectId,
            stageId:   $stageId,
            type:      ArtifactType::TO_BE_REQUIREMENTS,
            scopeType: 'project',
            scopeId:   $projectId,
            title:     'TO-BE 요구사항 분석',
            content:   '',
            userId:    $userId,
        );
    }

    /**
     * @return AiAgentArtifactFile[]
     */
    public function attachFiles(AiAgentArtifact $artifact, array $files, int $userId): array
    {
        $attached = [];

        foreach ($files as $file) {
            /** @var UploadedFile $file */
            $storagePath = $file->store(
                "ai-agent/artifacts/{$artifact->id}",
                'local'
            );

            $fileType = $this->resolveFileType($file->getMimeType() ?? '');

            $record = AiAgentArtifactFile::create([
                'artifact_id'  => $artifact->id,
                'file_name'    => $file->getClientOriginalName(),
                'file_type'    => $fileType,
                'file_size'    => $file->getSize(),
                'mime_type'    => $file->getMimeType() ?? 'application/octet-stream',
                'storage_path' => $storagePath,
                'parse_status' => 'pending',
                'uploaded_by'  => $userId,
            ]);

            ParseAttachedFile::dispatch($record);

            $attached[] = $record;
        }

        return $attached;
    }

    public function getAnalysisStatus(AiAgentArtifact $artifact): array
    {
        $files = $artifact->files()->get();

        $counts = [
            'total'     => $files->count(),
            'pending'   => 0,
            'parsing'   => 0,
            'completed' => 0,
            'failed'    => 0,
        ];

        foreach ($files as $file) {
            $counts[$file->parse_status] = ($counts[$file->parse_status] ?? 0) + 1;
        }

        return [
            'artifact_id' => $artifact->id,
            'counts'      => $counts,
            'ready'       => $counts['pending'] === 0 && $counts['parsing'] === 0,
        ];
    }

    public function removeFile(AiAgentArtifactFile $file): void
    {
        Storage::disk('local')->delete($file->storage_path);
        $file->delete();
    }

    private function resolveFileType(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/')                       => 'image',
            $mimeType === 'application/pdf'                            => 'pdf',
            str_contains($mimeType, 'spreadsheet') ||
                str_contains($mimeType, 'excel') ||
                str_contains($mimeType, 'xls')                         => 'excel',
            str_contains($mimeType, 'presentation') ||
                str_contains($mimeType, 'powerpoint') ||
                str_contains($mimeType, 'mspowerpoint')                => 'pptx',
            str_starts_with($mimeType, 'text/') ||
                $mimeType === 'application/json'                        => 'text',
            default                                                     => 'other',
        };
    }
}
