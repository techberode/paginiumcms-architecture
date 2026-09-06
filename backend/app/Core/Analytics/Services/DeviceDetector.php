<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Services;

/**
 * Detektor zariadení, OS a prehliadačov z User-Agent reťazca.
 */
class DeviceDetector
{
    private string $userAgent;

    public function __construct(?string $userAgent = null)
    {
        $this->userAgent = $userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function getDevice(): string
    {
        $ua = $this->userAgent;
        if (empty($ua)) {
            return 'Unknown';
        }

        if (strpos($ua, 'Mobile') !== false || strpos($ua, 'Android') !== false) {
            if (strpos($ua, 'iPad') !== false) {
                return 'iPad';
            }
            if (strpos($ua, 'iPhone') !== false || strpos($ua, 'iOS') !== false) {
                return 'iPhone';
            }
            return 'Mobile';
        }

        if (strpos($ua, 'Tablet') !== false || strpos($ua, 'Kindle') !== false) {
            return 'Tablet';
        }

        if (strpos($ua, 'Windows') !== false) {
            return 'PC (Windows)';
        }

        if (strpos($ua, 'Macintosh') !== false || strpos($ua, 'Mac OS X') !== false) {
            return 'PC (macOS)';
        }

        if (strpos($ua, 'Linux') !== false) {
            return 'PC (Linux)';
        }

        return 'Desktop';
    }

    public function getDeviceType(): string
    {
        $ua = $this->userAgent;
        if (empty($ua)) {
            return 'Unknown';
        }

        if (strpos($ua, 'Mobile') !== false || strpos($ua, 'Android') !== false) {
            if (strpos($ua, 'iPad') !== false) {
                return 'tablet';
            }
            if (strpos($ua, 'iPhone') !== false || strpos($ua, 'iOS') !== false) {
                return 'mobile';
            }
            return 'mobile';
        }

        if (strpos($ua, 'Tablet') !== false || strpos($ua, 'Kindle') !== false) {
            return 'tablet';
        }

        return 'desktop';
    }

    public function getOs(): string
    {
        $ua = $this->userAgent;
        if (empty($ua)) {
            return 'Unknown';
        }

        if (strpos($ua, 'Windows NT 10.0') !== false) return 'Windows 10';
        if (strpos($ua, 'Windows NT 6.3') !== false) return 'Windows 8.1';
        if (strpos($ua, 'Windows NT 6.2') !== false) return 'Windows 8';
        if (strpos($ua, 'Windows NT 6.1') !== false) return 'Windows 7';
        if (strpos($ua, 'Windows NT') !== false) return 'Windows';
        if (strpos($ua, 'Mac OS X') !== false) {
            preg_match('/Mac OS X ([0-9_]+)/', $ua, $matches);
            return 'macOS ' . (isset($matches[1]) ? str_replace('_', '.', $matches[1]) : '');
        }
        if (strpos($ua, 'iPhone') !== false || strpos($ua, 'iOS') !== false) return 'iOS';
        if (strpos($ua, 'Android') !== false) {
            preg_match('/Android ([0-9.]+)/', $ua, $matches);
            return 'Android ' . ($matches[1] ?? '');
        }
        if (strpos($ua, 'Linux') !== false) return 'Linux';
        if (strpos($ua, 'Ubuntu') !== false) return 'Ubuntu';
        if (strpos($ua, 'Chrome OS') !== false) return 'Chrome OS';

        return 'Unknown';
    }

    public function getBrowser(): string
    {
        $ua = $this->userAgent;
        if (empty($ua)) {
            return 'Unknown';
        }

        if (strpos($ua, 'Edg/') !== false) return 'Edge';
        if (strpos($ua, 'Opera') !== false || strpos($ua, 'OPR/') !== false) return 'Opera';
        if (strpos($ua, 'Chrome') !== false && strpos($ua, 'Headless') === false) {
            return 'Chrome';
        }
        if (strpos($ua, 'Safari') !== false) {
            if (strpos($ua, 'Version/') !== false) {
                preg_match('/Version\/([0-9.]+)/', $ua, $matches);
                return 'Safari ' . ($matches[1] ?? '');
            }
            return 'Safari';
        }
        if (strpos($ua, 'Firefox') !== false) {
            preg_match('/Firefox\/([0-9.]+)/', $ua, $matches);
            return 'Firefox ' . ($matches[1] ?? '');
        }
        if (strpos($ua, 'MSIE') !== false || strpos($ua, 'Trident/') !== false) {
            preg_match('/MSIE ([0-9.]+)/', $ua, $matches);
            return 'Internet Explorer ' . ($matches[1] ?? '');
        }

        return 'Unknown';
    }

    public function getPlatformLabel(): string
    {
        $ua = $this->userAgent;
        if ($ua === '') {
            return 'Unknown';
        }

        if (preg_match('/iPad/i', $ua) === 1) {
            return 'Tablet (iPad)';
        }

        if (preg_match('/iPhone|iOS/i', $ua) === 1) {
            return 'Mobile (iPhone)';
        }

        if (preg_match('/Android/i', $ua) === 1) {
            if (preg_match('/Mobile/i', $ua) === 1) {
                return 'Mobile (Android)';
            }

            return 'Tablet (Android)';
        }

        if (preg_match('/Mobile|Phone/i', $ua) === 1) {
            return 'Mobile';
        }

        if (preg_match('/Tablet|Kindle/i', $ua) === 1) {
            return 'Tablet';
        }

        if (preg_match('/Windows NT/i', $ua) === 1) {
            return 'PC (Windows)';
        }

        if (preg_match('/Macintosh|Mac OS X/i', $ua) === 1) {
            return 'PC (macOS)';
        }

        if (preg_match('/CrOS/i', $ua) === 1) {
            return 'PC (Chrome OS)';
        }

        if (preg_match('/Ubuntu/i', $ua) === 1) {
            return 'PC (Linux/Ubuntu)';
        }

        if (preg_match('/Linux/i', $ua) === 1) {
            return 'PC (Linux)';
        }

        return 'Desktop';
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getAll(): array
    {
        return [
            'device' => $this->getDevice(),
            'deviceType' => $this->getDeviceType(),
            'platformLabel' => $this->getPlatformLabel(),
            'os' => $this->getOs(),
            'browser' => $this->getBrowser(),
        ];
    }
}
