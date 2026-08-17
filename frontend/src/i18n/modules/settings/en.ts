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
    "performanceGuardIntro": "In-request latency and I/O sampling. Disabled by default — tune budgets for your hardware. Does not replace host metrics (It.46).",
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
        "description": "Compose with shortcodes in Markdown / WYSIWYG (It.58d)."
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
      "logs:view": "View logs",
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
        "help": "Only one mode can be active at a time."
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
        "help": "md = YAML front matter + Markdown; json = pure JSON file (Iteration 19)."
      },
      "defaultStatus": {
        "label": "Default content status",
        "help": "New pages/articles start as draft or published immediately."
      },
      "autoSaveInterval": {
        "label": "Auto-save interval (s)",
        "help": "How often drafts are saved (Iteration 2)."
      },
      "lockTtl": {
        "label": "Content lock TTL (s)",
        "help": "Auto-release lock after inactivity (Iteration 1)."
      },
      "autoTagEnabled": {
        "label": "Tag suggestions in editor",
        "help": "Enables “Suggest tags” in the article editor (It.57)."
      },
      "autoTagMax": {
        "label": "Max suggested tags",
        "help": "How many tags the generator returns at once."
      },
      "autoDescriptionEnabled": {
        "label": "Meta description generator",
        "help": "Enables “Generate description” in the editor (It.57)."
      },
      "autoDescriptionMaxLength": {
        "label": "Max meta description length (chars)",
        "help": "Recommended 150–160 characters for SEO."
      }
    },
    "editor": {
      "defaultEditor": {
        "label": "Default editor",
        "help": "Which editor opens for new content (Markdown or WYSIWYG)."
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
        "label": "Spellcheck",
        "help": "Enabled = browser underlines spelling errors in the editor. Disabled = no spellcheck."
      },
      "tabSize": {
        "label": "Tab size"
      },
      "customComponentsEnabled": {
        "label": "Enable custom editor components",
        "help": "Plugins can register custom blocks for Markdown and WYSIWYG (It.60)."
      },
      "profileCustomComponents": {
        "label": "Custom components by profile (JSON)",
        "help": "Managed in the matrix below."
      }
    },
    "navigationUi": {
      "defaultPreviewScale": {
        "label": "Default hover preview scale (×10)",
        "help": "Value 15 = 1.5× scale on menu icon hover (It.56)."
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
        "help": "Top bar, side cascade tree, or both on desktop (It.84e)."
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
        "help": "Enabled = WAF checks body of mutating requests (editor API is excluded). Disabled = URI and headers only."
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
        "help": "Enabled = external login is available (per enabled providers). Disabled = local accounts only."
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
    "scheduler": {
      "enabled": {
        "label": "Enable job scheduler",
        "help": "Master switch for scheduler:run CLI. Individual jobs can still be toggled in Scheduler."
      },
      "retainRuns": {
        "label": "Retain run history entries"
      }
    },
    "codePolicy": {
      "enabled": {
        "label": "Enable code policy checks",
        "help": "Enabled = forbidden functions and file size checked before saving PHP files. Disabled = checks off."
      },
      "strictMode": {
        "label": "Strict extension namespace rules",
        "help": "Enabled = stricter extension namespace rules. Disabled = relaxed rules."
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
        "label": "OTP on registration",
        "help": "New accounts are created only after email code verification (Iteration 41)."
      },
      "commentApprovalOtpEnabled": {
        "label": "OTP for comment approval",
        "help": "Editors must confirm comment approval with a code from email."
      },
      "publishApprovalOtpEnabled": {
        "label": "OTP for publishing",
        "help": "Editors must confirm publishing with a code from email."
      },
      "otpTtlMinutes": {
        "label": "OTP code validity (min)"
      },
      "otpMaxAttempts": {
        "label": "Max OTP attempts"
      }
    }
  },
  "enum": {
    "language": {
      "sk": "Slovak",
      "en": "English"
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
