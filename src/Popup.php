<?php

namespace Forup\EasyLeaflet;

class Popup
{
    /** @var string */
    private $html;

    private function __construct($html)
    {
        $this->html = $html;
    }

    /**
     * Plain text, HTML-escaped. This is the safe default — use this unless
     * you have already-trusted/sanitized HTML to render.
     *
     * @return Popup
     */
    public static function text($text)
    {
        return new self(htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Raw HTML, NOT escaped. Only pass content you trust (e.g. hand-written
     * template strings) — never pass unsanitized user/CMS input here.
     *
     * @return Popup
     */
    public static function html($trustedHtml)
    {
        return new self((string) $trustedHtml);
    }

    /** @return string */
    public function getHtml()
    {
        return $this->html;
    }
}
