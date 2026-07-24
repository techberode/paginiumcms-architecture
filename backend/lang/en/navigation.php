<?php

declare(strict_types=1);

return [
    'updated' => 'Navigation updated',
    'invalid_payload' => 'Invalid request payload',
    'max_depth_exceeded' => 'Navigation supports at most 3 levels (Menu → Submenu → Submenu)',
    'description_too_long' => 'Menu item description is too long (max 160 characters).',
    'invalid_icon_type' => 'Invalid icon type (none, lucide, media).',
    'icon_value_required' => 'Icon value is required when an icon type is selected.',
    'invalid_media_icon' => 'Media icon must be a path to an image (JPG, PNG, WebP, SVG…).',
    'invalid_lucide_icon' => 'Invalid Lucide icon name.',
    'invalid_thumbnail_size' => 'Invalid thumbnail size (sm, md, lg).',
    'invalid_preview_scale' => 'Preview scale must be between 1.0 and 3.0.',
];
