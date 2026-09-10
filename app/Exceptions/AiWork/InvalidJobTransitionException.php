<?php

namespace App\Exceptions\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use RuntimeException;

/** 허용표에 없는 상태 전이 시도. HTTP 로는 409 로 변환한다. */
class InvalidJobTransitionException extends RuntimeException
{
    public function __construct(
        public readonly AiwJobStatus $from,
        public readonly AiwJobStatus $to,
        public readonly ?int $jobId = null,
    ) {
        parent::__construct(sprintf(
            '상태 전이가 허용되지 않습니다%s: %s → %s',
            $jobId ? " (#{$jobId})" : '',
            $from->value,
            $to->value,
        ));
    }
}
