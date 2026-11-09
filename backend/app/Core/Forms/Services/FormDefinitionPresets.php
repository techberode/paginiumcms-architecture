<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Forms\Services;

use PaginiumCMS\Modules\Messages\Models\ContactMessage;

/**
 * Bundled form schemas (It.101b).
 */
final class FormDefinitionPresets
{
    /** @return list<string> */
    public static function ids(): array
    {
        return ['contact-lite', 'broken-content', 'newsletter-opt-in'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function build(string $presetId): ?array
    {
        return match ($presetId) {
            'contact-lite' => [
                'schemaVersion' => 1,
                'id' => 'contact-lite',
                'title' => 'Contact us',
                'description' => 'Short contact form for landing pages.',
                'enabled' => true,
                'preset' => 'contact-lite',
                'routing' => [
                    'channel' => 'contact',
                    'subject' => ContactMessage::SUBJECT_GENERAL,
                    'priority' => ContactMessage::PRIORITY_NORMAL,
                ],
                'fields' => [
                    ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'maxLength' => 120],
                    ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'maxLength' => 255],
                    ['key' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true, 'minLength' => 10, 'maxLength' => 5000],
                ],
            ],
            'broken-content' => [
                'schemaVersion' => 1,
                'id' => 'broken-content',
                'title' => 'Report broken content',
                'description' => 'Visitor report for broken links or missing pages.',
                'enabled' => true,
                'preset' => 'broken-content',
                'routing' => [
                    'channel' => 'broken_content',
                    'subject' => ContactMessage::SUBJECT_BROKEN_CONTENT,
                    'priority' => ContactMessage::PRIORITY_HIGH,
                ],
                'fields' => [
                    ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'maxLength' => 120],
                    ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'maxLength' => 255],
                    ['key' => 'reportedUrl', 'type' => 'url', 'label' => 'Reported URL', 'required' => true, 'maxLength' => 500],
                    ['key' => 'referer', 'type' => 'text', 'label' => 'Referer', 'required' => false, 'maxLength' => 500],
                    ['key' => 'message', 'type' => 'textarea', 'label' => 'Note', 'required' => false, 'maxLength' => 5000],
                ],
            ],
            'newsletter-opt-in' => [
                'schemaVersion' => 1,
                'id' => 'newsletter-opt-in',
                'title' => 'Newsletter signup',
                'description' => 'Lead capture with explicit consent.',
                'enabled' => true,
                'preset' => 'newsletter-opt-in',
                'routing' => [
                    'channel' => 'newsletter',
                    'subject' => ContactMessage::SUBJECT_SALES,
                    'priority' => ContactMessage::PRIORITY_NORMAL,
                ],
                'fields' => [
                    ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'maxLength' => 120],
                    ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'maxLength' => 255],
                    ['key' => 'consent', 'type' => 'checkbox', 'label' => 'I agree to receive updates.', 'required' => true],
                ],
            ],
            default => null,
        };
    }
}
