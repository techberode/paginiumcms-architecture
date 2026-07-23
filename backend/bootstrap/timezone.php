<?php

declare(strict_types=1);

use PaginiumCMS\Support\AppTimezone;

/**
 * Nastaví PHP timezone hneď po načítaní .env — pred akýmkoľvek date() v logoch/audite.
 */
AppTimezone::apply(AppTimezone::fromEnvironment());
