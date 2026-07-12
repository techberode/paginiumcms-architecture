<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QRCodeGenerator
{
    public function generate(string $provisioningUri, int $size = 200): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
                                      new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $svgContent = $writer->writeString($provisioningUri);

        return 'data:image/svg+xml;base64,' . base64_encode($svgContent);
    }

    public function generateSvg(string $provisioningUri, int $size = 200): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
                                      new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        return $writer->writeString($provisioningUri);
    }
}
