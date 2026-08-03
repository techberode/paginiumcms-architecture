import type { MessageTree } from '../../types';

export const settingsSk: MessageTree = {
  "page": {
    "title": "Nastavenia",
    "save": "Uložiť zmeny",
    "saving": "Ukladám...",
    "loadFailed": "Nepodarilo sa načítať nastavenia",
    "saved": "Nastavenia uložené",
    "validationFailed": "Validácia zlyhala",
    "saveFailed": "Uloženie zlyhalo",
    "groupCount": ":count skupín"
  },
  "twoFactor": {
    "title": "Dvojfaktorové overenie (2FA)",
    "description": "Nastavenie QR kódu a TOTP autentifikátora je v samostatnej sekcii bezpečnosti účtu.",
    "link": "Prejsť na bezpečnosť účtu"
  },
  "timezoneSelect": {
    "placeholder": "Vyberte časové pásmo…",
    "searchPlaceholder": "Hľadať mesto alebo pásmo…",
    "noResults": "Nenašlo sa žiadne pásmo.",
    "common": "Často používané",
    "all": "Všetky pásma",
    "dstActive": "Letný čas je v tomto pásme práve aktívny.",
    "dstInactive": "Letný čas je v tomto pásme práve neaktívny."
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
    "maintenance": "Režim údržby",
    "content": "Obsah",
    "editor": "Editor",
    "navigationUi": "Navigácia (UI)",
    "smtp": "Email / SMTP",
    "notifications": "Toast notifications",
    "connectors": "Notification connectors",
    "monitoring": "Monitoring & incidents",
    "scheduler": "Job scheduler",
    "systemUpdate": "Aktualizácia systému (deploy)",
    "codePolicy": "Code policy",
    "engine": "Hybrid Engine",
    "comments": "Komentáre",
    "contact": "Kontaktný formulár",
    "newsletter": "Newsletter",
    "marketing": "Marketing a sociálne siete",
    "gallery": "Galéria funkcií",
    "company": "Firemné údaje",
    "privacy": "Súkromie a cookies",
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
    "uploadSecurity": "Bezpečnosť uploadu",
    "branding": "Logo a favicon",
    "appearance": "Vzhľad",
    "layout": "Rozloženie stránky",
    "accessControl": "Oprávnenia rolí",
    "cmsInfo": "PaginiumCMS – info"
  },
  "cmsInfo": {
    "loadFailed": "Nepodarilo sa načítať informácie o CMS.",
    "tagline": "Headless flat-file CMS — open source, bez poplatkov.",
    "version": "Verzia",
    "phpVersion": "PHP runtime",
    "licenseTitle": "Licencia",
    "licenseBody": "PaginiumCMS je distribuovaný pod open-source licenciou MIT. Môžeš kód forknúť, upravovať a nasadiť — nesmie sa ponúkať ako platený produkt (viď filozofiu projektu).",
    "licenseLink": "text licencie na GitHub",
    "localesTitle": "Jazykové verzie",
    "localesBody": "Vstavané jazyky administrácie a verejného webu. Ďalšie locale pridávaš v module prekladov.",
    "localeBuiltin": "vstavaný",
    "stackTitle": "Technológie",
    "linksTitle": "Odkazy",
    "linkRepository": "GitHub repozitár",
    "linkDocs": "Dokumentácia",
    "linkPhilosophy": "Filozofia projektu",
    "linkChangelog": "Changelog (história verzií)",
    "footer": "Verzia sa aktualizuje pri release tagu. Táto sekcia je len na čítanie."
  },
  "engine": {
    "probeTitle": "Capability probe enginu",
    "probeIntro": "Aktuálny deployment profil a nainštalované schopnosti Hybrid Engine. Budúce režimy sú zobrazené ako nedostupné — nie ako funkčné prepínače.",
    "deploymentMode": "Režim nasadenia",
    "storageDriver": "Storage driver",
    "docsLink": "Dokumentácia Hybrid Engine architektúry"
  },
  "appearance": {
    "defaultBadge": "Predvolená",
    "schemesTitle": "Farebné schémy",
    "schemesHint": "Vyberte paletu pre verejný web. Každá schéma má svetlú a tmavú variantu.",
    "modeTitle": "Režim zobrazenia",
    "modes": {
      "light": "Svetlý",
      "dark": "Tmavý",
      "system": "Podľa systému"
    },
    "allowUserToggle": "Povoliť návštevníkom prepínať svetlý / tmavý režim",
    "previewTemplateTitle": "Náhľadová layout šablóna",
    "previewTitle": "Náhľad stránky",
    "schemes": {
      "indigoClassic": {
        "name": "Indigo Classic",
        "description": "Indigo / violet na slate — default Paginium, SaaS a tech."
      },
      "oceanSlate": {
        "name": "Ocean Slate",
        "description": "Teal / cyan na cool gray — calm tech, fintech."
      },
      "forestSage": {
        "name": "Forest Sage",
        "description": "Emerald / sage na warm neutral — eko, wellness, B2B."
      },
      "sunsetRose": {
        "name": "Sunset Rose",
        "description": "Coral / rose na cream — magazín, lifestyle, kreatíva."
      },
      "monoZinc": {
        "name": "Mono Zinc",
        "description": "Zinc neutrals + accent — editorial, minimal, portfólio."
      }
    }
  },
  "layout": {
    "buildersTitle": "Layout builder",
    "buildersHint": "Vyberte, ako editormi skladajú štruktúru stránky. Režimy neskôr zdieľajú rovnaké dáta — prepínač mení len UI.",
    "templatesTitle": "Predvolená layout šablóna",
    "templatesHint": "Pre nové stránky a živý náhľad nižšie (štruktúra, nie chrome šablóny Home/Kontakt).",
    "previewTitle": "Náhľad layoutu",
    "developerRequiresAdmin": "Developer (Monaco) režim len pre ADMIN a SUPER_ADMIN",
    "developerLocked": "Dostupné len pre ADMIN / SUPER_ADMIN, kým je toto obmedzenie zapnuté.",
    "builders": {
      "templates": {
        "name": "Šablóny",
        "description": "Výber pomenovanej štruktúry a vyplnenie slotov — rýchle stránky."
      },
      "shortcodes": {
        "name": "Shortcodes",
        "description": "Skladanie cez shortcodes v Markdown / WYSIWYG (It.58d)."
      },
      "outline": {
        "name": "Outline blokov",
        "description": "Presúvanie sekcií bez kódu (neskorší slice)."
      },
      "developer": {
        "name": "Developer",
        "description": "Monaco pre layout JSON a definície shortcodes."
      }
    },
    "templates": {
      "single": {
        "name": "Jeden stĺpec",
        "description": "Header → body → footer"
      },
      "heroContent": {
        "name": "Hero + obsah",
        "description": "Header → hero → karty → footer"
      },
      "twoColumn": {
        "name": "Dva stĺpce",
        "description": "Hlavný stĺpec + sidebar"
      },
      "landing": {
        "name": "Landing",
        "description": "Hero → mriežka → CTA"
      },
      "blogArticle": {
        "name": "Blog článok",
        "description": "Nadpis, meta, dlhé telo"
      }
    }
  },
  "marketing": {
    "social": {
      "title": "Sociálne siete vo footeri",
      "description": "Pridajte ikony s odkazmi na propagáciu projektu (GitHub, X, LinkedIn, …). Max. 12 položiek.",
      "platform": "Platforma",
      "url": "URL",
      "email": "E-mailová adresa",
      "label": "Popis pre čítačku",
      "enabled": "Zobraziť vo footeri",
      "add": "Pridať odkaz",
      "remove": "Odstrániť odkaz",
      "moveUp": "Posunúť hore",
      "moveDown": "Posunúť dole",
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
        "website": "Web",
        "email": "E-mail",
        "rss": "RSS kanál"
      }
    }
  },
  "accessControl": {
    "superAdminTitle": "Len pre super administrátora",
    "superAdminHint": "Mapovanie oprávnení rolí ADMIN, EDITOR a USER a path ACL pravidlá môže meniť iba SUPER_ADMIN. SUPER_ADMIN má vždy plný prístup.",
    "restrictedTitle": "Obmedzený prístup",
    "restrictedHint": "Táto skupina nastavení je viditeľná len pre super administrátora.",
    "rolesTitle": "Oprávnenia podľa rolí",
    "pathAclTitle": "Path ACL (cesty obsahu)",
    "pathAclEnabled": "Povoliť path ACL",
    "pathAclHint": "Obmedzí prístup k vybraným cestám flat-file obsahu (napr. content/pages/restricted/*).",
    "permissions": {
      "user:manage": "Správa používateľov",
      "content:manage": "Správa obsahu (všetko)",
      "content:create": "Vytváranie obsahu",
      "content:edit": "Úprava obsahu",
      "content:delete": "Mazanie obsahu",
      "content:view": "Zobrazenie obsahu",
      "media:manage": "Správa médií (všetko)",
      "media:upload": "Upload médií",
      "media:delete": "Mazanie médií",
      "settings:manage": "Správa nastavení",
      "logs:view": "Prehliadanie logov",
      "profile:edit": "Úprava profilu"
    }
  },
  "hints": {
    "security": {
      "title": "Bezpečnostné skupiny",
      "body": "Nastavenia Obsah a Upload sa uplatňujú pri renderovaní HTML a pri každom upload do knižnice médií. Odporúčame nechať sanitizáciu a magic bytes zapnuté."
    }
  },
  "editorComponents": {
    "title": "Custom komponenty podľa profilu",
    "help": "Vyberte, ktoré bloky z pluginov budú dostupné v jednotlivých profiloch editora.",
    "empty": "Žiadne custom komponenty. Povoľte plugin s editor.components v manifeste.",
    "disabledHint": "Zapnite „Povoliť custom komponenty editora“ vyššie pre konfiguráciu profilov.",
    "profile": "Profil",
    "toggle": "Povoliť {component} pre {profile}"
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
        "label": "Časové pásmo",
        "help": "Platí pre logy, audit a naplánované reporty."
      },
      "timezoneDst": {
        "label": "Letný čas (DST)",
        "help": "Zapnuté = automatická korekcia letného času podľa zvoleného pásma. Vypnuté = stály zimný čas bez posunu."
      },
      "allowRegistration": {
        "label": "Povoliť registráciu",
        "help": "Vypnutím zablokujete POST /api/auth/register. Počas režimu údržby je registrácia vždy vypnutá."
      }
    },
    "maintenance": {
      "mode": {
        "label": "Aktívny režim",
        "help": "Naraz môže byť zapnutý iba jeden režim."
      },
      "heroImageUrl": {
        "label": "Pozadie (URL)",
        "help": "Absolútna URL alebo cesta /storage/… — voliteľný obrázok na pozadí oboch stránok údržby. Môžete vybrať z médií alebo nahrať súbor z disku."
      },
      "newsletterEnabled": {
        "label": "Povoliť newsletter",
        "help": "Zapnuté = na Coming Soon stránke sa zobrazí prihlásenie na newsletter. Vypnuté = formulár skrytý."
      },
      "newsletterHint": {
        "label": "Text newsletteru"
      },
      "comingSoonBadge": {
        "label": "Coming Soon – odznak"
      },
      "comingSoonTitle": {
        "label": "Coming Soon – nadpis"
      },
      "comingSoonSubtitle": {
        "label": "Coming Soon – podnadpis"
      },
      "comingSoonBody": {
        "label": "Coming Soon – telo"
      },
      "maintenanceBadge": {
        "label": "Údržba – odznak"
      },
      "maintenanceTitle": {
        "label": "Údržba – nadpis"
      },
      "maintenanceSubtitle": {
        "label": "Údržba – podnadpis"
      },
      "maintenanceBody": {
        "label": "Údržba – telo"
      },
      "maintenanceShowContactForm": {
        "label": "Zobraziť kontaktný formulár",
        "help": "Zapnuté = počas údržby je dostupný kontaktný formulár. Vypnuté = len informačný text."
      },
      "maintenanceContactSubject": {
        "label": "Predmet správy z údržby"
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
        "label": "Predvolený stav obsahu",
        "help": "Nové stránky/články vzniknú ako koncept alebo rovno publikované."
      },
      "autoSaveInterval": {
        "label": "Interval auto-save (s)",
        "help": "Ako často sa ukladá koncept (Iterácia 2)."
      },
      "lockTtl": {
        "label": "Platnosť zámku obsahu (s)",
        "help": "Auto-release zámku po nečinnosti (Iterácia 1)."
      },
      "autoTagEnabled": {
        "label": "Navrhovanie tagov v editore",
        "help": "Povolí tlačidlo „Navrhnúť tagy“ v editore článkov (It.57)."
      },
      "autoTagMax": {
        "label": "Max. počet navrhovaných tagov",
        "help": "Koľko tagov vráti generátor naraz."
      },
      "autoDescriptionEnabled": {
        "label": "Generovanie meta popisu",
        "help": "Povolí tlačidlo „Generovať popis“ v editore (It.57)."
      },
      "autoDescriptionMaxLength": {
        "label": "Max. dĺžka meta popisu (znaky)",
        "help": "Odporúčané 150–160 znakov pre SEO."
      }
    },
    "editor": {
      "defaultEditor": {
        "label": "Predvolený editor",
        "help": "Ktorý editor sa otvorí pri novom obsahu (Markdown alebo WYSIWYG)."
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
        "label": "Kontrola pravopisu",
        "help": "Zapnuté = prehliadač podčiarkne pravopisné chyby v editore. Vypnuté = bez kontroly."
      },
      "tabSize": {
        "label": "Veľkosť tabulátora"
      },
      "customComponentsEnabled": {
        "label": "Povoliť custom komponenty editora",
        "help": "Pluginy môžu registrovať vlastné bloky pre Markdown a WYSIWYG (It.60)."
      },
      "profileCustomComponents": {
        "label": "Custom komponenty podľa profilu (JSON)",
        "help": "Spravuje sa v matici nižšie."
      }
    },
    "navigationUi": {
      "defaultPreviewScale": {
        "label": "Predvolená mierka hover náhľadu (×10)",
        "help": "Hodnota 15 = mierka 1.5× pri hover ikony v menu (It.56)."
      },
      "maxTooltipWidthPx": {
        "label": "Max. šírka tooltipu (px)",
        "help": "Obmedzenie šírky hover náhľadu v dropdown menu."
      },
      "enableHoverAnimations": {
        "label": "Animácie hover náhľadu",
        "help": "Respektuje prefers-reduced-motion — pri vypnutí statický náhľad."
      }
    },
    "smtp": {
      "enabled": {
        "label": "Enable SMTP",
        "help": "Zapnuté = systém odosiela e-maily (OTP, notifikácie, kontakt). Vypnuté = odosielanie vypnuté."
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
        "help": "Zapnuté = krátke hlášky pri ukladaní a chybách v admin rozhraní. Vypnuté = bez toastov."
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
        "help": "Zapnuté = incidenty a reporty môžu ísť e-mailom (vyžaduje zapnuté SMTP). Vypnuté = kanál e-mail sa nepoužije."
      },
      "ntfyEnabled": {
        "label": "Enable ntfy",
        "help": "Zapnuté = push notifikácie cez ntfy. Vypnuté = kanál ntfy vypnutý."
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
        "help": "Zapnuté = incidenty sa posielajú na Discord webhook. Vypnuté = webhook sa nevolá."
      },
      "discordWebhookUrl": {
        "label": "Discord webhook URL"
      },
      "telegramEnabled": {
        "label": "Enable Telegram bot",
        "help": "Zapnuté = incidenty do Telegram chatu. Vypnuté = bot sa nepoužije."
      },
      "telegramBotToken": {
        "label": "Telegram bot token"
      },
      "telegramChatId": {
        "label": "Telegram chat ID"
      },
      "webhookEnabled": {
        "label": "Enable generic webhook",
        "help": "Zapnuté = incidenty na vlastný HTTP webhook. Vypnuté = webhook vypnutý."
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
        "help": "Zapnuté = systém posiela alerty pri incidentoch cez zapnuté kanály. Vypnuté = žiadne automatické alerty."
      },
      "alertEmail": {
        "label": "Fallback alert email",
        "help": "Used when connectors are off; defaults to admin email."
      },
      "notifyFailedLogin": {
        "label": "Alert on failed login",
        "help": "Zapnuté = upozornenie pri neúspešnom prihlásení. Vypnuté = udalosť sa nehlási."
      },
      "notifySecurityIncident": {
        "label": "Alert on security audit events",
        "help": "Zapnuté = upozornenie pri bezpečnostných audit udalostiach. Vypnuté = neposiela sa."
      },
      "notifyTrafficSpike": {
        "label": "Alert on traffic spike",
        "help": "Zapnuté = alert pri prekročení prahu návštevnosti. Vypnuté = traffic spike sa ignoruje."
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
        "help": "Zapnuté = report obsahuje štatistiky návštevnosti. Vypnuté = bez analytiky."
      },
      "reportIncludeHealth": {
        "label": "Report: system health",
        "help": "Zapnuté = report obsahuje stav zdravia systému. Vypnuté = bez health sekcie."
      },
      "reportIncludeFlatFile": {
        "label": "Report: flat-file counts",
        "help": "Zapnuté = report obsahuje počty stránok/článkov. Vypnuté = bez štatistík obsahu."
      },
      "notifyLogErrors": {
        "label": "Alert on log ERROR/CRITICAL",
        "help": "Zapnuté = ERROR/CRITICAL v logu spustí alert. Vypnuté = chyby v logu sa nehlásia."
      },
      "notifyLogWarnings": {
        "label": "Alert on log WARNING",
        "help": "Zapnuté = WARNING v logu spustí alert. Vypnuté = varovania sa nehlásia."
      },
      "logIncidentConnector": {
        "label": "Log incident connector"
      }
    },
    "scheduler": {
      "enabled": {
        "label": "Enable job scheduler",
        "help": "Hlavný vypínač pre scheduler:run CLI. Jednotlivé joby sa dajú stále prepínať v Plánovači."
      },
      "retainRuns": {
        "label": "Retain run history entries"
      }
    },
    "codePolicy": {
      "enabled": {
        "label": "Enable code policy checks",
        "help": "Zapnuté = pred uložením PHP súborov sa kontrolujú zakázané funkcie a veľkosť. Vypnuté = kontrola vypnutá."
      },
      "strictMode": {
        "label": "Strict extension namespace rules",
        "help": "Zapnuté = prísnejšie pravidlá pre namespaces rozšírení. Vypnuté = uvoľnené pravidlá."
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
    "marketing": {
      "demoFooterLinkEnabled": {
        "label": "Zobraziť odkaz na demo vo footeri",
        "help": "Len produkcia — odkaz na verejnú demo inštanciu."
      },
      "demoUrl": {
        "label": "URL demo inštancie",
        "help": "Cieľ odkazu „Vyskúšajte CMS“ vo footeri."
      },
      "socialLinksEnabled": {
        "label": "Zobraziť sociálne siete vo footeri",
        "help": "Riadok ikon v pätičke verejného webu (GitHub, X, …)."
      },
      "socialLinksJson": {
        "label": "Sociálne siete (JSON)",
        "help": "Spravované vizuálnym editorom nižšie."
      }
    },
    "newsletter": {
      "footerEnabled": {
        "label": "Povoliť newsletter vo footeri",
        "help": "Zapnuté = kompaktný odberový formulár v pätičke verejného webu."
      },
      "footerHint": {
        "label": "Text vo footeri",
        "help": "Krátky popis pod nadpisom newsletteru vo footeri."
      },
      "fromEmail": {
        "label": "Odosielateľ (e-mail)",
        "help": "Pripravené pre budúce odosielanie; fallback na SMTP nastavenia."
      },
      "fromName": {
        "label": "Odosielateľ (meno)",
        "help": "Zobrazované meno odosielateľa v e-mailoch."
      },
      "replyTo": {
        "label": "Reply-To",
        "help": "Voliteľná adresa pre odpovede na newsletter."
      },
      "enabledPreferences": {
        "label": "Typy odberu vo formulári",
        "help": "Jeden kľúč na riadok: weekly_digest, new_article, cms_release, general_news."
      },
      "requireConsentCheckbox": {
        "label": "Vyžadovať súhlas (checkbox)",
        "help": "Návštevník musí explicitne potvrdiť súhlas pred odberom."
      },
      "sendEnabled": {
        "label": "Povoliť odosielanie e-mailov",
        "help": "Master prepínač pre weekly digest a notifikácie o nových článkoch. Vyžaduje nakonfigurovaný SMTP."
      },
      "weeklyDigestEnabled": {
        "label": "Týždenný digest",
        "help": "Odosiela zhrnutie publikovaných článkov odberateľom s preferenciou weekly_digest."
      },
      "newArticleEnabled": {
        "label": "Notifikácia pri novom článku",
        "help": "Pri publikovaní článku odošle e-mail odberateľom s preferenciou new_article."
      },
      "cmsReleaseEnabled": {
        "label": "Kampane o vydaniach CMS",
        "help": "Povolí manuálne odoslanie oznámenia o verzii odberateľom s preferenciou cms_release."
      },
      "instantArticleCooldownHours": {
        "label": "Cooldown medzi instant mailmi (hodiny)",
        "help": "Max. jeden instant mail na odberateľa za dané obdobie."
      },
      "sendBatchLimitPerRun": {
        "label": "Limit odoslaní na beh",
        "help": "Počet e-mailov odoslaných v jednom behu (cron alebo manuálne)."
      },
      "requireDoubleOptIn": {
        "label": "Double opt-in (potvrdenie e-mailom)",
        "help": "Nový odberateľ je pending, kým neklikne na potvrdzovací link v e-maili."
      },
      "confirmTokenTtlHours": {
        "label": "Platnosť potvrdzovacieho linku (hodiny)",
        "help": "Po uplynutí musí odberateľ požiadať o nový potvrdzovací e-mail."
      }
    },
    "company": {
      "showOnContactPage": {
        "label": "Zobraziť blok na kontaktnej stránke",
        "help": "Zapnuté = firemné údaje sa zobrazia na kontaktnej stránke. Vypnuté = blok skrytý."
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
    "branding": {
      "logoUrl": {
        "label": "Logo stránky (URL)",
        "help": "Zobrazí sa vo verejnom menu, administrácii a maintenance stránkach."
      },
      "faviconUrl": {
        "label": "Favicon (URL)",
        "help": "Ikona v karte prehliadača. Odporúčané ICO, PNG alebo SVG."
      },
      "imagePicker": {
        "pickFromMedia": "Vybrať z médií",
        "uploadLocal": "Nahrať z disku",
        "uploading": "Nahrávam…",
        "remove": "Odstrániť",
        "previewAlt": "Náhľad",
        "mediaModalTitle": "Vyberte obrázok",
        "uploadAlt": "Branding upload",
        "invalidType": "Nepodporovaný typ súboru",
        "uploadFailed": "Upload zlyhal"
      }
    },
    "privacy": {
      "cookieBannerEnabled": {
        "label": "Zobraziť cookie lištu",
        "help": "Zapnuté = pri prvej návšteve sa zobrazí súhlas s cookies (GDPR)."
      },
      "cookieBannerText": {
        "label": "Text cookie lišty"
      },
      "cookiePolicyUrl": {
        "label": "URL zásad cookies / GDPR",
        "help": "Voliteľný odkaz na stránku so zásadami ochrany súkromia."
      },
      "cookieShowRejectButton": {
        "label": "Tlačidlo „Odmietnuť voliteľné“",
        "help": "Zapnuté = návštevník môže odmietnuť voliteľné cookies okrem technicky nutných."
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
      "scanRequestBody": {
        "label": "Skenuj POST/JSON telo",
        "help": "Zapnuté = WAF kontroluje telo mutujúcich requestov (editor API je vyňaté). Vypnuté = skenuje sa len URI a hlavičky."
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
        "label": "Povoliť feedy",
        "help": "Zapnuté = RSS a sitemap.xml sú dostupné. Vypnuté = feedy vracajú 404."
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
        "label": "Sitemap: podstránky",
        "help": "Zapnuté = statické stránky sú v sitemap. Vypnuté = stránky v sitemap chýbajú."
      },
      "includeArticles": {
        "label": "RSS/Sitemap: články",
        "help": "Zapnuté = články v RSS a sitemap. Vypnuté = články v feedoch chýbajú."
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
        "label": "Povoliť SSO prihlásenie",
        "help": "Zapnuté = externé prihlásenie je dostupné (podľa zapnutých providerov). Vypnuté = len lokálne účty."
      },
      "defaultRole": {
        "label": "Predvolená rola (nový účet)"
      },
      "githubEnabled": {
        "label": "GitHub OAuth",
        "help": "Zapnuté = tlačidlo „Prihlásiť cez GitHub“. Vypnuté = GitHub login skrytý."
      },
      "githubClientId": {
        "label": "GitHub Client ID"
      },
      "githubClientSecret": {
        "label": "GitHub Client Secret"
      },
      "genericEnabled": {
        "label": "Generic OAuth2",
        "help": "Zapnuté = Generic OAuth2 provider podľa nižšie zadaných údajov. Vypnuté = provider vypnutý."
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
  },
  "maintenance": {
    "mode": {
      "off": {
        "title": "Vypnuté",
        "description": "Verejný web beží normálne."
      },
      "comingSoon": {
        "title": "Coming Soon",
        "description": "Nová stránka pred spustením — teaser s newsletterom."
      },
      "underMaintenance": {
        "title": "Under Maintenance",
        "description": "Dočasná údržba existujúceho webu so správou pre návštevníkov."
      }
    }
  }
};
