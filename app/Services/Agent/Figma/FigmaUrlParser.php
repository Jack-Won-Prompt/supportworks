<?php

namespace App\Services\Agent\Figma;

class FigmaUrlParser
{
    /**
     * 다양한 Figma URL 형식에서 file key 추출.
     *
     * 지원 형식:
     *   https://www.figma.com/file/ABC123/Title
     *   https://www.figma.com/design/ABC123/Title
     *   https://www.figma.com/proto/ABC123/Title
     *   https://www.figma.com/file/ABC123/Title?node-id=1%3A2
     */
    public static function parseFileKey(string $url): ?string
    {
        if (preg_match('#figma\.com/(file|design|proto)/([a-zA-Z0-9]+)#', $url, $matches)) {
            return $matches[2];
        }
        return null;
    }

    /**
     * URL에서 node-id 파라미터 추출.
     */
    public static function parseNodeId(string $url): ?string
    {
        if (preg_match('#node-id=([^&]+)#', $url, $matches)) {
            return urldecode($matches[1]);
        }
        return null;
    }

    public static function isValidFigmaUrl(string $url): bool
    {
        return self::parseFileKey($url) !== null;
    }
}
