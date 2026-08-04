<?php

namespace App\Services\Cms;

use Mews\Purifier\Facades\Purifier;

class HtmlSanitizerService
{
    /**
     * Sanitize article body HTML against the 'article' HTMLPurifier profile
     * (config/purifier.php) before it is ever saved to the database. This is
     * the only field in the project allowed to contain real HTML.
     */
    public function sanitizeArticleBody(string $html): string
    {
        return Purifier::clean($html, 'article');
    }

    /**
     * Strip all markup entirely, via HTMLPurifier's empty-allow-list
     * 'plain_text' profile rather than PHP's strip_tags(). strip_tags()
     * only removes tags and leaves element bodies (e.g. <script>...</script>
     * content) behind as visible text; the 'plain_text' profile removes
     * both. Used for fields that must remain plain text (article title,
     * excerpt) even though they share the same form submission path as the
     * HTML body field.
     */
    public function stripAllTags(string $value): string
    {
        return trim(Purifier::clean($value, 'plain_text'));
    }
}
