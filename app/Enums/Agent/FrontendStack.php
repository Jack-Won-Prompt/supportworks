<?php

namespace App\Enums\Agent;

enum FrontendStack: string
{
    case HTML  = 'html';
    case REACT = 'react';
    case VUE   = 'vue';
    case BLADE = 'blade';

    public function label(): string
    {
        return match($this) {
            self::HTML  => 'HTML / Vanilla JS',
            self::REACT => 'React',
            self::VUE   => 'Vue 3',
            self::BLADE => 'Laravel Blade',
        };
    }
}
