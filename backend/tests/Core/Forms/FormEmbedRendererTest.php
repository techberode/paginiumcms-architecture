<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Forms;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Forms\Services\FormDefinitionPolicy;
use PaginiumCMS\Core\Forms\Services\FormDefinitionRepository;
use PaginiumCMS\Core\Forms\Services\FormEmbedRenderer;
use PHPUnit\Framework\TestCase;

final class FormEmbedRendererTest extends TestCase
{
    public function testRendersIslandMarkup(): void
    {
        $baseDir = sys_get_temp_dir() . '/pag_form_render_' . uniqid('', true);
        mkdir($baseDir . '/data', 0777, true);
        $validator = new FileValidator($baseDir);
        $repository = new FormDefinitionRepository(
            new FormDefinitionPolicy(),
            new FileReader($validator),
            new FileWriter($validator)
        );
        $repository->createFromPreset('contact-lite');
        $renderer = new FormEmbedRenderer($repository);
        $html = $renderer->render('contact-lite');
        $this->assertStringContainsString('data-island="form-embed"', $html);
        $this->assertStringContainsString('data-form-id="contact-lite"', $html);
    }
}
