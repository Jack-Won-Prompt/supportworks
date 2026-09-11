<?php

namespace App\Services\AiWork;

use App\Models\AiWork\AiwJobAttachment;
use App\Models\AiWork\AiwJobMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * 지시에 붙는 이미지의 저장.
 *
 * 리사이즈가 핵심이다. 이미지 한 장이 대략 1,000~1,600 토큰을 먹으므로 원본
 * 스크린샷을 그대로 넣으면 컨텍스트가 금세 차고 인수인계가 빨리 터진다.
 * Anthropic 권장대로 긴 변을 1568px 로 줄인다 — 그보다 크면 어차피 축소되어
 * 전송량만 늘고 정확도는 늘지 않는다.
 *
 * GD 만 쓴다(서버·로컬 모두 있음). 새 의존성을 들이지 않기 위해서다.
 */
class AttachmentService
{
    /** 긴 변 상한. Anthropic 권장값. */
    public const MAX_EDGE = 1568;

    /** 업로드 원본 크기 상한. 리사이즈 전 기준. */
    public const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;

    /** 메시지 하나에 붙일 수 있는 장수. */
    public const MAX_PER_MESSAGE = 5;

    /** 받아들이는 형식. 여기 없는 것은 거부한다. */
    public const ALLOWED_MIMES = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, AiwJobAttachment>
     *
     * @throws ValidationException
     */
    public function attach(AiwJobMessage $message, array $files, User $user): array
    {
        $files = array_values(array_filter($files));

        if ($files === []) {
            return [];
        }

        if (count($files) > self::MAX_PER_MESSAGE) {
            throw ValidationException::withMessages([
                'images' => '이미지는 한 번에 '.self::MAX_PER_MESSAGE.'장까지 첨부할 수 있습니다.',
            ]);
        }

        $saved = [];

        foreach ($files as $file) {
            $saved[] = $this->store($message, $file, $user);
        }

        return $saved;
    }

    private function store(AiwJobMessage $message, UploadedFile $file, User $user): AiwJobAttachment
    {
        $mime = (string) $file->getMimeType();

        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'images' => '이미지 파일만 첨부할 수 있습니다(PNG·JPEG·WebP·GIF). 받은 형식: '.$mime,
            ]);
        }

        if ($file->getSize() > self::MAX_UPLOAD_BYTES) {
            throw ValidationException::withMessages([
                'images' => '이미지 한 장은 10MB 이하여야 합니다.',
            ]);
        }

        [$binary, $outMime, $width, $height] = $this->normalize($file, $mime);

        $ext = $outMime === 'image/png' ? 'png' : 'jpg';
        $path = sprintf('aiw/attachments/%d/%s.%s', $message->job_id, Str::uuid(), $ext);

        Storage::disk('local')->put($path, $binary);

        return AiwJobAttachment::create([
            'job_id'        => $message->job_id,
            'message_id'    => $message->id,
            'path'          => $path,
            'mime'          => $outMime,
            'bytes'         => strlen($binary),
            'width'         => $width,
            'height'        => $height,
            'original_name' => Str::limit((string) $file->getClientOriginalName(), 250, ''),
            'created_by'    => $user->id,
        ]);
    }

    /**
     * 긴 변을 MAX_EDGE 로 줄이고 다시 인코딩한다.
     *
     * 투명도가 의미 있는 PNG 는 PNG 로, 나머지는 JPEG 로 낸다. GIF 는 첫 프레임만
     * 쓴다(애니메이션은 모델에 어차피 한 장으로 간다).
     *
     * @return array{0: string, 1: string, 2: int|null, 3: int|null}
     */
    private function normalize(UploadedFile $file, string $mime): array
    {
        $raw = (string) file_get_contents($file->getRealPath());

        try {
            $image = @imagecreatefromstring($raw);

            if ($image === false) {
                throw new \RuntimeException('이미지를 해석할 수 없습니다.');
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $longEdge = max($width, $height);

            if ($longEdge > self::MAX_EDGE) {
                $scale = self::MAX_EDGE / $longEdge;
                $newWidth = max(1, (int) round($width * $scale));
                $newHeight = max(1, (int) round($height * $scale));

                $resized = imagescale($image, $newWidth, $newHeight);

                if ($resized !== false) {
                    imagedestroy($image);
                    $image = $resized;
                    $width = $newWidth;
                    $height = $newHeight;
                }
            }

            $asPng = $mime === 'image/png';

            ob_start();
            if ($asPng) {
                imagesavealpha($image, true);
                imagepng($image, null, 8);
            } else {
                imagejpeg($image, null, 85);
            }
            $binary = (string) ob_get_clean();

            imagedestroy($image);

            return [$binary, $asPng ? 'image/png' : 'image/jpeg', $width, $height];
        } catch (\Throwable $e) {
            // 리사이즈 실패가 첨부 자체를 막지는 않는다. 다만 원본이 크면
            // 컨텍스트를 많이 먹으므로 기록은 남긴다.
            Log::warning('AI Works: 이미지 리사이즈 실패 — 원본을 그대로 저장합니다.', [
                'mime'  => $mime,
                'bytes' => strlen($raw),
                'error' => $e->getMessage(),
            ]);

            return [$raw, $mime, null, null];
        }
    }
}
