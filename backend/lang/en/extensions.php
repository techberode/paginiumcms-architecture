<?php

declare(strict_types=1);

return [
    'capabilities_required' => 'plugin.json must declare capabilities as an array of capability strings.',
    'capabilities_invalid' => 'plugin.json capabilities must be unique non-empty strings.',
    'capabilities_unknown' => 'Unknown plugin capability: :capability',
    'manifest_version_required' => 'plugin.json must declare manifestVersion as integer 1.',
];
