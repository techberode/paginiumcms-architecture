<?php

declare(strict_types=1);

return [
    'not_found' => 'Media file not found',
    'file_required' => 'File is required',
    'upload_failed' => 'Failed to upload file',
    'deleted' => 'Media file deleted',
    'updated' => 'Media file updated',
    'invalid_type' => 'Unsupported file type',
    'folder_required' => 'Folder name is required',
    'folder_created' => 'Folder created',
    'paths_required' => 'Paths list is required',
    'bulk_deleted' => 'Selected media files deleted',
    'stock_imported' => 'Stock image imported into the library',
    'stock_disabled' => 'Stock image import is disabled in settings',
    'optimized' => 'Image optimized successfully',
    'optimize_gd_required' => 'PHP GD extension is required for image optimization. Install php-gd (or rebuild the Docker PHP image) and restart PHP-FPM.',
    'optimize_gd_jpeg' => 'PHP GD was compiled without JPEG support. Reinstall php-gd with JPEG enabled.',
    'optimize_gd_png' => 'PHP GD was compiled without PNG support. Reinstall php-gd with PNG enabled.',
    'optimize_gd_webp' => 'PHP GD was compiled without WebP support. Reinstall php-gd with WebP enabled.',
    'optimize_empty' => 'Empty image file.',
    'optimize_invalid' => 'Invalid image file.',
    'optimize_invalid_dimensions' => 'Invalid image dimensions.',
    'optimize_unsupported_type' => 'Only JPEG, PNG, and WebP images can be optimized manually.',
    'optimize_decode_failed' => 'Could not decode image for optimization.',
    'optimize_encode_failed' => 'Could not re-encode optimized image.',
    'optimize_no_reduction' => 'Image is already optimally compressed (re-encoding would not reduce file size).',
    'optimize_preview_expired' => 'Optimization preview expired or is invalid. Generate a new preview.',
];
