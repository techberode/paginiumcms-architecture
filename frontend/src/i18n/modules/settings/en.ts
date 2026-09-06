import type { MessageTree } from '../../types';

export const settingsEn: MessageTree = {
  "page": {
    "title": "Settings",
    "save": "Save changes",
    "saving": "Saving…",
    "loadFailed": "Failed to load settings",
    "saved": "Settings saved",
    "validationFailed": "Validation failed",
    "saveFailed": "Save failed",
    "groupCount": ":count groups"
  },
  "helpTooltip": {
    "toggle": "Show detailed help"
  },
  "twoFactor": {
    "title": "Two-factor authentication (2FA)",
    "description": "QR code and TOTP authenticator setup lives in the account security section.",
    "link": "Go to account security"
  },
  "timezoneSelect": {
    "placeholder": "Select a timezone…",
    "searchPlaceholder": "Search city or timezone…",
    "noResults": "No timezone found.",
    "common": "Common",
    "all": "All timezones",
    "dstActive": "Daylight saving time is currently active in this zone.",
    "dstInactive": "Daylight saving time is currently inactive in this zone."
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
    "maintenance": "Maintenance mode",
    "content": "Content",
    "editor": "Editor",
    "navigationUi": "Navigation (UI)",
    "navigation": "Navigation layout",
    "smtp": "Email / SMTP",
    "notifications": "Toast notifications",
    "connectors": "Notification connectors",
    "monitoring": "Monitoring & incidents",
    "analytics": "Analytics retention",
    "scheduler": "Job scheduler",
    "systemUpdate": "System update (deploy)",
    "codePolicy": "Code policy",
    "engine": "Hybrid Engine",
    "comments": "Comments",
    "contact": "Contact form",
    "newsletter": "Newsletter",
    "marketing": "Marketing & social",
    "gallery": "Feature gallery",
    "company": "Company details",
    "privacy": "Privacy & cookies",
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
    "uploadSecurity": "Upload security",
    "branding": "Logo & favicon",
    "appearance": "Appearance",
    "layout": "Page layout",
    "accessControl": "Role permissions",
    "cmsInfo": "PaginiumCMS – info"
  },
  "cmsInfo": {
    "loadFailed": "Could not load CMS information.",
    "tagline": "Headless flat-file CMS — open source, no fees.",
    "version": "Version",
    "phpVersion": "PHP runtime",
    "licenseTitle": "License",
    "licenseBody": "PaginiumCMS is distributed under the MIT open-source license. You may fork, modify, and deploy the code — it must not be offered as a paid product (see project philosophy).",
    "licenseLink": "license text on GitHub",
    "localesTitle": "Language versions",
    "localesBody": "Built-in admin and public site languages. Add more locales in the translation module.",
    "localeBuiltin": "built-in",
    "stackTitle": "Technology stack",
    "linksTitle": "Links",
    "linkRepository": "GitHub repository",
    "linkDocs": "Documentation",
    "linkPhilosophy": "Project philosophy",
    "linkChangelog": "Changelog (release history)",
    "footer": "Version updates with each release tag. This section is read-only."
  },
  "engine": {
    "probeTitle": "Engine capability probe",
    "probeIntro": "Current deployment profile and installed Hybrid Engine capabilities. Future modes are shown as unavailable — not as working switches.",
    "deploymentMode": "Deployment mode",
    "storageDriver": "Storage driver",
    "cacheProbeTitle": "Cache layer probe",
    "cacheDriver": "Cache driver",
    "cacheHealth": "Cache health",
    "gitProbeTitle": "Git publish probe",
    "gitProbeStatus": "Git publish status",
    "gitProbeStrategy": "Configured strategy",
    "performanceGuardTitle": "Performance Guard (APM)",
    "performanceGuardIntro": "In-request latency and I/O sampling. Disabled by default — tune budgets for your hardware. Does not replace host metrics.",
    "performanceGuardOverhead": "Overhead grows with sample rate; ring buffer retains the latest 500 route templates without content payloads.",
    "docsLink": "Hybrid Engine architecture documentation"
  },
  "appearance": {
    "defaultBadge": "Default",
    "schemesTitle": "Color schemes",
    "schemesHint": "Choose the public site palette. Each scheme includes light and dark variants.",
    "modeTitle": "Appearance mode",
    "modes": {
      "light": "Light",
      "dark": "Dark",
      "system": "System"
    },
    "allowUserToggle": "Allow visitors to switch light / dark mode",
    "previewTemplateTitle": "Preview layout wireframe",
    "previewTitle": "Page preview",
    "schemes": {
      "indigoClassic": {
        "name": "Indigo Classic",
        "description": "Indigo / violet on slate — default Paginium, SaaS and tech."
      },
      "oceanSlate": {
        "name": "Ocean Slate",
        "description": "Teal / cyan on cool gray — calm tech, fintech."
      },
      "forestSage": {
        "name": "Forest Sage",
        "description": "Emerald / sage on warm neutral — eco, wellness, B2B."
      },
      "sunsetRose": {
        "name": "Sunset Rose",
        "description": "Coral / rose on cream — magazine, lifestyle, creative."
      },
      "monoZinc": {
        "name": "Mono Zinc",
        "description": "Zinc neutrals + accent — editorial, minimal, portfolio."
      },
      "terminalBreach": {
        "name": "Terminal Breach",
        "description": "SOC-terminal green on dark slate — security ops, cyber presentation."
      }
    }
  },
  "layout": {
    "buildersTitle": "Layout builder",
    "buildersHint": "Choose how editors build page structure. All modes share the same layout data later — this switch only changes the UI.",
    "templatesTitle": "Default page layout template",
    "templatesHint": "Used for new pages and the live layout preview below (structure, not chrome templates like Home/Contact).",
    "previewTitle": "Layout preview",
    "developerRequiresAdmin": "Restrict Developer (Monaco) builder to ADMIN and SUPER_ADMIN",
    "developerLocked": "Available only to ADMIN / SUPER_ADMIN while this restriction is on.",
    "builders": {
      "templates": {
        "name": "Templates",
        "description": "Pick a named structure and fill slots — best for quick pages."
      },
      "shortcodes": {
        "name": "Shortcodes",
        "description": "Compose with shortcodes in Markdown / WYSIWYG."
      },
      "outline": {
        "name": "Block outline",
        "description": "Reorder sections without code (optional later slice)."
      },
      "developer": {
        "name": "Developer",
        "description": "Monaco for layout JSON and shortcode definitions."
      }
    },
    "templates": {
      "single": {
        "name": "Single column",
        "description": "Header → body → footer"
      },
      "heroContent": {
        "name": "Hero + content",
        "description": "Header → hero → cards → footer"
      },
      "twoColumn": {
        "name": "Two column",
        "description": "Main column + sidebar"
      },
      "landing": {
        "name": "Landing",
        "description": "Hero → feature grid → CTA"
      },
      "blogArticle": {
        "name": "Blog article",
        "description": "Title, meta strip, long body"
      }
    }
  },
  "marketing": {
    "social": {
      "title": "Footer social links",
      "description": "Add icons with links for project promotion (GitHub, X, LinkedIn, …). Up to 12 entries.",
      "platform": "Platform",
      "url": "URL",
      "email": "Email address",
      "label": "Accessible label",
      "enabled": "Show in footer",
      "add": "Add link",
      "remove": "Remove link",
      "moveUp": "Move up",
      "moveDown": "Move down",
      "platforms": {
        "github": "GitHub",
        "gitlab": "GitLab",
        "twitter": "X / Twitter",
        "facebook": "Facebook",
        "instagram": "Instagram",
        "linkedin": "LinkedIn",
        "youtube": "YouTube",
        "mastodon": "Mastodon",
        "discord": "Discord",
        "website": "Website",
        "email": "Email",
        "rss": "RSS feed"
      }
    }
  },
  "privacy": {
    "panel": {
      "title": "Cookies & GDPR page editor",
      "description": "Configure the public banner, /cookies page content, legal blocks, and contact details.",
      "preview": "Preview policy page",
      "bannerTitle": "Cookie banner",
      "pageTitle": "Policy page header",
      "blocksTitle": "Custom GDPR blocks",
      "blocksHint": "Add legal sections (controller, legal basis, retention, subprocessors, etc.). Plain text only.",
      "blocksEmpty": "No custom blocks yet.",
      "addBlock": "Add block",
      "blockLabel": "Block {index}",
      "blockTitlePlaceholder": "Section title (e.g. Data controller)",
      "blockBodyPlaceholder": "Section body — plain text, line breaks preserved.",
      "removeBlock": "Remove block",
      "moveUp": "Move up",
      "moveDown": "Move down",
      "contactTitle": "GDPR contact",
      "contactHint": "Shown on /cookies. Empty fields fall back to Company settings where available.",
      "sectionsTitle": "Built-in page sections"
    }
  },
  "accessControl": {
    "superAdminTitle": "Super administrator only",
    "superAdminHint": "Permission mapping for ADMIN, EDITOR, and USER roles plus path ACL rules can only be changed by SUPER_ADMIN. SUPER_ADMIN always has full access. Manage custom roles in",
    "rolesManagerLink": "Security → Custom roles",
    "restrictedTitle": "Restricted access",
    "restrictedHint": "This settings group is visible only to the super administrator.",
    "rolesTitle": "Permissions by role",
    "pathAclTitle": "Path ACL (content paths)",
    "pathAclEnabled": "Enable path ACL",
    "pathAclHint": "Restrict access to selected flat-file content paths (e.g. content/pages/restricted/*).",
    "permissions": {
      "user:manage": "Manage users",
      "content:manage": "Manage all content",
      "content:create": "Create content",
      "content:edit": "Edit content",
      "content:delete": "Delete content",
      "content:view": "View content",
      "media:manage": "Manage all media",
      "media:upload": "Upload media",
      "media:delete": "Delete media",
      "settings:manage": "Manage settings",
      "git:publish": "Git publish",
      "gallery:manage": "Manage gallery",
      "logs:view": "View logs",
      "metrics:read": "Read metrics",
      "api-keys:manage": "Manage API keys",
      "redirects:manage": "Manage redirects",
      "webhooks:manage": "Manage webhooks",
      "profile:edit": "Edit profile"
    }
  },
  "hints": {
    "security": {
      "title": "Security groups",
      "body": "Content and Upload settings apply when HTML is rendered and when files are uploaded to the media library. Keep sanitization and magic-byte checks enabled unless you have a specific reason."
    }
  },
  "editorComponents": {
    "title": "Custom components by profile",
    "help": "Choose which plugin blocks are available in each editor profile.",
    "empty": "No custom components registered. Enable a plugin with editor.components in its manifest.",
    "disabledHint": "Turn on “Enable custom editor components” above to configure profiles.",
    "profile": "Profile",
    "toggle": "Enable {component} for {profile}"
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
        "label": "Timezone",
        "help": "Used for logs, audit trail, and scheduled reports."
      },
      "timezoneDst": {
        "label": "Daylight saving time (DST)",
        "help": "Enabled = automatic DST correction for the selected timezone. Disabled = fixed standard (winter) offset."
      },
      "allowRegistration": {
        "label": "Allow registration",
        "help": "When disabled, POST /api/auth/register is blocked. Registration is always disabled during maintenance modes."
      }
    },
    "maintenance": {
      "mode": {
        "label": "Active mode",
        "help": "Only one mode can be active at a time.",
        "tooltip": "Off = public site runs normally. Coming Soon = teaser page with optional newsletter; login and admin still work. Under Maintenance = visitors see the maintenance page; registration is blocked and the public API may return 503 for non-admin routes depending on middleware."
      },
      "heroImageUrl": {
        "label": "Background image (URL)",
        "help": "Absolute URL or /storage/… path — optional background for both maintenance pages. Pick from media or upload from disk."
      },
      "newsletterEnabled": {
        "label": "Enable newsletter",
        "help": "Enabled = newsletter signup on the Coming Soon page. Disabled = form hidden."
      },
      "newsletterHint": {
        "label": "Newsletter hint text"
      },
      "comingSoonBadge": {
        "label": "Coming Soon – badge"
      },
      "comingSoonTitle": {
        "label": "Coming Soon – title"
      },
      "comingSoonSubtitle": {
        "label": "Coming Soon – subtitle"
      },
      "comingSoonBody": {
        "label": "Coming Soon – body"
      },
      "maintenanceBadge": {
        "label": "Maintenance – badge"
      },
      "maintenanceTitle": {
        "label": "Maintenance – title"
      },
      "maintenanceSubtitle": {
        "label": "Maintenance – subtitle"
      },
      "maintenanceBody": {
        "label": "Maintenance – body"
      },
      "maintenanceShowContactForm": {
        "label": "Show contact form",
        "help": "Enabled = visitors can send a message during maintenance. Disabled = info text only."
      },
      "maintenanceContactSubject": {
        "label": "Maintenance message subject"
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
        "help": "md = YAML front matter + Markdown; json = pure JSON file."
      },
      "defaultStatus": {
        "label": "Default content status",
        "help": "New pages/articles start as draft or published immediately."
      },
      "autoSaveInterval": {
        "label": "Auto-save interval (s)",
        "help": "How often drafts are saved."
      },
      "lockTtl": {
        "label": "Content lock TTL (s)",
        "help": "Auto-release lock after inactivity."
      },
      "autoTagEnabled": {
        "label": "Tag suggestions in editor",
        "help": "Enables “Suggest tags” in the article editor."
      },
      "autoTagMax": {
        "label": "Max suggested tags",
        "help": "How many tags the generator returns at once."
      },
      "autoDescriptionEnabled": {
        "label": "Meta description generator",
        "help": "Enables “Generate description” in the editor."
      },
      "autoDescriptionMaxLength": {
        "label": "Max meta description length (chars)",
        "help": "Recommended 150–160 characters for SEO."
      },
      "localeFallbackEnabled": {
        "label": "Enable locale fallback",
        "help": "When a requested language is missing, fall back to defaultLocale or site language.",
        "tooltip": "Affects public content reads only. If an article has no Slovak variant, the CMS can serve the default locale document instead of 404. Does not auto-translate — it reuses an existing locale file."
      },
      "localeNegotiationEnabled": {
        "label": "Accept-Language for public content",
        "help": "Public GET may pick locale from Accept-Language when ?locale= is absent.",
        "tooltip": "Browsers send Accept-Language on first visit. When enabled, listing and detail endpoints may choose the best matching locale before fallback rules apply."
      },
      "staleReviewMonths": {
        "label": "Stale content threshold (months)",
        "help": "Published content older than this (since last review/edit) is flagged stale. 0 = off."
      }
    },
    "editor": {
      "defaultEditor": {
        "label": "Default editor",
        "help": "Which editor opens for new content (Markdown or WYSIWYG)."
      },
      "defaultProfilePage": {
        "label": "Default profile (pages)",
        "help": "Modular toolbar for pages."
      },
      "defaultProfileArticle": {
        "label": "Default profile (articles)",
        "help": "Modular toolbar for articles."
      },
      "spellcheck": {
        "label": "Spellcheck",
        "help": "Enabled = browser underlines spelling errors in the editor. Disabled = no spellcheck."
      },
      "tabSize": {
        "label": "Tab size"
      },
      "customComponentsEnabled": {
        "label": "Enable custom editor components",
        "help": "Plugins can register custom blocks for Markdown and WYSIWYG."
      },
      "profileCustomComponents": {
        "label": "Custom components by profile (JSON)",
        "help": "Managed in the matrix below."
      }
    },
    "navigationUi": {
      "defaultPreviewScale": {
        "label": "Default hover preview scale (×10)",
        "help": "Value 15 = 1.5× scale on menu icon hover."
      },
      "maxTooltipWidthPx": {
        "label": "Max tooltip width (px)",
        "help": "Width cap for hover preview in dropdown menus."
      },
      "enableHoverAnimations": {
        "label": "Hover preview animations",
        "help": "Respects prefers-reduced-motion — static preview when disabled."
      }
    },
    "navigation": {
      "placement": {
        "label": "Menu placement",
        "help": "Top bar, side cascade tree, or both on desktop."
      },
      "sideBreakpoint": {
        "label": "Side nav breakpoint",
        "help": "Below this width the side column hides; mobile drawer stays in the header."
      },
      "expandAnimation": {
        "label": "Expand/collapse animation",
        "help": "Cascade motion in the side menu. Respects prefers-reduced-motion."
      },
      "maxDepth": {
        "label": "Maximum menu depth",
        "help": "SUPER_ADMIN may set 4 levels; default is 3."
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
      },
      "spamHeuristicsEnabled": {
        "label": "Spam heuristics",
        "help": "Honeypot + score (links, disposable email, velocity). When off, honeypot remains."
      },
      "spamMaxLinks": {
        "label": "Spam: max links in text"
      },
      "spamVelocityMaxPerHour": {
        "label": "Spam: max comments / IP / hour"
      },
      "spamQuarantineThreshold": {
        "label": "Spam: quarantine threshold (score)"
      },
      "spamRejectThreshold": {
        "label": "Spam: reject threshold (score)"
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
    "marketing": {
      "demoFooterLinkEnabled": {
        "label": "Show demo link in footer",
        "help": "Production only — links to the public demo instance."
      },
      "demoUrl": {
        "label": "Demo instance URL",
        "help": "Target URL for the “Try the CMS” footer block."
      },
      "socialLinksEnabled": {
        "label": "Show social links in footer",
        "help": "Icon row in the public footer (GitHub, X, …)."
      },
      "socialLinksJson": {
        "label": "Social links (JSON)",
        "help": "Managed by the visual editor below."
      }
    },
    "newsletter": {
      "footerEnabled": {
        "label": "Enable footer newsletter",
        "help": "Enabled = compact signup form in the public site footer."
      },
      "footerHint": {
        "label": "Footer hint text",
        "help": "Short description under the newsletter heading in the footer."
      },
      "fromEmail": {
        "label": "Sender email",
        "help": "Prepared for outbound mail; falls back to SMTP settings when empty."
      },
      "fromName": {
        "label": "Sender name",
        "help": "Display name for future newsletter emails."
      },
      "replyTo": {
        "label": "Reply-To",
        "help": "Optional address for replies to newsletter messages."
      },
      "enabledPreferences": {
        "label": "Subscription types in forms",
        "help": "One key per line: weekly_digest, new_article, cms_release, general_news."
      },
      "requireConsentCheckbox": {
        "label": "Require consent checkbox",
        "help": "Visitors must explicitly confirm consent before subscribing."
      },
      "sendEnabled": {
        "label": "Enable email sending",
        "help": "Master switch for weekly digest and new-article notifications. Requires configured SMTP/email channel."
      },
      "weeklyDigestEnabled": {
        "label": "Weekly digest",
        "help": "Sends a summary of published articles to subscribers with the weekly_digest preference."
      },
      "newArticleEnabled": {
        "label": "New article notification",
        "help": "Sends an email when an article is published to subscribers with the new_article preference."
      },
      "cmsReleaseEnabled": {
        "label": "CMS release campaigns",
        "help": "Enables manual release announcements to subscribers with the cms_release preference."
      },
      "instantArticleCooldownHours": {
        "label": "Instant mail cooldown (hours)",
        "help": "At most one instant email per subscriber within this period."
      },
      "sendBatchLimitPerRun": {
        "label": "Send limit per run",
        "help": "Number of emails sent in one cron or manual run."
      },
      "requireDoubleOptIn": {
        "label": "Double opt-in (email confirmation)",
        "help": "New subscribers stay pending until they click the confirmation link in email."
      },
      "confirmTokenTtlHours": {
        "label": "Confirmation link validity (hours)",
        "help": "After expiry the subscriber must request a new confirmation email."
      }
    },
    "company": {
      "showOnContactPage": {
        "label": "Show block on contact page",
        "help": "Enabled = company details appear on the contact page. Disabled = block hidden."
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
        "help": "Badge counts on admin modules."
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
        "help": "When enabled, 2FA cannot be disabled for EDITOR, ADMIN and SUPER_ADMIN roles.",
        "tooltip": "Editors and admins without a configured authenticator will be prompted to set up 2FA on next login. Does not affect public visitors or USER role accounts."
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
    "branding": {
      "logoUrl": {
        "label": "Site logo (URL)",
        "help": "Shown in the public navbar, admin sidebar, and maintenance pages."
      },
      "faviconUrl": {
        "label": "Favicon (URL)",
        "help": "Browser tab icon. ICO, PNG or SVG recommended."
      },
      "imagePicker": {
        "pickFromMedia": "Pick from media",
        "uploadLocal": "Upload from disk",
        "uploading": "Uploading…",
        "remove": "Remove",
        "previewAlt": "Preview",
        "mediaModalTitle": "Select image",
        "uploadAlt": "Branding upload",
        "invalidType": "Unsupported file type",
        "uploadFailed": "Upload failed"
      }
    },
    "privacy": {
      "cookieBannerEnabled": {
        "label": "Show cookie banner",
        "help": "Enabled = visitors see a GDPR cookie consent bar on first visit."
      },
      "cookieBannerText": {
        "label": "Cookie banner text"
      },
      "cookiePolicyUrl": {
        "label": "Cookie / privacy policy URL",
        "help": "Optional. Empty = built-in page at /cookies. Relative path or external URL."
      },
      "cookieShowRejectButton": {
        "label": "Show “Reject optional” button",
        "help": "Enabled = visitors can reject optional cookies while keeping necessary ones."
      },
      "cookiePolicyPageTitle": {
        "label": "Cookie page title",
        "help": "Empty = default translated title on /cookies."
      },
      "cookiePolicyIntro": {
        "label": "Cookie page intro",
        "help": "Custom introduction shown under the page title. Empty = default i18n text."
      },
      "cookiePolicySectionsJson": {
        "label": "GDPR content blocks (JSON)",
        "help": "Managed by the visual block editor below."
      },
      "privacyContactName": {
        "label": "Name or controller",
        "help": "Displayed on /cookies. Empty = Company legal name or display name."
      },
      "privacyContactEmail": {
        "label": "E-mail",
        "help": "Empty = Company e-mail."
      },
      "privacyContactPhone": {
        "label": "Phone",
        "help": "Empty = Company phone."
      },
      "privacyContactAddress": {
        "label": "GDPR contact — address",
        "help": "Empty = Company settings address."
      },
      "cookiePolicyShowCategoriesTable": {
        "label": "Show cookie categories table"
      },
      "cookiePolicyShowStorageInventory": {
        "label": "Show technical storage inventory"
      },
      "cookiePolicyShowDefaultRights": {
        "label": "Show “Your choices” section"
      },
      "cookiePolicyShowManagePanel": {
        "label": "Show consent management panel"
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
        "help": "Absolute URL or /storage/… path shown behind the auth card. Pick from media library or upload from disk."
      },
      "backgroundPicker": {
        "pickFromMedia": "Pick from media",
        "uploadLocal": "Upload from disk",
        "uploading": "Uploading…",
        "remove": "Remove background",
        "previewAlt": "Login background preview",
        "mediaModalTitle": "Select login background",
        "uploadAlt": "Login page background",
        "uploadFailed": "Background upload failed.",
        "invalidType": "Only image types allowed by uploadSecurity settings."
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
      "scanRequestBody": {
        "label": "Scan POST/JSON body",
        "help": "Enabled = WAF checks body of mutating requests (editor API is excluded). Disabled = URI and headers only.",
        "tooltip": "When enabled, JSON bodies on POST/PUT/PATCH are scanned for injection patterns. Content editor routes (/api/pages, /api/articles, drafts, code-editor) and multipart uploads are exempt to avoid false positives on legitimate HTML/Markdown saves."
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
        "help": "HTTP response mode for blocked IPs. Tarpit slows bots (max 2 s).",
        "tooltip": "forbidden = HTTP 403 JSON; empty = connection close with minimal body; tarpit = intentional delay before response (ties up a PHP-FPM worker — keep tarpitSeconds ≤ 2)."
      },
      "tarpitSeconds": {
        "label": "Tarpit delay (s)",
        "help": "Only when jailMode=tarpit. Not recommended >2 s (FPM worker).",
        "tooltip": "Each blocked request waits this many seconds inside PHP before responding. High values reduce throughput under bot floods but also consume worker slots."
      },
      "logRetention": {
        "label": "Max incidents in log"
      },
      "blockEmptyUserAgent": {
        "label": "Block empty User-Agent",
        "help": "Enabled = requests without a User-Agent header are logged as WAF incidents (common for scrapers)."
      },
      "blockScraperTools": {
        "label": "Block scraper tools (curl, wget…)",
        "help": "Enabled = WAF blocks known CLI/scraper User-Agent strings. Search bots (Googlebot…) are never blocked."
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
        "help": "Older daily files are purged manually (Logs) and by scheduler job maintenance.cleanup (app, audit, event, user)."
      },
      "slowRequestMs": {
        "label": "Slow request (ms)",
        "help": "Requests above this limit are logged as WARNING."
      },
      "logAuthEndpoints": {
        "label": "Log auth endpoints",
        "help": "Login/register paths — metadata only (IP, status), no body."
      },
      "includeResponseSize": {
        "label": "Log response size (size_bytes)",
        "help": "Adds size_bytes to http_access log entries (Content-Length or body size).",
        "tooltip": "Response-size diagnostic. Helps spot slow responses that still transfer little data (session lock, cache miss). Does not log response bodies — only byte count."
      }
    },
    "feeds": {
      "enabled": {
        "label": "Enable feeds",
        "help": "Enabled = RSS and sitemap.xml are available. Disabled = feeds return 404."
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
        "label": "Sitemap: pages",
        "help": "Enabled = static pages are listed in the sitemap. Disabled = pages omitted."
      },
      "includeArticles": {
        "label": "RSS/Sitemap: articles",
        "help": "Enabled = articles in RSS and sitemap. Disabled = articles omitted from feeds."
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
      "allowSearchIndexing": {
        "label": "Allow search engine indexing",
        "help": "When disabled, robots.txt uses Disallow: / and meta tags use noindex (except pages with explicit noIndex)."
      },
      "twitterCard": {
        "label": "Twitter card type"
      }
    },
    "media": {
      "storageDriver": {
        "label": "Media storage driver",
        "help": "local = flat-file binaries under media/. S3 appears in UI but falls back to local until the driver ships."
      },
      "s3Endpoint": {
        "label": "S3 endpoint URL"
      },
      "s3Region": {
        "label": "S3 region"
      },
      "s3Bucket": {
        "label": "S3 bucket"
      },
      "s3KeyId": {
        "label": "S3 access key ID"
      },
      "s3Secret": {
        "label": "S3 secret key"
      },
      "s3PathStyle": {
        "label": "S3 path-style URLs",
        "help": "Enable for MinIO and compatible services without virtual-hosted bucket URLs."
      },
      "s3PublicBaseUrl": {
        "label": "Public base URL for S3 objects"
      },
      "s3Visibility": {
        "label": "S3 object visibility"
      },
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
        "label": "Enable SSO login",
        "help": "Enabled = external login is available (per enabled providers). Disabled = local accounts only.",
        "tooltip": "When off, only email/password login works. When on, configured OAuth providers appear on the login page; new SSO users receive the default role below unless mapped otherwise."
      },
      "defaultRole": {
        "label": "Default role (new account)"
      },
      "githubEnabled": {
        "label": "GitHub OAuth",
        "help": "Enabled = “Sign in with GitHub” button shown. Disabled = GitHub login hidden."
      },
      "githubClientId": {
        "label": "GitHub Client ID"
      },
      "githubClientSecret": {
        "label": "GitHub Client Secret"
      },
      "genericEnabled": {
        "label": "Generic OAuth2",
        "help": "Enabled = generic OAuth2 provider using settings below. Disabled = provider off."
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
        "label": "Enable SMTP",
        "help": "Enabled = system sends email (OTP, notifications, contact). Disabled = sending off."
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
        "label": "Enable toast notifications",
        "help": "Enabled = short messages on save/errors in admin UI. Disabled = no toasts."
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
        "label": "Email channel (SMTP)",
        "help": "Enabled = incidents and reports can go by email (requires SMTP on). Disabled = email channel unused."
      },
      "ntfyEnabled": {
        "label": "Enable ntfy",
        "help": "Enabled = push notifications via ntfy. Disabled = ntfy channel off."
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
        "label": "Enable Discord webhook",
        "help": "Enabled = incidents sent to Discord webhook. Disabled = webhook not called."
      },
      "discordWebhookUrl": {
        "label": "Discord webhook URL"
      },
      "telegramEnabled": {
        "label": "Enable Telegram bot",
        "help": "Enabled = incidents sent to Telegram chat. Disabled = bot unused."
      },
      "telegramBotToken": {
        "label": "Telegram bot token"
      },
      "telegramChatId": {
        "label": "Telegram chat ID"
      },
      "webhookEnabled": {
        "label": "Enable generic webhook",
        "help": "Enabled = incidents sent to custom HTTP webhook. Disabled = webhook off."
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
        "label": "Enable incident alerts",
        "help": "Enabled = system sends alerts on incidents via enabled channels. Disabled = no automatic alerts."
      },
      "alertEmail": {
        "label": "Fallback alert email",
        "help": "Used when connectors are off; defaults to admin email."
      },
      "notifyFailedLogin": {
        "label": "Alert on failed login",
        "help": "Enabled = notification on failed login. Disabled = event not reported."
      },
      "notifySecurityIncident": {
        "label": "Alert on security audit events",
        "help": "Enabled = notification on security audit events. Disabled = not sent."
      },
      "notifyTrafficSpike": {
        "label": "Alert on traffic spike",
        "help": "Enabled = alert when visit threshold exceeded. Disabled = traffic spikes ignored."
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
        "label": "Report: analytics stats",
        "help": "Enabled = report includes visit statistics. Disabled = no analytics section."
      },
      "reportIncludeHealth": {
        "label": "Report: system health",
        "help": "Enabled = report includes system health. Disabled = no health section."
      },
      "reportIncludeFlatFile": {
        "label": "Report: flat-file counts",
        "help": "Enabled = report includes page/article counts. Disabled = no content stats."
      },
      "notifyLogErrors": {
        "label": "Alert on log ERROR/CRITICAL",
        "help": "Enabled = ERROR/CRITICAL log entries trigger alerts. Disabled = log errors not reported."
      },
      "notifyLogWarnings": {
        "label": "Alert on log WARNING",
        "help": "Enabled = WARNING log entries trigger alerts. Disabled = warnings not reported."
      },
      "logIncidentConnector": {
        "label": "Log incident connector"
      }
    },
    "analytics": {
      "retentionDays": {
        "label": "Analytics data retention (days)",
        "help": "Visit, daily, and stale visitor files older than this are deleted by scheduler job maintenance.cleanup (default 90)."
      }
    },
    "scheduler": {
      "enabled": {
        "label": "Enable job scheduler",
        "help": "Master switch for scheduler:run CLI. Individual jobs can still be toggled in Scheduler."
      },
      "retainRuns": {
        "label": "Retain run history entries"
      }
    },
    "engine": {
      "deploymentMode": {
        "label": "Deployment mode",
        "help": "Only Classic mode is active. Hybrid and Git headless appear as not installed.",
        "tooltip": "Classic = flat-file SSOT on disk (default). Hybrid/Git modes are reserved for future engine iterations — changing this does not migrate data automatically."
      },
      "storageDriver": {
        "label": "Storage driver",
        "help": "Local flat-file driver (default). Remote drivers require later iterations.",
        "tooltip": "Classic SSOT = JSON/Markdown on disk under data/. Remote storage is not active in this release."
      },
      "schemaValidationEnabled": {
        "label": "JSON Schema validation",
        "help": "When enabled, admin JSON documents are validated against registered schemas before write.",
        "tooltip": "Protects flat-file data from invalid JSON shapes. Recommended on for production."
      },
      "capabilityProbeEnabled": {
        "label": "Enable capability probe",
        "help": "Expose engine capability diagnostics in admin settings.",
        "tooltip": "The panel below shows cache, Git, and storage availability. Disabling hides the probe — engine behavior is unchanged."
      },
      "cacheDriver": {
        "label": "Cache driver",
        "help": "auto = memory + file chain. Redis appears as not installed when unavailable.",
        "tooltip": "auto picks the best available driver at runtime. file persists across requests; memory is per-process only. Redis requires extension and correct env — probe panel below shows active driver."
      },
      "cacheDefaultTtlSeconds": {
        "label": "Default cache TTL (seconds)",
        "help": "Applies to new cache keys when no other TTL is specified (60–86400)."
      },
      "httpValidatorsEnabled": {
        "label": "Enable HTTP ETag / Last-Modified",
        "help": "Conditional requests on safe public GET endpoints (e.g. /api/settings/public).",
        "tooltip": "Clients may send If-None-Match / If-Modified-Since and receive 304 with no body. Reduces traffic for public settings."
      },
      "gitEnabled": {
        "label": "Enable Git publish distribution",
        "help": "Git is distribution only; SSOT stays on disk. Default off.",
        "tooltip": "When enabled, content writes can create Git commits in a configured working tree. It does not replace flat-file storage — failed Git operations do not roll back CMS writes."
      },
      "gitPublishStrategy": {
        "label": "Git publish strategy",
        "help": "disabled = no Git calls; immediate = commit per write; queued = batch release commit.",
        "tooltip": "immediate adds latency to each mutating save; queued batches commits for deploy windows. Push still requires gitPushEnabled and valid credentials on the server."
      },
      "gitPublisher": {
        "label": "Git publisher driver",
        "help": "local = server git binary. github_api deferred in this release.",
        "tooltip": "Publish runs on the server filesystem only. github_api requires token and outbound network — not active yet."
      },
      "gitRepositoryPath": {
        "label": "Git repository path",
        "help": "Absolute server path to a Git working tree containing pages/ and blog/ content. Never exposed to the frontend.",
        "tooltip": "Must be writable by the PHP user. Empty = Git publish is skipped even when enabled."
      },
      "gitRemote": {
        "label": "Git remote name",
        "help": "Allow-listed remote name (e.g. origin)."
      },
      "gitBranch": {
        "label": "Git branch",
        "help": "Allow-listed branch name for optional push."
      },
      "gitPushEnabled": {
        "label": "Push after commit",
        "help": "When enabled, successful commits attempt git push to configured remote/branch.",
        "tooltip": "Runs on the server filesystem only. Requires SSH keys or credentials available to the PHP user inside Docker — never exposed to the admin UI."
      },
      "gitCommitMessageTemplate": {
        "label": "Commit message template",
        "help": "Use {count} placeholder for number of staged files."
      },
      "performanceGuardEnabled": {
        "label": "Enable Performance Guard (APM)",
        "help": "Lightweight in-request latency and I/O sampling. Disabled by default.",
        "tooltip": "Records per-route timings to a ring buffer shown on Dashboard. Overhead grows with sample rate; does not modify content or settings automatically unless remediation mode allows cache purge."
      },
      "performanceGuardSampleRate": {
        "label": "APM sample rate",
        "help": "1.0 = every request when enabled; lower values reduce overhead.",
        "tooltip": "Use 0.1–0.3 on busy sites. Media/static routes are already excluded from skewing p95."
      },
      "performanceGuardLatencyMsWarning": {
        "label": "Latency warning (ms)"
      },
      "performanceGuardLatencyMsCritical": {
        "label": "Latency critical (ms)"
      },
      "performanceGuardBreachCount": {
        "label": "Breaches before incident"
      },
      "performanceGuardWindowMinutes": {
        "label": "Breach window (minutes)"
      },
      "performanceGuardRemediationMode": {
        "label": "Remediation mode",
        "help": "suggest = incidents only; automatic = allow-listed cache purge after probe.",
        "tooltip": "automatic never enables Redis or changes engine mode — only safe cache/content-cache purges after repeated budget breaches. suggest logs incidents for manual review."
      },
      "performanceGuardServerTiming": {
        "label": "Server-Timing header",
        "help": "Expose sess-lock/storage/app phases in DevTools (also on when APP_DEBUG).",
        "tooltip": "Adds W3C Server-Timing to HTTP responses for admins/debug. Visible in browser Network tab — disable on production if you prefer not to expose phase breakdown to clients."
      }
    },
    "contentSecurity": {
      "sanitizeHtmlOnSave": {
        "label": "Sanitize HTML on save",
        "help": "Removes dangerous tags and attributes from HTML/WYSIWYG output."
      },
      "stripExternalEntities": {
        "label": "Strip external entities (XXE)",
        "help": "Blocks external DTD and entities when parsing XML/HTML — recommended on."
      },
      "allowSvgInline": {
        "label": "Allow inline SVG in content",
        "help": "SVG may contain script — recommended off.",
        "tooltip": "Inline SVG can carry JavaScript and external references. Keep disabled unless editors are fully trusted and you accept XSS review overhead."
      },
      "allowScriptTags": {
        "label": "Allow <script> in content",
        "help": "For trusted editors only; default off.",
        "tooltip": "Enabling script tags in article/page HTML bypasses most XSS protections on render. Public pages will execute editor-supplied JavaScript."
      },
      "allowedHtmlTags": {
        "label": "Allowed HTML tags",
        "help": "Comma-separated whitelist. Layout shortcodes need div/article/section/aside/span.",
        "tooltip": "Tags not in this list are stripped on save when sanitization is on. Overly permissive lists (iframe, object) increase XSS risk."
      }
    },
    "uploadSecurity": {
      "scanMagicBytes": {
        "label": "Verify file magic bytes",
        "help": "Compares file header with declared MIME type.",
        "tooltip": "Blocks renamed executables (e.g. shell.php uploaded as image/jpeg). Recommended on for any internet-facing upload surface."
      },
      "blockDoubleExtensions": {
        "label": "Block double extensions",
        "help": "e.g. shell.php.jpg — common upload attack.",
        "tooltip": "Rejects filenames with multiple extensions where the inner segment is executable. Complements MIME and magic-byte checks."
      },
      "blockExecutables": {
        "label": "Block executable uploads",
        "help": "Rejects PHP, shell scripts, and similar types regardless of extension."
      },
      "allowedExtensions": {
        "label": "Allowed extensions",
        "help": "Without dot, comma-separated.",
        "tooltip": "Applied together with allowed MIME types and media settings. Tighten this list before enabling SVG or PDF if untrusted users can upload."
      },
      "maxUploadSizeKb": {
        "label": "Max upload size (KB)"
      },
      "backupImportMaxSizeKb": {
        "label": "Max backup import size (KB)",
        "help": "Limit for ZIP backup import via admin."
      },
      "allowedMimeTypes": {
        "label": "Allowed MIME types (upload)",
        "help": "Comma-separated. Complements extension and magic-byte checks."
      }
    },
    "accessControl": {
      "pathAclEnabled": {
        "label": "Enable path ACL",
        "help": "Restrict access to selected flat-file content paths by role or permission.",
        "tooltip": "When enabled, rules below are evaluated before content read/write APIs. Paths use glob semantics under content/pages and content/articles. SUPER_ADMIN bypasses path ACL."
      }
    },
    "codePolicy": {
      "enabled": {
        "label": "Enable code policy checks (core)",
        "help": "Applies to core Code Editor writes. Untrusted paths (plugins, themes, shortcodes) are always checked even when this is off."
      },
      "strictMode": {
        "label": "Strict extension namespace rules",
        "help": "Enabled = stricter extension namespace rules. Disabled = relaxed rules."
      },
      "maxFileSizeKb": {
        "label": "Max file size (KB)"
      },
      "untrustedMaxFileSizeKb": {
        "label": "Max untrusted file size (KB)",
        "help": "Size cap for plugins, themes, and layout shortcode files. Cannot exceed max file size above."
      },
      "forbiddenPhpFunctions": {
        "label": "Forbidden PHP functions",
        "help": "Comma-separated list scanned before save."
      }
    },
    "workflows": {
      "registrationOtpEnabled": {
        "label": "OTP on registration",
        "help": "New accounts are created only after email code verification.",
        "tooltip": "Registration stores a pending user until the OTP from email is verified. Requires working SMTP/notification channel; otherwise registrations stall in pending state."
      },
      "commentApprovalOtpEnabled": {
        "label": "OTP for comment approval",
        "help": "Editors must confirm comment approval with a code from email."
      },
      "publishApprovalOtpEnabled": {
        "label": "OTP for publishing",
        "help": "Editors must confirm publishing with a code from email.",
        "tooltip": "Applies when an editor changes content status to published. Adds a second factor via email before the publish write is accepted — useful for regulated workflows."
      },
      "otpTtlMinutes": {
        "label": "OTP code validity (min)"
      },
      "otpMaxAttempts": {
        "label": "Max OTP attempts"
      }
    },
    "gallery": {
      "enabled": {
        "label": "Enable gallery on public site",
        "help": "Master switch for the admin screenshots section."
      },
      "placement": {
        "label": "Placement",
        "help": "Where the gallery appears: home page, dedicated route, both, or off."
      },
      "publicRoute": {
        "label": "Public route",
        "help": "Single-segment path without domain, e.g. /features."
      },
      "layout": {
        "label": "Layout",
        "help": "Grid = tiles; slider = carousel with autoplay; hero-strip = wide screenshot strip."
      },
      "effectPreset": {
        "label": "Effect preset",
        "help": "subtle = fade+scale; cinematic = crossfade+vignette; minimal = instant swap (no animation)."
      },
      "autoplayEnabled": {
        "label": "Slider autoplay",
        "help": "Applies to slider and hero-strip layouts. Pauses on hover/focus; off when prefers-reduced-motion."
      },
      "autoplayIntervalMs": {
        "label": "Autoplay interval (ms)",
        "help": "Time between slides (4000–15000 ms)."
      },
      "showFeatureTags": {
        "label": "Show module tags",
        "help": "Badge with module name (Analytics, Newsletter, …) on gallery items."
      },
      "modalCaptionStyle": {
        "label": "Modal caption style",
        "help": "below = under image; overlay = over bottom; side = beside image on wide screens."
      }
    },
    "systemUpdate": {
      "deployEnabled": {
        "label": "Enable admin deploy",
        "help": "SUPER_ADMIN can enqueue code deploy from Platform → System update. Ignored when DEMO_MODE=true."
      },
      "githubOwner": {
        "label": "GitHub owner"
      },
      "githubRepo": {
        "label": "GitHub repository"
      },
      "githubToken": {
        "label": "GitHub token (repo read)",
        "help": "Fine-grained or classic token with read access to code and releases."
      },
      "defaultBranch": {
        "label": "Default branch"
      },
      "allowDeployMain": {
        "label": "Allow deploy from branch (origin/…)"
      },
      "allowDeployTags": {
        "label": "Allow deploy from semver tags"
      },
      "stackDir": {
        "label": "Docker stack directory (host path)",
        "help": "Absolute path on the host, e.g. /var/lib/docker/compose/paginiumcms — required for admin UI deploy to restart PHP. Falls back to STACK_DIR env when empty."
      },
      "backendPort": {
        "label": "Backend health port",
        "help": "Port for post-deploy health check (default 8089). Falls back to BACKEND_PORT env."
      },
      "webhookDeployEnabled": {
        "label": "Enable GitHub release webhook deploy",
        "help": "When enabled, POST /api/webhooks/github/release queues deploy on release published (HMAC secret required)."
      },
      "githubWebhookSecret": {
        "label": "GitHub webhook secret",
        "help": "Same secret as configured in GitHub → Settings → Webhooks. Never logged."
      }
    }
  },
  "enum": {
    "language": {
      "sk": "Slovak",
      "en": "English"
    },
    "deploymentMode": {
      "classic": "Classic",
      "hybrid": "Hybrid",
      "git_headless": "Git headless"
    },
    "storageDriver": {
      "local": "Local"
    },
    "cacheDriver": {
      "auto": "Auto",
      "memory": "Memory",
      "file": "File",
      "redis": "Redis"
    },
    "gitPublishStrategy": {
      "disabled": "Disabled",
      "immediate": "Immediate",
      "queued": "Queued"
    },
    "gitPublisher": {
      "local": "Local git",
      "github_api": "GitHub API"
    },
    "performanceGuardRemediationMode": {
      "off": "Off",
      "suggest": "Suggest only",
      "automatic": "Automatic"
    },
    "encryption": {
      "none": "None",
      "tls": "TLS",
      "ssl": "SSL"
    },
    "toastPosition": {
      "top-right": "Top right",
      "top-left": "Top left",
      "bottom-right": "Bottom right",
      "bottom-left": "Bottom left"
    },
    "logIncidentConnector": {
      "email": "Email",
      "ntfy": "ntfy",
      "discord": "Discord",
      "telegram": "Telegram",
      "webhook": "Webhook",
      "all": "All channels"
    },
    "reportConnector": {
      "email": "Email",
      "ntfy": "ntfy",
      "discord": "Discord",
      "telegram": "Telegram",
      "webhook": "Webhook",
      "all": "All channels"
    },
    "reportInterval": {
      "hourly": "Hourly",
      "daily": "Daily",
      "weekly": "Weekly"
    },
    "minSeverity": {
      "debug": "Debug",
      "info": "Info",
      "notice": "Notice",
      "warning": "Warning",
      "error": "Error",
      "critical": "Critical"
    },
    "defaultStatus": {
      "draft": "Draft",
      "published": "Published"
    },
    "storageFormat": {
      "md": "Markdown + YAML",
      "json": "JSON"
    },
    "defaultEditor": {
      "markdown": "Markdown",
      "wysiwyg": "WYSIWYG"
    },
    "jailMode": {
      "forbidden": "403 Forbidden",
      "empty": "Empty response",
      "tarpit": "Tarpit"
    },
    "placement": {
      "home": "Home page",
      "route": "Dedicated route",
      "both": "Both",
      "off": "Off",
      "top": "Top bar",
      "side": "Side menu"
    },
    "layout": {
      "grid": "Grid",
      "slider": "Slider",
      "hero-strip": "Hero strip"
    },
    "effectPreset": {
      "subtle": "Subtle",
      "cinematic": "Cinematic",
      "minimal": "Minimal"
    },
    "modalCaptionStyle": {
      "below": "Below image",
      "overlay": "Overlay",
      "side": "Side"
    }
  },
  "maintenance": {
    "mode": {
      "off": {
        "title": "Off",
        "description": "Public site runs normally."
      },
      "comingSoon": {
        "title": "Coming Soon",
        "description": "Pre-launch teaser page with newsletter signup."
      },
      "underMaintenance": {
        "title": "Under Maintenance",
        "description": "Temporary downtime page with visitor messaging."
      }
    }
  }
};
