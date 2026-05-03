<?php

namespace App\Enums\Agent;

enum RequirementPriority: string
{
    case MUST   = 'must';
    case SHOULD = 'should';
    case COULD  = 'could';
    case WONT   = 'wont';

    public function label(): string
    {
        return match($this) {
            self::MUST   => 'Must Have',
            self::SHOULD => 'Should Have',
            self::COULD  => 'Could Have',
            self::WONT   => "Won't Have",
        };
    }

    public function order(): int
    {
        return match($this) {
            self::MUST   => 1,
            self::SHOULD => 2,
            self::COULD  => 3,
            self::WONT   => 4,
        };
    }
}
