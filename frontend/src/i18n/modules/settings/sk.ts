import type { MessageTree } from '../../types';

export const settingsSk: MessageTree = {
  "page": {
    "title": "Nastavenia",
    "save": "Uložiť zmeny",
    "saving": "Ukladám...",
    "loadFailed": "Nepodarilo sa načítať nastavenia",
    "saved": "Nastavenia uložené",
    "validationFailed": "Validácia zlyhala",
    "saveFailed": "Uloženie zlyhalo"
  },
  "twoFactor": {
    "title": "Dvojfaktorové overenie (2FA)",
    "description": "Nastavenie QR kódu a TOTP autentifikátora je v samostatnej sekcii bezpečnosti účtu.",
    "link": "Prejsť na bezpečnosť účtu"
  },
  "cache": {
    "title": "Cache systému",
    "description": "Manuálne vymazanie cache po deployi alebo keď verejný web zobrazuje starý obsah. Odporúčané: najprv „Cache obsahu“.",
    "refresh": "Obnoviť stav",
    "loading": "Načítavam stav cache…",
    "loadFailed": "Nepodarilo sa načítať stav cache.",
    "fileEntries": "Súbory na disku",
    "pagesGeneration": "Generácia stránok",
    "articlesGeneration": "Generácia článkov",
    "feedsGeneration": "Generácia feedov",
    "purgeContent": "Vymazať cache obsahu",
    "purgeAll": "Vymazať celú cache",
    "purging": "Mažem…",
    "purged": "Cache vymazaná",
    "purgeFailed": "Vymazanie cache zlyhalo",
    "confirmContent": "Naozaj chcete vymazať cache obsahu (stránky, články, feedy)?",
    "confirmAll": "Naozaj chcete vymazať celú cache?"
  },
  "categories": {
    "system": {
      "label": "Systém",
      "description": "Jadrové nastavenia CMS, logy, notifikácie a plánovač."
    },
    "site": {
      "label": "Stránka",
      "description": "Obsah, editor, SEO, feedy a kontaktné formuláre."
    },
    "media": {
      "label": "Médiá",
      "description": "Knižnica médií a stock obrázky."
    },
    "security": {
      "label": "Bezpečnosť",
      "description": "Prihlásenie, firewall, obsah, upload a SSO."
    }
  },
  "groups": {
    "general": "Všeobecné",
    "content": "Obsah",
    "editor": "Editor",
    "smtp": "Email / SMTP",
    "notifications": "Toast notifications",
    "connectors": "Notification connectors",
    "monitoring": "Monitoring & incidents",
    "scheduler": "Job scheduler",
    "codePolicy": "Code policy",
    "comments": "Komentáre",
    "contact": "Kontaktný formulár",
    "company": "Firemné údaje",
    "workflows": "Workflow OTP",
    "ui": "Admin UI",
    "security": "Bezpečnosť",
    "firewall": "Firewall (WAF)",
    "logging": "Logy",
    "feeds": "RSS & Sitemap",
    "seo": "SEO",
    "media": "Media / DAM",
    "sso": "SSO / OAuth",
    "contentSecurity": "Bezpečnosť obsahu (XML/HTML)",
    "uploadSecurity": "Bezpečnosť uploadu"
  },
  "hints": {
    "security": {
      "title": "Bezpečnostné skupiny",
      "body": "Nastavenia Obsah a Upload sa uplatňujú pri renderovaní HTML a pri každom upload do knižnice médií. Odporúčame nechať sanitizáciu a magic bytes zapnuté."
    }
  },
  "fields": {
    "general": {
      "siteName": {
        "label": "Názov stránky"
      },
      "siteDescription": {
        "label": "Popis stránky"
      },
      "siteUrl": {
        "label": "URL stránky",
        "help": "Napr. https://example.com"
      },
      "adminEmail": {
        "label": "Administrátorský e-mail"
      },
      "language": {
        "label": "Jazyk administrácie"
      },
      "timezone": {
        "label": "Časové pásmo"
      },
      "maintenanceMode": {
        "label": "Režim údržby",
        "help": "Zablokuje verejný web okrem administrácie."
      },
      "allowRegistration": {
        "label": "Povoliť registráciu",
        "help": "Vypnutím zablokujete POST /api/auth/register."
      }
    },
    "content": {
      "itemsPerPage": {
        "label": "Položiek na stránku (admin)",
        "help": "Admin zoznamy stránok a článkov."
      },
      "blogItemsPerPage": {
        "label": "Článkov na stránku (blog)",
        "help": "Verejný zoznam článkov – stránkovanie sa zobrazí, keď je viac článkov."
      },
      "showReadingTime": {
        "label": "Zobraziť odhadovaný čas čítania",
        "help": "Na blog kartách a detaile článku (počítané z dĺžky textu)."
      },
      "storageFormat": {
        "label": "Formát úložiska obsahu",
        "help": "md = YAML front matter + Markdown; json = čistý JSON súbor (Iterácia 19)."
      },
      "defaultStatus": {
        "label": "Predvolený stav obsahu"
      },
      "autoSaveInterval": {
        "label": "Interval auto-save (s)",
        "help": "Ako často sa ukladá koncept (Iterácia 2)."
      },
      "lockTtl": {
        "label": "Platnosť zámku obsahu (s)",
        "help": "Auto-release zámku po nečinnosti (Iterácia 1)."
      }
    },
    "editor": {
      "defaultEditor": {
        "label": "Predvolený editor"
      },
      "defaultProfilePage": {
        "label": "Predvolený profil (stránky)",
        "help": "Modulárny toolbar pre stránky (Iterácia 54)."
      },
      "defaultProfileArticle": {
        "label": "Predvolený profil (články)",
        "help": "Modulárny toolbar pre články (Iterácia 54)."
      },
      "spellcheck": {
        "label": "Kontrola pravopisu"
      },
      "tabSize": {
        "label": "Veľkosť tabulátora"
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
    "comments": {
      "enabled": {
        "label": "Povoliť komentáre globálne",
        "help": "Vypnutím sa skryje formulár na celom webe (okrem článkov s vlastným prepínačom)."
      },
      "requireApproval": {
        "label": "Globálne vyžadovať schválenie",
        "help": "Nové komentáre čakajú na schválenie v administrácii. Dá sa prepísať pri jednotlivom článku."
      },
      "allowGuestComments": {
        "label": "Povoliť komentáre od hostí",
        "help": "Neprihlásení návštevníci môžu pridávať komentáre. Dá sa prepísať pri jednotlivom článku."
      },
      "maxLength": {
        "label": "Max. dĺžka komentára"
      }
    },
    "contact": {
      "subjects": {
        "label": "Predvolené predmety správ",
        "help": "Jeden predmet na riadok — zobrazí sa vo verejnom kontaktnom formulári."
      },
      "allowCustomSubject": {
        "label": "Povoliť vlastný predmet",
        "help": "Návštevník môže zvoliť „Vlastný predmet“ a napísať vlastný text."
      }
    },
    "company": {
      "showOnContactPage": {
        "label": "Zobraziť blok na kontaktnej stránke"
      },
      "name": {
        "label": "Názov firmy"
      },
      "legalName": {
        "label": "Právna forma / obchodné meno"
      },
      "ico": {
        "label": "IČO"
      },
      "dic": {
        "label": "DIČ"
      },
      "icDph": {
        "label": "IČ DPH"
      },
      "address": {
        "label": "Adresa"
      },
      "email": {
        "label": "Kontaktný e-mail"
      },
      "phone": {
        "label": "Telefón"
      },
      "website": {
        "label": "Web"
      },
      "mapEmbedUrl": {
        "label": "Google Maps embed URL",
        "help": "Google Maps → Zdieľať → Vložiť mapu (hodnota src z iframe)."
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
    },
    "ui": {
      "showListCounts": {
        "label": "Zobraziť počty v sidebari",
        "help": "Badge s počtom položiek pri moduloch v administrácii (Iterácia 42)."
      },
      "adminListPageSize": {
        "label": "Položiek na stránku (admin)",
        "help": "Predvolený počet riadkov v admin zoznamoch (Media, Kôš, komentáre…)."
      },
      "openLinksInNewTab": {
        "label": "Otvárať náhľady a externé odkazy v novej karte",
        "help": "Platí pre náhľad obsahu, prechod na verejný web z adminu, media download a externé odkazy vo footeri. Vypnuté = rovnaká karta / SPA navigácia."
      }
    },
    "security": {
      "maxLoginAttempts": {
        "label": "Max. neúspešných prihlásení",
        "help": "Po prekročení sa účet/IP dočasne zablokuje."
      },
      "lockoutMinutes": {
        "label": "Dĺžka blokácie (min)"
      },
      "requireTwoFactorStaff": {
        "label": "Vynútiť 2FA pre editorov a adminov",
        "help": "Pri zapnutí nie je možné vypnúť 2FA pre roly EDITOR, ADMIN a SUPER_ADMIN."
      },
      "passwordMinLength": {
        "label": "Min. dĺžka hesla",
        "help": "Platí pre registráciu, zmenu hesla a admin vytvorenie používateľa."
      },
      "passwordMaxLength": {
        "label": "Max. dĺžka hesla"
      },
      "passwordRequireUppercase": {
        "label": "Vyžadovať veľké písmeno (A–Z)"
      },
      "passwordRequireLowercase": {
        "label": "Vyžadovať malé písmeno (a–z)"
      },
      "passwordRequireNumbers": {
        "label": "Vyžadovať číslicu (0–9)"
      },
      "passwordRequireSpecialChars": {
        "label": "Vyžadovať špeciálny znak",
        "help": "Napr. ! @ # $ % & *"
      }
    },
    "login": {
      "pageTitle": {
        "label": "Nadpis prihlasovacej stránky",
        "help": "Prázdne = použije sa názov stránky z Všeobecných."
      },
      "pageDescription": {
        "label": "Popis prihlasovacej stránky",
        "help": "Krátky text v informačnom paneli prihlásenia/registrácie."
      },
      "backgroundImageUrl": {
        "label": "URL obrázka pozadia",
        "help": "Absolútna URL alebo cesta /storage/… — zobrazí sa za prihlasovacím formulárom. Môžete vybrať z médií alebo nahrať súbor z disku."
      },
      "backgroundPicker": {
        "pickFromMedia": "Vybrať z médií",
        "uploadLocal": "Nahrať z disku",
        "uploading": "Nahrávam…",
        "remove": "Odstrániť pozadie",
        "previewAlt": "Náhľad pozadia prihlásenia",
        "mediaModalTitle": "Vybrať pozadie prihlásenia",
        "uploadAlt": "Pozadie prihlasovacej stránky",
        "uploadFailed": "Nahratie pozadia zlyhalo.",
        "invalidType": "Povolené sú len obrázky podľa nastavenia uploadSecurity."
      },
      "infoBullets": {
        "label": "Informačné body",
        "help": "Jeden riadok = jeden bod v informačnom paneli."
      }
    },
    "firewall": {
      "enabled": {
        "label": "Zapnúť firewall",
        "help": "Interný WAF skenuje URI, query a User-Agent pred spracovaním požiadavky."
      },
      "jailMinutes": {
        "label": "Dĺžka jail (min)",
        "help": "Dočasná blokácia IP po prekročení prahu incidentov."
      },
      "maxRetries": {
        "label": "Incidentov pred jail",
        "help": "Počet porušení v okne pred dočasným banom."
      },
      "permanentThreshold": {
        "label": "Prah trvalého banu",
        "help": "Počet jail cyklov pred trvalou blokáciou IP."
      },
      "jailMode": {
        "label": "Jail odpoveď",
        "help": "Režim HTTP odpovede pre zablokované IP. Tarpit spomaľuje botov (max 2 s)."
      },
      "tarpitSeconds": {
        "label": "Tarpit oneskorenie (s)",
        "help": "Platí len pri jailMode=tarpit. Neodporúčame >2 s (FPM worker)."
      },
      "logRetention": {
        "label": "Max. incidentov v logu"
      }
    },
    "logging": {
      "enabled": {
        "label": "Zapnúť logovanie",
        "help": "Master prepínač structured logov (app, audit, event, user)."
      },
      "requestLogging": {
        "label": "Logovať HTTP requesty",
        "help": "Každý API endpoint → záznam s timestamp, IP, status, duration."
      },
      "minSeverity": {
        "label": "Min. úroveň zápisu",
        "help": "Nižšie úrovne sa neukladajú (HTTP access log)."
      },
      "retentionDays": {
        "label": "Retencia logov (dni)",
        "help": "Staršie denné súbory sa vymažú (purge v admin Logy)."
      },
      "slowRequestMs": {
        "label": "Pomalý request (ms)",
        "help": "Requesty nad tento limit sa logujú ako WARNING."
      },
      "logAuthEndpoints": {
        "label": "Logovať auth endpointy",
        "help": "Login/register cesty — bez tela, len metadata (IP, status)."
      }
    },
    "feeds": {
      "enabled": {
        "label": "Povoliť feedy"
      },
      "title": {
        "label": "Názov RSS kanála",
        "help": "Prázdne = názov stránky z všeobecných nastavení."
      },
      "description": {
        "label": "Popis RSS kanála"
      },
      "itemsLimit": {
        "label": "Počet položiek v RSS"
      },
      "includePages": {
        "label": "Sitemap: podstránky"
      },
      "includeArticles": {
        "label": "RSS/Sitemap: články"
      }
    },
    "seo": {
      "titleTemplate": {
        "label": "Šablóna titulku",
        "help": "Placeholders: %title%, %siteName%"
      },
      "defaultDescription": {
        "label": "Predvolený meta popis"
      },
      "defaultImage": {
        "label": "Predvolený OG obrázok (URL)"
      },
      "robotsDefault": {
        "label": "Robots (predvolene)"
      },
      "allowSearchIndexing": {
        "label": "Povoliť indexovanie vyhľadávačmi",
        "help": "Vypnuté = robots.txt obsahuje Disallow: / a meta tagy noindex (okrem stránok s vlastným noIndex)."
      },
      "twitterCard": {
        "label": "Twitter card typ"
      }
    },
    "media": {
      "allowedMimeTypes": {
        "label": "Povolené MIME typy",
        "help": "Oddeľte čiarkou. Ovplyvňuje upload v Media Library."
      },
      "maxUploadSizeKb": {
        "label": "Max. veľkosť uploadu (KB)",
        "help": "5120 KB = 5 MB."
      },
      "stockImagesEnabled": {
        "label": "Povoliť stock knižnicu",
        "help": "Tlačidlo „Generovať z knižnice“ v Media Library."
      },
      "stockImageTopic": {
        "label": "Téma stock obrázkov",
        "help": "Obrázky sa vyberajú podľa zamerania webu (IT, varenie, cestovanie…)."
      }
    },
    "sso": {
      "enabled": {
        "label": "Povoliť SSO prihlásenie"
      },
      "defaultRole": {
        "label": "Predvolená rola (nový účet)"
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
    }
  },
  "enum": {
    "language": {
      "sk": "Slovenčina",
      "en": "English"
    }
  }
};
