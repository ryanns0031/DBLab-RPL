<?php

namespace App\Services;

class YouTubeVideoUrl
{
    private const HOSTS = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be'];

    public static function videoId(?string $url): ?string
    {
        if (! $url || strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($url);
        if (! is_array($parts)
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || ! in_array(strtolower($parts['host'] ?? ''), self::HOSTS, true)
            || isset($parts['user'])
            || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)) {
            return null;
        }

        $host = strtolower($parts['host']);
        $path = trim($parts['path'] ?? '', '/');
        $id = null;

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $segments = explode('/', $path);
            $id = count($segments) === 1 ? $segments[0] : null;
        } elseif ($path === 'watch') {
            parse_str($parts['query'] ?? '', $query);
            $id = is_string($query['v'] ?? null) ? $query['v'] : null;
        } else {
            $segments = explode('/', $path);
            if (count($segments) === 2 && in_array($segments[0], ['embed', 'shorts'], true)) {
                $id = $segments[1];
            }
        }

        return is_string($id) && preg_match('/\A[A-Za-z0-9_-]{11}\z/', $id) === 1 ? $id : null;
    }

    public static function embedUrl(?string $url): ?string
    {
        $id = self::videoId($url);

        return $id ? 'https://www.youtube.com/embed/'.$id : null;
    }
}
