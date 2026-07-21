import type { MessageTree } from '../../types';

export const settingsEn: MessageTree = {
  "page": {
    "title": "Settings",
    "save": "Save changes",
    "saving": "Saving…",
    "loadFailed": "Failed to load settings",
    "saved": "Settings saved",
    "validationFailed": "Validation failed",
    "saveFailed": "Save failed"
  },
  "twoFactor": {
    "title": "Two-factor authentication (2FA)",
    "description": "QR code and TOTP authenticator setup lives in the account security section.",
    "link": "Go to account security"
  },
  "cache": {
    "title": "System cache",
    "description": "Manually purge cache after deploy or when the public site shows stale content. Recommended: start with “Content cache”.",
    "refresh": "Refresh status",
    "loading": "Loading cache status…",
    "loadFailed": "Failed to load cache status.",
    "fileEntries": "Files on disk",
    "pagesGeneration": "Pages generation",
    "articlesGeneration": "Articles generation",
    "feedsGeneration": "Feeds generation",
    "purgeContent": "Purge content cache",
    "purgeAll": "Purge entire cache",
    "purging": "Purging…",
    "purged": "Cache purged",
    "purgeFailed": "Cache purge failed",
    "confirmContent": "Delete content cache (pages, articles, feeds)?",
    "confirmAll": "Delete the entire cache?"
  },
  "categories": {
    "system": {
      "label": "System",
      "description": "Core CMS settings, logs, notifications, and scheduler."
    },
    "site": {
      "label": "Site",
      "description": "Content, editor, SEO, feeds, and contact forms."
    },
    "media": {
      "label": "Media",
      "description": "Media library and stock images."
    },
    "security": {
      "label": "Security",
      "description": "Login, firewall, content, upload, and SSO."
    }
  },
  "groups": {
    "general": "General",
    "content": "Content",
    "editor": "Editor",
    "smtp": "Email / SMTP",
    "notifications": "Toast notifications",
    "connectors": "Notification connectors",
    "monitoring": "Monitoring & incidents",
    "scheduler": "Job scheduler",
    "codePolicy": "Code policy",
    "comments": "Comments",
    "contact": "Contact form",
    "company": "Company details",
    "workflows": "Workflow OTP",
    "ui": "Admin UI",
    "security": "Security",
    "firewall": "Firewall (WAF)",
    "logging": "Logs",
    "feeds": "RSS & Sitemap",
    "seo": "SEO",
    "media": "Media / DAM",
    "sso": "SSO / OAuth",
    "contentSecurity": "Content security (XML/HTML)",
    "uploadSecurity": "Upload security"
  },
  "hints": {
    "security": {
      "title": "Security groups",
      "body": "Content and Upload settings apply when HTML is rendered and when files are uploaded to the media library. Keep sanitization and magic-byte checks enabled unless you have a specific reason."
    }
  },
  "fields": {
    "general": {
      "siteName": {
        "label": "Site name"
      },
      "siteDescription": {
        "label": "Site description"
      },
      "siteUrl": {
        "label": "Site URL",
        "help": "E.g. https://example.com"
      },
      "adminEmail": {
        "label": "Administrator email"
      },
      "language": {
        "label": "Admin language"
      },
      "timezone": {
        "label": "Timezone"
      },
      "maintenanceMode": {
        "label": "Maintenance mode",
        "help": "Blocks the public site except admin access."
      },
      "allowRegistration": {
        "label": "Allow registration",
        "help": "When disabled, POST /api/auth/register is blocked."
      }
    },
    "content": {
      "itemsPerPage": {
        "label": "Items per page (admin)",
        "help": "Admin lists for pages and articles."
      },
      "blogItemsPerPage": {
        "label": "Articles per page (blog)",
        "help": "Public article list pagination when there are more articles."
      },
      "showReadingTime": {
        "label": "Show estimated reading time",
        "help": "On blog cards and article detail (computed from text length)."
      },
      "storageFormat": {
        "label": "Content storage format",
        "help": "md = YAML front matter + Markdown; json = pure JSON file (Iteration 19)."
      },
      "defaultStatus": {
        "label": "Default content status"
      },
      "autoSaveInterval": {
        "label": "Auto-save interval (s)",
        "help": "How often drafts are saved (Iteration 2)."
      },
      "lockTtl": {
        "label": "Content lock TTL (s)",
        "help": "Auto-release lock after inactivity (Iteration 1)."
      }
    },
    "editor": {
      "defaultEditor": {
        "label": "Default editor"
      },
      "defaultProfilePage": {
        "label": "Default profile (pages)",
        "help": "Modular toolbar for pages (Iteration 54)."
      },
      "defaultProfileArticle": {
        "label": "Default profile (articles)",
        "help": "Modular toolbar for articles (Iteration 54)."
      },
      "spellcheck": {
        "label": "Spellcheck"
      },
      "tabSize": {
        "label": "Tab size"
      }
    },
    "comments": {
      "enabled": {
        "label": "Enable comments globally",
        "help": "When disabled, the form is hidden site-wide (except per-article overrides)."
      },
      "requireApproval": {
        "label": "Require approval globally",
        "help": "New comments wait for moderation. Can be overridden per article."
      },
      "allowGuestComments": {
        "label": "Allow guest comments",
        "help": "Visitors without an account can comment. Can be overridden per article."
      },
      "maxLength": {
        "label": "Max comment length"
      }
    },
    "contact": {
      "subjects": {
        "label": "Default message subjects",
        "help": "One subject per line — shown on the public contact form."
      },
      "allowCustomSubject": {
        "label": "Allow custom subject",
        "help": "Visitors can choose “Custom subject” and enter their own text."
      }
    },
    "company": {
      "showOnContactPage": {
        "label": "Show block on contact page"
      },
      "name": {
        "label": "Company name"
      },
      "legalName": {
        "label": "Legal form / registered name"
      },
      "ico": {
        "label": "Company ID (IČO)"
      },
      "dic": {
        "label": "Tax ID (DIČ)"
      },
      "icDph": {
        "label": "VAT ID (IČ DPH)"
      },
      "address": {
        "label": "Address"
      },
      "email": {
        "label": "Contact email"
      },
      "phone": {
        "label": "Phone"
      },
      "website": {
        "label": "Website"
      },
      "mapEmbedUrl": {
        "label": "Google Maps embed URL",
        "help": "Google Maps → Share → Embed map (iframe src value)."
      }
    },
    "ui": {
      "showListCounts": {
        "label": "Show counts in sidebar",
        "help": "Badge counts on admin modules (Iteration 42)."
      },
      "adminListPageSize": {
        "label": "Items per page (admin)",
        "help": "Default rows in admin lists (Media, Trash, comments…)."
      },
      "openLinksInNewTab": {
        "label": "Open previews and external links in a new tab",
        "help": "Applies to content preview, public site link, media download and footer external links."
      }
    },
    "security": {
      "maxLoginAttempts": {
        "label": "Max failed login attempts",
        "help": "Account/IP is temporarily blocked after exceeding this limit."
      },
      "lockoutMinutes": {
        "label": "Lockout duration (min)"
      },
      "requireTwoFactorStaff": {
        "label": "Require 2FA for editors and admins",
        "help": "When enabled, 2FA cannot be disabled for EDITOR, ADMIN and SUPER_ADMIN roles."
      },
      "passwordMinLength": {
        "label": "Minimum password length",
        "help": "Applies to registration, password change and admin user creation."
      },
      "passwordMaxLength": {
        "label": "Maximum password length"
      },
      "passwordRequireUppercase": {
        "label": "Require uppercase letter (A–Z)"
      },
      "passwordRequireLowercase": {
        "label": "Require lowercase letter (a–z)"
      },
      "passwordRequireNumbers": {
        "label": "Require digit (0–9)"
      },
      "passwordRequireSpecialChars": {
        "label": "Require special character",
        "help": "e.g. ! @ # $ % & *"
      }
    },
    "login": {
      "pageTitle": {
        "label": "Login page title",
        "help": "Empty = uses site name from General settings."
      },
      "pageDescription": {
        "label": "Login page description",
        "help": "Short text in the login/registration info panel."
      },
      "backgroundImageUrl": {
        "label": "Background image URL",
        "help": "Absolute URL or /media/… path shown behind the auth card."
      },
      "infoBullets": {
        "label": "Info bullet points",
        "help": "One line = one bullet in the side panel."
      }
    },
    "firewall": {
      "enabled": {
        "label": "Enable firewall",
        "help": "Internal WAF scans URI, query and User-Agent before handling the request."
      },
      "jailMinutes": {
        "label": "Jail duration (min)",
        "help": "Temporary IP block after exceeding the incident threshold."
      },
      "maxRetries": {
        "label": "Incidents before jail",
        "help": "Violations in the window before a temporary ban."
      },
      "permanentThreshold": {
        "label": "Permanent ban threshold",
        "help": "Jail cycles before a permanent IP block."
      },
      "jailMode": {
        "label": "Jail response",
        "help": "HTTP response mode for blocked IPs. Tarpit slows bots (max 2 s)."
      },
      "tarpitSeconds": {
        "label": "Tarpit delay (s)",
        "help": "Only when jailMode=tarpit. Not recommended >2 s (FPM worker)."
      },
      "logRetention": {
        "label": "Max incidents in log"
      }
    },
    "logging": {
      "enabled": {
        "label": "Enable logging",
        "help": "Master switch for structured logs (app, audit, event, user)."
      },
      "requestLogging": {
        "label": "Log HTTP requests",
        "help": "Each API endpoint → record with timestamp, IP, status, duration."
      },
      "minSeverity": {
        "label": "Minimum log level",
        "help": "Lower levels are not stored (HTTP access log)."
      },
      "retentionDays": {
        "label": "Log retention (days)",
        "help": "Older daily files are purged (admin Logs)."
      },
      "slowRequestMs": {
        "label": "Slow request (ms)",
        "help": "Requests above this limit are logged as WARNING."
      },
      "logAuthEndpoints": {
        "label": "Log auth endpoints",
        "help": "Login/register paths — metadata only (IP, status), no body."
      }
    },
    "feeds": {
      "enabled": {
        "label": "Enable feeds"
      },
      "title": {
        "label": "RSS channel title",
        "help": "Empty = site name from general settings."
      },
      "description": {
        "label": "RSS channel description"
      },
      "itemsLimit": {
        "label": "RSS item count"
      },
      "includePages": {
        "label": "Sitemap: pages"
      },
      "includeArticles": {
        "label": "RSS/Sitemap: articles"
      }
    },
    "seo": {
      "titleTemplate": {
        "label": "Title template",
        "help": "Placeholders: %title%, %siteName%"
      },
      "defaultDescription": {
        "label": "Default meta description"
      },
      "defaultImage": {
        "label": "Default OG image (URL)"
      },
      "robotsDefault": {
        "label": "Robots (default)"
      },
      "twitterCard": {
        "label": "Twitter card type"
      }
    },
    "media": {
      "allowedMimeTypes": {
        "label": "Allowed MIME types",
        "help": "Comma-separated. Affects Media Library upload."
      },
      "maxUploadSizeKb": {
        "label": "Max upload size (KB)",
        "help": "5120 KB = 5 MB."
      },
      "stockImagesEnabled": {
        "label": "Enable stock library",
        "help": "“Generate from library” button in Media Library."
      },
      "stockImageTopic": {
        "label": "Stock image topic",
        "help": "Images are picked by site focus (IT, food, travel…)."
      }
    },
    "sso": {
      "enabled": {
        "label": "Enable SSO login"
      },
      "defaultRole": {
        "label": "Default role (new account)"
      },
      "githubEnabled": {
        "label": "GitHub OAuth"
      },
      "githubClientId": {
        "label": "GitHub Client ID"
      },
      "githubClientSecret": {
        "label": "GitHub Client Secret"
      },
      "genericEnabled": {
        "label": "Generic OAuth2"
      },
      "genericName": {
        "label": "Generic provider name"
      },
      "genericClientId": {
        "label": "Generic Client ID"
      },
      "genericClientSecret": {
        "label": "Generic Client Secret"
      },
      "genericAuthorizeUrl": {
        "label": "Authorize URL"
      },
      "genericTokenUrl": {
        "label": "Token URL"
      },
      "genericUserInfoUrl": {
        "label": "UserInfo URL"
      },
      "genericScope": {
        "label": "OAuth scope"
      }
    },
    "smtp": {
      "enabled": {
        "label": "Enable SMTP"
      },
      "host": {
        "label": "SMTP host"
      },
      "port": {
        "label": "SMTP port"
      },
      "encryption": {
        "label": "Encryption"
      },
      "username": {
        "label": "SMTP username"
      },
      "password": {
        "label": "SMTP password",
        "help": "Stored in settings.json; never exposed via public API."
      },
      "fromEmail": {
        "label": "From email"
      },
      "fromName": {
        "label": "From name"
      }
    },
    "notifications": {
      "toastEnabled": {
        "label": "Enable toast notifications"
      },
      "toastPosition": {
        "label": "Toast position"
      },
      "toastDuration": {
        "label": "Default duration (ms)"
      },
      "toastDebugMode": {
        "label": "Debug mode (longer toasts, console log)",
        "help": "Useful when developing modules, code editor, and content workflows."
      }
    },
    "connectors": {
      "emailEnabled": {
        "label": "Email channel (SMTP)"
      },
      "ntfyEnabled": {
        "label": "Enable ntfy"
      },
      "ntfyServer": {
        "label": "ntfy server URL"
      },
      "ntfyTopic": {
        "label": "ntfy topic"
      },
      "ntfyAuthMode": {
        "label": "ntfy authentication",
        "help": "Use token for ntfy.sh ACL topics or Basic for self-hosted instances."
      },
      "ntfyAccessToken": {
        "label": "ntfy access token"
      },
      "ntfyUsername": {
        "label": "ntfy username (Basic auth)"
      },
      "ntfyPassword": {
        "label": "ntfy password (Basic auth)"
      },
      "discordEnabled": {
        "label": "Enable Discord webhook"
      },
      "discordWebhookUrl": {
        "label": "Discord webhook URL"
      },
      "telegramEnabled": {
        "label": "Enable Telegram bot"
      },
      "telegramBotToken": {
        "label": "Telegram bot token"
      },
      "telegramChatId": {
        "label": "Telegram chat ID"
      },
      "webhookEnabled": {
        "label": "Enable generic webhook"
      },
      "webhookUrl": {
        "label": "Webhook URL"
      },
      "webhookSecret": {
        "label": "Webhook secret (optional)"
      },
      "webhookAuthHeader": {
        "label": "Webhook auth header name",
        "help": "HTTP header used to send webhookSecret when set."
      }
    },
    "monitoring": {
      "alertsEnabled": {
        "label": "Enable incident alerts"
      },
      "alertEmail": {
        "label": "Fallback alert email",
        "help": "Used when connectors are off; defaults to admin email."
      },
      "notifyFailedLogin": {
        "label": "Alert on failed login"
      },
      "notifySecurityIncident": {
        "label": "Alert on security audit events"
      },
      "notifyTrafficSpike": {
        "label": "Alert on traffic spike"
      },
      "trafficSpikeThreshold": {
        "label": "Traffic spike threshold (visits/hour)"
      },
      "minSeverity": {
        "label": "Minimum audit severity"
      },
      "reportsEnabled": {
        "label": "Enable scheduled monitoring reports",
        "help": "Requires cron: php backend/bin/console monitoring:run-schedule"
      },
      "reportInterval": {
        "label": "Report interval"
      },
      "reportTime": {
        "label": "Send time (HH:MM)",
        "help": "Used for daily and weekly reports (site timezone)."
      },
      "reportWeekday": {
        "label": "Weekly report day"
      },
      "reportMinute": {
        "label": "Hourly report minute (0–59)",
        "help": "For hourly interval – minute past each hour."
      },
      "reportConnector": {
        "label": "Report connector"
      },
      "reportIncludeAnalytics": {
        "label": "Report: analytics stats"
      },
      "reportIncludeHealth": {
        "label": "Report: system health"
      },
      "reportIncludeFlatFile": {
        "label": "Report: flat-file counts"
      },
      "notifyLogErrors": {
        "label": "Alert on log ERROR/CRITICAL"
      },
      "notifyLogWarnings": {
        "label": "Alert on log WARNING"
      },
      "logIncidentConnector": {
        "label": "Log incident connector"
      }
    },
    "scheduler": {
      "enabled": {
        "label": "Enable job scheduler",
        "help": "Master switch for scheduler:run CLI. Individual jobs can still be toggled in Plánovač."
      },
      "retainRuns": {
        "label": "Retain run history entries"
      }
    },
    "codePolicy": {
      "enabled": {
        "label": "Enable code policy checks"
      },
      "strictMode": {
        "label": "Strict extension namespace rules"
      },
      "maxFileSizeKb": {
        "label": "Max file size (KB)"
      },
      "forbiddenPhpFunctions": {
        "label": "Forbidden PHP functions",
        "help": "Comma-separated list scanned before save."
      }
    },
    "workflows": {
      "registrationOtpEnabled": {
        "label": "OTP pri registrácii",
        "help": "Nový účet vznikne až po overení e-mailového kódu (Iterácia 41)."
      },
      "commentApprovalOtpEnabled": {
        "label": "OTP pri schválení komentára",
        "help": "Editor musí potvrdiť schválenie komentára kódom z mailu."
      },
      "publishApprovalOtpEnabled": {
        "label": "OTP pri publikácii",
        "help": "Editor musí potvrdiť publikáciu príspevku kódom z mailu."
      },
      "otpTtlMinutes": {
        "label": "Platnosť OTP kódu (min)"
      },
      "otpMaxAttempts": {
        "label": "Max. pokusov OTP"
      }
    }
  },
  "enum": {
    "language": {
      "sk": "Slovak",
      "en": "English"
    }
  }
};
