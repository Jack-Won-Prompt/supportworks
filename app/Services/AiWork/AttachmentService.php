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
 * 지시·회신에 붙는 파일의 저장.
 *
 * 두 갈래를 다르게 다룬다.
 *
 * **이미지** — 모델이 그림으로 직접 본다. 리사이즈가 핵심이다. 한 장이 대략
 * 1,000~1,600 토큰을 먹으므로 원본 스크린샷을 그대로 넣으면 컨텍스트가 금세 차고
 * 인수인계가 빨리 터진다. Anthropic 권장대로 긴 변을 1568px 로 줄인다 — 그보다
 * 크면 어차피 축소되어 전송량만 늘고 정확도는 늘지 않는다. GD 만 쓴다(서버·로컬
 * 모두 있음).
 *
 * **문서**(텍스트·CSV·PDF·워드·엑셀·PPT) — 모델이 내용을 직접 볼 수 없다. Office
 * 파일은 XML 을 압축한 덩어리라 그림처럼 넣을 수 없기 때문이다. 그래서 손대지 않고
 * 원본 그대로 보관하고, 데몬이 작업 폴더에 풀어 준 뒤 모델이 도구로 열어 읽는다
 * (tools/aiw-agent/src/attachments.ts).
 */
class AttachmentService
{
    /** 긴 변 상한. Anthropic 권장값. */
    public const MAX_EDGE = 1568;

    /** 이미지 원본 크기 상한. 리사이즈 전 기준. */
    public const MAX_UPLOAD_BYTES = 20 * 1024 * 1024;

    /** 문서 크기 상한. 손대지 않고 그대로 보관하므로 이미지보다 넉넉하다. */
    public const MAX_DOCUMENT_BYTES = 30 * 1024 * 1024;

    /** 메시지 하나에 붙일 수 있는 개수. */
    public const MAX_PER_MESSAGE = 5;

    /** 모델이 그림으로 직접 보는 형식. 리사이즈 대상이다. */
    public const IMAGE_MIMES = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];

    /**
     * 원본 그대로 보관하는 형식. 데몬이 작업 폴더에 풀어 주고 모델이 도구로 읽는다.
     *
     * 브라우저가 보내는 MIME 은 OS·확장자에 따라 흔들린다(특히 Office 는
     * application/zip 이나 octet-stream 으로 오기도 한다). 그래서 MIME 과 확장자를
     * 함께 본다 — 아래 EXTENSION_MIMES 참고.
     */
    public const DOCUMENT_MIMES = [
        'text/plain', 'text/markdown', 'text/csv', 'application/json', 'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    /**
     * 확장자로 인정하는 문서. MIME 이 흔들려도 이 확장자면 받아들이고, 저장할
     * MIME 은 여기 값으로 바로잡는다.
     */
    public const EXTENSION_MIMES = [
        'txt'  => 'text/plain',
        'md'   => 'text/markdown',
        'csv'  => 'text/csv',
        'json' => 'application/json',
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    /** 예전 이름. 이미지만 받던 시절의 호출부가 남아 있을 수 있다. */
    public const ALLOWED_MIMES = self::IMAGE_MIMES;

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
                'images' => '첨부는 한 번에 '.self::MAX_PER_MESSAGE.'개까지 올릴 수 있습니다.',
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
        $ext  = strtolower((string) $file->getClientOriginalExtension());

        if (in_array($mime, self::IMAGE_MIMES, true)) {
            return $this->storeImage($message, $file, $user, $mime);
        }

        // MIME 이 흔들려도 확장자가 맞으면 받는다. Office 파일은 zip 컨테이너라
        // application/zip 이나 octet-stream 으로 오는 경우가 흔하다.
        $byExtension = self::EXTENSION_MIMES[$ext] ?? null;

        if ($byExtension !== null || in_array($mime, self::DOCUMENT_MIMES, true)) {
            return $this->storeDocument($message, $file, $user, $byExtension ?? $mime, $ext);
        }

        throw ValidationException::withMessages([
            'images' => '올릴 수 없는 형식입니다: '.($ext !== '' ? '.'.$ext : $mime)
                .'. 이미지(PNG·JPEG·WebP·GIF)와 문서(txt·md·csv·json·pdf·doc·docx·xls·xlsx·ppt·pptx)만 올릴 수 있습니다.',
        ]);
    }

    /**
     * 문서는 손대지 않고 그대로 보관한다.
     *
     * 서버가 열어서 텍스트를 뽑지 않는다 — 그러려면 Office 파서 의존성이 필요하고,
     * 표·서식·수식을 어떻게 줄일지 서버가 정하게 된다. 그 판단은 실제로 파일을 열어
     * 보는 쪽(담당자 PC 의 모델)이 하는 편이 낫다.
     */
    private function storeDocument(
        AiwJobMessage $message,
        UploadedFile $file,
        User $user,
        string $mime,
        string $ext,
    ): AiwJobAttachment {
        if ($file->getSize() > self::MAX_DOCUMENT_BYTES) {
            throw ValidationException::withMessages([
                'images' => '문서 하나는 '.(int) (self::MAX_DOCUMENT_BYTES / 1024 / 1024).'MB 이하여야 합니다.',
            ]);
        }

        $binary = (string) file_get_contents($file->getRealPath());
        $path = sprintf('aiw/attachments/%d/%s.%s', $message->job_id, Str::uuid(), $ext !== '' ? $ext : 'bin');

        Storage::disk('local')->put($path, $binary);

        return AiwJobAttachment::create([
            'job_id'        => $message->job_id,
            'message_id'    => $message->id,
            'path'          => $path,
            'mime'          => $mime,
            'bytes'         => strlen($binary),
            'width'         => null,
            'height'        => null,
            'original_name' => Str::limit((string) $file->getClientOriginalName(), 250, ''),
            'created_by'    => $user->id,
        ]);
    }

    private function storeImage(
        AiwJobMessage $message,
        UploadedFile $file,
        User $user,
        string $mime,
    ): AiwJobAttachment {
        if ($file->getSize() > self::MAX_UPLOAD_BYTES) {
            throw ValidationException::withMessages([
                'images' => '이미지 한 장은 '.(int) (self::MAX_UPLOAD_BYTES / 1024 / 1024).'MB 이하여야 합니다.',
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
