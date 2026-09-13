<?php

namespace App\Services\AiWork;

/**
 * 같은 에러인지 가르는 규칙.
 *
 * 이 규칙 하나에 기능 전체가 걸려 있다. 너무 촘촘하면 같은 고장이 수백 건으로
 * 흩어져 작업 지시가 수백 개 생기고, 너무 성기면 서로 다른 고장이 한 건으로
 * 묶여 고쳐도 계속 살아 있는 것처럼 보인다.
 *
 * 기준은 **예외 클래스 + 파일 + 줄** 이다. 메시지는 쓰지 않는다 — 같은 코드가
 * 내는 메시지에 주문번호나 사용자 이름이 섞여 매번 달라지기 때문이다.
 * 줄 번호는 코드를 고치면 밀리지만, 그때는 사실 다른 상황으로 보는 편이 맞다.
 */
class ErrorFingerprint
{
    public static function make(?string $exception, ?string $file, ?int $line): string
    {
        return hash('sha256', implode('|', [
            self::normaliseException($exception),
            self::normalisePath($file),
            $line ?? 0,
        ]));
    }

    /**
     * 익명 클래스는 파일 경로와 해시가 이름에 붙어 매번 달라진다.
     * (`Migration@anonymous/path/to/x.php:20$1b5`) 그 꼬리를 떼어 낸다.
     */
    private static function normaliseException(?string $exception): string
    {
        $name = trim((string) $exception);

        if ($name === '') {
            return '(none)';
        }

        $name = preg_replace('/@anonymous.*$/', '@anonymous', $name);

        return mb_substr($name, 0, 255);
    }

    /**
     * 경로는 배포마다 다르다 — 로컬은 `E:\xampp\htdocs\korsafety\app\...`,
     * 운영은 `/home/ubuntu/www/korsafety/app/...`. 같은 파일인데 다른 지문이
     * 되면 운영에서 난 에러와 로컬에서 재현한 에러가 따로 놀게 된다.
     *
     * 그래서 애플리케이션 루트 아래(app/ config/ routes/ …)만 남긴다.
     */
    private static function normalisePath(?string $file): string
    {
        $path = str_replace('\\', '/', trim((string) $file));

        if ($path === '') {
            return '(none)';
        }

        $roots = ['app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'tests', 'vendor'];

        foreach ($roots as $root) {
            $at = strpos($path, "/$root/");

            if ($at !== false) {
                return substr($path, $at + 1);
            }
        }

        // 루트를 못 찾으면 파일 이름만 쓴다. 경로 전체를 쓰는 것보다 낫다.
        return basename($path);
    }
}
