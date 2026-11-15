<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    /** SVG markup for the given text. Needs no PHP image extension. */
    public function svg(string $text, int $size = 240): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($text);
    }
}
