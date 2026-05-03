<?php

namespace App\Enums\Agent;

enum StageType: string
{
    case PLANNING    = 'planning';
    case DESIGN      = 'design';
    case DEV_PREP    = 'dev_prep';
    case DEVELOPMENT = 'development';
    case RELEASE     = 'release';

    public function label(): string
    {
        return match($this) {
            self::PLANNING    => '기획',
            self::DESIGN      => '디자인',
            self::DEV_PREP    => '개발 준비',
            self::DEVELOPMENT => '개발',
            self::RELEASE     => '릴리즈',
        };
    }

    public function order(): int
    {
        return match($this) {
            self::PLANNING    => 1,
            self::DESIGN      => 2,
            self::DEV_PREP    => 3,
            self::DEVELOPMENT => 4,
            self::RELEASE     => 5,
        };
    }

    public function next(): ?self
    {
        return match($this) {
            self::PLANNING    => self::DESIGN,
            self::DESIGN      => self::DEV_PREP,
            self::DEV_PREP    => self::DEVELOPMENT,
            self::DEVELOPMENT => self::RELEASE,
            self::RELEASE     => null,
        };
    }
}
