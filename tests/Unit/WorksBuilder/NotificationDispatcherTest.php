<?php

namespace Tests\Unit\WorksBuilder;

use App\Services\WorksBuilder\Notification\NotificationDispatcher;
use PHPUnit\Framework\TestCase;

class NotificationDispatcherTest extends TestCase
{
    public function test_stage_messages_cover_spec_stage_codes(): void
    {
        // 명세 §1.9 표 — 모든 stage 코드가 매핑되어 있어야 한다.
        $required = [
            'started', 'option_input', 'spec_review', 'result_confirm',
            'qa_review', 'ng_input', 'recheck', 'prompt_learned', 'complete',
        ];

        foreach ($required as $code) {
            $this->assertArrayHasKey(
                $code, NotificationDispatcher::STAGE_MESSAGES,
                "Missing stage_code mapping: {$code}"
            );

            [$title, $message] = NotificationDispatcher::STAGE_MESSAGES[$code];
            $this->assertNotEmpty($title,   "Empty title for {$code}");
            $this->assertNotEmpty($message, "Empty message for {$code}");
        }
    }
}
