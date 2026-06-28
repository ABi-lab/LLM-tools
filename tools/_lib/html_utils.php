<?php
/**
 * Shared HTML processing utilities used by fetch_url and any tool that fetches web content.
 * Always loaded via require_once — safe to include from multiple tool scripts in one request.
 */

function processHtml(string $html, string $baseUrl): string {
    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $html = preg_replace('#<(script|style)[^>]*>.*?</\1>#si', '', $html);

    $links = [];
    $html  = preg_replace_callback('#<a\s[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)</a>#si', function ($m) use (&$links, $baseUrl) {
        $href    = resolveUrl($m[1], $baseUrl);
        $text    = strip_tags($m[2]);
        $links[] = "[$text]($href)";
        return ' §LINK' . (count($links) - 1) . '§ ';
    }, $html);

    $text = strip_tags($html);

    foreach ($links as $i => $link) {
        $text = str_replace("§LINK{$i}§", $link, $text);
    }

    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    $text = preg_replace('/^\s+|\s+$/m', '', $text);

    return trim($text);
}

function resolveUrl(string $href, string $baseUrl): string {
    if (preg_match('#^https?://#', $href)) return $href;
    if (str_starts_with($href, '//')) return parse_url($baseUrl, PHP_URL_SCHEME) . ':' . $href;
    if (str_starts_with($href, '/')) {
        $p = parse_url($baseUrl);
        return $p['scheme'] . '://' . $p['host'] . $href;
    }
    return rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
}

function createHttpContext(): array {
    return [
        'http' => [
            'method'          => 'GET',
            'header'          => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\nAccept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8\r\nAccept-Language: en-US,en;q=0.5\r\n",
            'follow_location' => 1,
            'max_redirects'   => 5,
            'timeout'         => 15,
        ],
        'ssl' => [
            'verify_peer'      => true,
            'verify_peer_name' => true,
        ],
    ];
}
