<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\StaticSite;

use PaginiumCMS\Core\FlatFile\Models\Content;

/**
 * Optional compile hook after SSOT write. Must not roll back content (It.48).
 */
final class StaticSiteDispatcher
{
    public function __construct(
        private StaticSiteSettings $settings,
        private StaticSiteCompiler $compiler,
    ) {
    }

    public function afterContentStored(Content $content): void
    {
        if (!$this->settings->autoRebuildOnWrite()) {
            return;
        }

        try {
            $type = $this->compiler->typeOf($content);
            if ($content->getStatus() === 'published') {
                $this->compiler->writePublished($content, $type);
            } else {
                $this->compiler->removeOne($type, $content->getSlug());
            }
        } catch (\Throwable) {
            // Derived tree must not undo a successful SSOT write.
        }
    }

    public function afterContentDeleted(Content $content): void
    {
        try {
            $this->compiler->removeOne($this->compiler->typeOf($content), $content->getSlug());
        } catch (\Throwable) {
            // Delete of SSOT already succeeded.
        }
    }
}
