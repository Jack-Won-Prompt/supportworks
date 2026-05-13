<?php

namespace App\Services\Agent\Figma;

use App\Models\Agent\AiAgentUserCredential;
use App\Models\User;
use App\Services\Agent\Figma\Contracts\FigmaClient;
use App\Services\Agent\Figma\Exceptions\FigmaTokenNotConfiguredException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Auth;

class FigmaClientFactory
{
    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    public function forUser(User $user): FigmaClient
    {
        $credential = AiAgentUserCredential::where('user_id', $user->id)->first();

        if (!$credential || !$credential->hasPat()) {
            throw new FigmaTokenNotConfiguredException();
        }

        return new FigmaApiClient(
            personalAccessToken: $credential->getFigmaPat(),
            cache:               $this->cache,
        );
    }

    public function forCurrentUser(): FigmaClient
    {
        /** @var User $user */
        $user = Auth::user();
        return $this->forUser($user);
    }
}
