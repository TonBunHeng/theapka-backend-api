<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    /**
     * Generate an SVG QR code string.
     */
    public function generateSvg(string $data, int $size = 300): string
    {
        return (string) QrCode::format('svg')->size($size)->generate($data);
    }

    /**
     * Generate a Base64 Data URI for inline HTML rendering.
     */
    public function generateDataUri(string $data, int $size = 300): string
    {
        $svg = $this->generateSvg($data, $size);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Generate guest invitation QR payload / URL.
     */
    public function forGuest(string $slug, string $token): string
    {
        $baseUrl = config('app.url', 'http://localhost');
        return "{$baseUrl}/invitation/{$slug}/{$token}";
    }
}
