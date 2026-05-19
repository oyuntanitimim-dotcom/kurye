<?php

declare(strict_types=1);

namespace App\Support;

/**
 * CMS rich_text için temel XSS sertleştirmesi.
 */
final class HtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><ul><ol><li><a><h2><h3><h4><blockquote><span>';

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $html = strip_tags($html, self::ALLOWED_TAGS);
        $html = preg_replace('/\s*on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html) ?? $html;
        $html = preg_replace('/javascript\s*:/iu', '', $html) ?? $html;

        return trim($html);
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    public static function sanitizeMarketingBlocks(array $blocks): array
    {
        foreach ($blocks as $i => $block) {
            if (! is_array($block)) {
                continue;
            }
            $type = (string) ($block['type'] ?? '');
            if ($type === 'rich_text' && isset($block['data']) && is_array($block['data'])) {
                $blocks[$i]['data']['html'] = self::clean(
                    isset($block['data']['html']) ? (string) $block['data']['html'] : null
                );
            }
        }

        return $blocks;
    }
}
