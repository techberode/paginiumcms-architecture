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
  "helpTooltip": {
    "toggle": "Zobraziť podrobnú nápovedu"
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
    "navigation": "Layout navigácie",
    "smtp": "Email / SMTP",
    "notifications": "Toast notifikácie",
    "connectors": "Notifikačné konektory",
    "monitoring": "Monitoring a incidenty",
    "scheduler": "Plánovač jobov",
    "systemUpdate": "Aktualizácia systému (deploy)",
    "codePolicy": "Politika kódu",
    "engine": "Hybridný engine",
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
    "media": "Médiá / DAM",
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
    "probeTitle": "Diagnostika schopností enginu",
    "probeIntro": "Aktuálny profil nasadenia a nainštalované schopnosti hybridného enginu. Budúce režimy sú zobrazené ako nedostupné — nie ako funkčné prepínače.",
    "deploymentMode": "Režim nasadenia",
    "storageDriver": "Ovládač úložiska",
    "cacheProbeTitle": "Cache vrstva — diagnostika",
    "cacheDriver": "Ovládač cache",
    "cacheHealth": "Stav cache",
    "gitProbeTitle": "Git publish — diagnostika",
    "gitProbeStatus": "Stav Git publish",
    "gitProbeStrategy": "Nastavená stratégia",
    "performanceGuardTitle": "Performance Guard (APM)",
    "performanceGuardIntro": "Meranie latencie a I/O v rámci requestu. Predvolene vypnuté — budgety nastavte podľa vlastného hardvéru. Nenahrádza metriky hostiteľa.",
    "performanceGuardOverhead": "Overhead rastie so sample rate; ring buffer drží posledných 500 route šablón bez content payloadov.",
    "docsLink": "Dokumentácia architektúry hybridného enginu"
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
        "description": "Skladanie cez shortcodes v Markdown / WYSIWYG."
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
  "privacy": {
    "panel": {
      "title": "Editor Cookies & GDPR",
      "description": "Nastavte verejnú lištu, obsah stránky /cookies, právne bloky a kontakt.",
      "preview": "Náhľad stránky",
      "bannerTitle": "Cookie lišta",
      "pageTitle": "Hlavička stránky",
      "blocksTitle": "Vlastné GDPR bloky",
      "blocksHint": "Pridajte právne sekcie (prevádzkovateľ, právny základ, retention…). Iba plain text.",
      "blocksEmpty": "Zatiaľ žiadne bloky.",
      "addBlock": "Pridať blok",
      "blockLabel": "Blok {index}",
      "blockTitlePlaceholder": "Nadpis sekcie (napr. Prevádzkovateľ)",
      "blockBodyPlaceholder": "Text sekcie — plain text, zachované zalomenia riadkov.",
      "removeBlock": "Odstrániť blok",
      "moveUp": "Posunúť hore",
      "moveDown": "Posunúť dole",
      "contactTitle": "GDPR kontakt",
      "contactHint": "Zobrazí sa na /cookies. Prázdne polia doplní nastavenia Company.",
      "sectionsTitle": "Vstavané sekcie stránky"
    }
  },
  "accessControl": {
    "superAdminTitle": "Len pre super administrátora",
    "superAdminHint": "Mapovanie oprávnení rolí ADMIN, EDITOR a USER a path ACL pravidlá môže meniť iba SUPER_ADMIN. SUPER_ADMIN má vždy plný prístup. Vlastné role spravujte v",
    "rolesManagerLink": "Bezpečnosť → Vlastné role",
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
      "git:publish": "Publikovanie cez Git",
      "gallery:manage": "Správa galérie",
      "logs:view": "Prehliadanie logov",
      "metrics:read": "Čítanie metrik",
      "api-keys:manage": "Správa API kľúčov",
      "redirects:manage": "Správa presmerovaní",
      "webhooks:manage": "Správa webhookov",
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
        "help": "Naraz môže byť zapnutý iba jeden režim.",
        "tooltip": "Vypnuté = verejný web beží normálne. Coming Soon = teaser s voliteľným newsletterom; admin a prihlásenie fungujú. Údržba = návštevníci vidia maintenance stránku; registrácia je blokovaná a verejné API môže vracať 503 podľa middleware."
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
        "help": "md = YAML front matter + Markdown; json = čistý JSON súbor."
      },
      "defaultStatus": {
        "label": "Predvolený stav obsahu",
        "help": "Nové stránky/články vzniknú ako koncept alebo rovno publikované."
      },
      "autoSaveInterval": {
        "label": "Interval auto-save (s)",
        "help": "Ako často sa ukladá koncept."
      },
      "lockTtl": {
        "label": "Platnosť zámku obsahu (s)",
        "help": "Auto-release zámku po nečinnosti."
      },
      "autoTagEnabled": {
        "label": "Navrhovanie tagov v editore",
        "help": "Povolí tlačidlo „Navrhnúť tagy“ v editore článkov."
      },
      "autoTagMax": {
        "label": "Max. počet navrhovaných tagov",
        "help": "Koľko tagov vráti generátor naraz."
      },
      "autoDescriptionEnabled": {
        "label": "Generovanie meta popisu",
        "help": "Povolí tlačidlo „Generovať popis“ v editore."
      },
      "autoDescriptionMaxLength": {
        "label": "Max. dĺžka meta popisu (znaky)",
        "help": "Odporúčané 150–160 znakov pre SEO."
      },
      "localeFallbackEnabled": {
        "label": "Povoliť locale fallback",
        "help": "Ak chýba požadovaný jazyk, použije sa defaultLocale alebo jazyk stránky.",
        "tooltip": "Platí len pre verejné čítanie obsahu. Ak článok nemá slovenskú variantu, CMS môže vrátiť dokument v predvolenom jazyku namiesto 404. Neprekladá automaticky — použije existujúci locale súbor."
      },
      "localeNegotiationEnabled": {
        "label": "Accept-Language pre verejný obsah",
        "help": "Verejné GET môže zvoliť locale z hlavičky Accept-Language ak chýba ?locale=.",
        "tooltip": "Prehliadače posielajú Accept-Language pri prvej návšteve. Zapnuté = zoznamy a detail môžu zvoliť najlepší jazyk pred pravidlami fallbacku."
      },
      "staleReviewMonths": {
        "label": "Prah zastarávajúceho obsahu (mesiace)",
        "help": "Publikovaný obsah starší ako tento počet mesiacov sa označí ako zastaralý. 0 = vypnuté."
      }
    },
    "editor": {
      "defaultEditor": {
        "label": "Predvolený editor",
        "help": "Ktorý editor sa otvorí pri novom obsahu (Markdown alebo WYSIWYG)."
      },
      "defaultProfilePage": {
        "label": "Predvolený profil (stránky)",
        "help": "Modulárny toolbar pre stránky."
      },
      "defaultProfileArticle": {
        "label": "Predvolený profil (články)",
        "help": "Modulárny toolbar pre články."
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
        "help": "Pluginy môžu registrovať vlastné bloky pre Markdown a WYSIWYG."
      },
      "profileCustomComponents": {
        "label": "Custom komponenty podľa profilu (JSON)",
        "help": "Spravuje sa v matici nižšie."
      }
    },
    "navigationUi": {
      "defaultPreviewScale": {
        "label": "Predvolená mierka hover náhľadu (×10)",
        "help": "Hodnota 15 = mierka 1.5× pri hover ikony v menu."
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
    "navigation": {
      "placement": {
        "label": "Umiestnenie menu",
        "help": "Horný panel, bočný strom alebo oboje na desktope."
      },
      "sideBreakpoint": {
        "label": "Breakpoint bočného menu",
        "help": "Pod touto šírkou sa bočný stĺpec skryje; mobilný drawer zostáva v hlavičke."
      },
      "expandAnimation": {
        "label": "Animácia rozbaľovania",
        "help": "Cascade motion v bočnom menu. Respektuje prefers-reduced-motion."
      },
      "maxDepth": {
        "label": "Max. hĺbka menu",
        "help": "SUPER_ADMIN môže nastaviť 4 úrovne; predvolene 3."
      }
    },
    "smtp": {
      "enabled": {
        "label": "Zapnúť SMTP",
        "help": "Zapnuté = systém odosiela e-maily (OTP, notifikácie, kontakt). Vypnuté = odosielanie vypnuté."
      },
      "host": {
        "label": "SMTP server"
      },
      "port": {
        "label": "SMTP port"
      },
      "encryption": {
        "label": "Šifrovanie"
      },
      "username": {
        "label": "SMTP používateľ"
      },
      "password": {
        "label": "SMTP heslo",
        "help": "Uložené v settings.json; nikdy nie cez verejné API."
      },
      "fromEmail": {
        "label": "Odosielateľ (e-mail)"
      },
      "fromName": {
        "label": "Odosielateľ (meno)"
      }
    },
    "notifications": {
      "toastEnabled": {
        "label": "Zapnúť toast notifikácie",
        "help": "Zapnuté = krátke hlášky pri ukladaní a chybách v admin rozhraní. Vypnuté = bez toastov."
      },
      "toastPosition": {
        "label": "Pozícia toastov"
      },
      "toastDuration": {
        "label": "Predvolené trvanie (ms)"
      },
      "toastDebugMode": {
        "label": "Debug režim (dlhšie toasty, log do konzoly)",
        "help": "Užitočné pri vývoji modulov, code editora a obsahových workflow."
      }
    },
    "connectors": {
      "emailEnabled": {
        "label": "E-mailový kanál (SMTP)",
        "help": "Zapnuté = incidenty a reporty môžu ísť e-mailom (vyžaduje zapnuté SMTP). Vypnuté = kanál e-mail sa nepoužije."
      },
      "ntfyEnabled": {
        "label": "Zapnúť ntfy",
        "help": "Zapnuté = push notifikácie cez ntfy. Vypnuté = kanál ntfy vypnutý."
      },
      "ntfyServer": {
        "label": "URL ntfy servera"
      },
      "ntfyTopic": {
        "label": "ntfy topic"
      },
      "ntfyAuthMode": {
        "label": "ntfy autentifikácia",
        "help": "Token pre ntfy.sh ACL topic alebo Basic pre self-hosted inštanciu."
      },
      "ntfyAccessToken": {
        "label": "ntfy prístupový token"
      },
      "ntfyUsername": {
        "label": "ntfy používateľ (Basic auth)"
      },
      "ntfyPassword": {
        "label": "ntfy heslo (Basic auth)"
      },
      "discordEnabled": {
        "label": "Zapnúť Discord webhook",
        "help": "Zapnuté = incidenty sa posielajú na Discord webhook. Vypnuté = webhook sa nevolá."
      },
      "discordWebhookUrl": {
        "label": "URL Discord webhooku"
      },
      "telegramEnabled": {
        "label": "Zapnúť Telegram bota",
        "help": "Zapnuté = incidenty do Telegram chatu. Vypnuté = bot sa nepoužije."
      },
      "telegramBotToken": {
        "label": "Token Telegram bota"
      },
      "telegramChatId": {
        "label": "Telegram chat ID"
      },
      "webhookEnabled": {
        "label": "Zapnúť generický webhook",
        "help": "Zapnuté = incidenty na vlastný HTTP webhook. Vypnuté = webhook vypnutý."
      },
      "webhookUrl": {
        "label": "URL webhooku"
      },
      "webhookSecret": {
        "label": "Webhook secret (voliteľné)"
      },
      "webhookAuthHeader": {
        "label": "Názov auth hlavičky webhooku",
        "help": "HTTP hlavička pre odoslanie webhookSecret ak je nastavený."
      }
    },
    "monitoring": {
      "alertsEnabled": {
        "label": "Zapnúť incident alerty",
        "help": "Zapnuté = systém posiela alerty pri incidentoch cez zapnuté kanály. Vypnuté = žiadne automatické alerty."
      },
      "alertEmail": {
        "label": "Záložný e-mail pre alerty",
        "help": "Použije sa keď sú konektory vypnuté; predvolene admin e-mail."
      },
      "notifyFailedLogin": {
        "label": "Alert pri neúspešnom prihlásení",
        "help": "Zapnuté = upozornenie pri neúspešnom prihlásení. Vypnuté = udalosť sa nehlási."
      },
      "notifySecurityIncident": {
        "label": "Alert pri bezpečnostných audit udalostiach",
        "help": "Zapnuté = upozornenie pri bezpečnostných audit udalostiach. Vypnuté = neposiela sa."
      },
      "notifyTrafficSpike": {
        "label": "Alert pri náhlej návštevnosti",
        "help": "Zapnuté = alert pri prekročení prahu návštevnosti. Vypnuté = traffic spike sa ignoruje."
      },
      "trafficSpikeThreshold": {
        "label": "Prah návštevnosti (návštevy/hod)"
      },
      "minSeverity": {
        "label": "Minimálna závažnosť auditu"
      },
      "reportsEnabled": {
        "label": "Zapnúť plánované monitoring reporty",
        "help": "Vyžaduje cron: php backend/bin/console monitoring:run-schedule"
      },
      "reportInterval": {
        "label": "Interval reportu"
      },
      "reportTime": {
        "label": "Čas odoslania (HH:MM)",
        "help": "Pre denné a týždenné reporty (časové pásmo webu)."
      },
      "reportWeekday": {
        "label": "Deň týždenného reportu"
      },
      "reportMinute": {
        "label": "Minúta hodinového reportu (0–59)",
        "help": "Pre hodinový interval — minúta v rámci každej hodiny."
      },
      "reportConnector": {
        "label": "Konektor pre reporty"
      },
      "reportIncludeAnalytics": {
        "label": "Report: analytika",
        "help": "Zapnuté = report obsahuje štatistiky návštevnosti. Vypnuté = bez analytiky."
      },
      "reportIncludeHealth": {
        "label": "Report: stav systému",
        "help": "Zapnuté = report obsahuje stav zdravia systému. Vypnuté = bez health sekcie."
      },
      "reportIncludeFlatFile": {
        "label": "Report: počty flat-file",
        "help": "Zapnuté = report obsahuje počty stránok/článkov. Vypnuté = bez štatistík obsahu."
      },
      "notifyLogErrors": {
        "label": "Alert pri ERROR/CRITICAL v logu",
        "help": "Zapnuté = ERROR/CRITICAL v logu spustí alert. Vypnuté = chyby v logu sa nehlásia."
      },
      "notifyLogWarnings": {
        "label": "Alert pri WARNING v logu",
        "help": "Zapnuté = WARNING v logu spustí alert. Vypnuté = varovania sa nehlásia."
      },
      "logIncidentConnector": {
        "label": "Konektor pre log incidenty"
      }
    },
    "scheduler": {
      "enabled": {
        "label": "Zapnúť plánovač jobov",
        "help": "Hlavný vypínač pre scheduler:run CLI. Jednotlivé joby sa dajú stále prepínať v Plánovači."
      },
      "retainRuns": {
        "label": "Počet záznamov histórie behov"
      }
    },
    "engine": {
      "deploymentMode": {
        "label": "Režim nasadenia",
        "help": "Aktívny je len režim Classic. Hybrid a Git headless sa zobrazia ako neinštalované.",
        "tooltip": "Classic = flat-file SSOT na disku (predvolené). Hybrid/Git sú rezervované pre budúce iterácie — zmena režimu automaticky nemigruje dáta."
      },
      "storageDriver": {
        "label": "Ovládač úložiska",
        "help": "Lokálny flat-file ovládač (predvolený). Vzdialené ovládače prídu v neskorších iteráciách.",
        "tooltip": "Classic SSOT = JSON/Markdown na disku pod data/. Vzdialené úložisko nie je v tomto release aktívne."
      },
      "schemaValidationEnabled": {
        "label": "Validácia JSON Schema",
        "help": "Zapnuté = admin JSON dokumenty sa pred zápisom overia proti registrovaným schémam.",
        "tooltip": "Chráni flat-file dáta pred neplatným JSON tvarom. Odporúčané nechať zapnuté na produkcii."
      },
      "capabilityProbeEnabled": {
        "label": "Zapnúť capability probe",
        "help": "Zobrazí diagnostiku schopností enginu v nastaveniach.",
        "tooltip": "Panel nižšie ukazuje dostupnosť cache, Git a storage. Vypnutím skryjete probe — správanie enginu sa nemení."
      },
      "cacheDriver": {
        "label": "Ovládač cache",
        "help": "auto = reťazec memory + file. Redis sa zobrazí ako neinštalovaný, ak nie je dostupný.",
        "tooltip": "auto zvolí najlepší dostupný driver za behu. file prežije medzi requestmi; memory len v rámci procesu. Redis vyžaduje extension a env — panel nižšie ukazuje aktívny driver."
      },
      "cacheDefaultTtlSeconds": {
        "label": "Predvolená TTL cache (s)",
        "help": "Platí pre nové cache kľúče, ak nie je uvedené inak (60–86400)."
      },
      "httpValidatorsEnabled": {
        "label": "HTTP ETag / Last-Modified",
        "help": "Podmienené requesty na bezpečných verejných GET endpointoch (napr. /api/settings/public).",
        "tooltip": "Klient môže poslať If-None-Match / If-Modified-Since a dostať 304 bez tela. Znižuje traffic pre verejné nastavenia."
      },
      "gitEnabled": {
        "label": "Povoliť Git publish distribúciu",
        "help": "Git je len distribúcia; SSOT zostáva na disku. Predvolene vypnuté.",
        "tooltip": "Zapnuté = zápisy obsahu môžu vytvárať Git commity v pracovnom strome. Nenahrádza flat-file úložisko — zlyhanie Git operácie nevracia CMS zápis späť."
      },
      "gitPublishStrategy": {
        "label": "Stratégia Git publish",
        "help": "disabled = bez Git volaní; immediate = commit pri zápise; queued = dávkový release commit.",
        "tooltip": "immediate pridáva latenciu každému mutujúcemu save; queued zoskupí commity pred deployom. Push vyžaduje gitPushEnabled a platné credentials na serveri."
      },
      "gitPublisher": {
        "label": "Ovládač Git publish",
        "help": "local = git binary na serveri. github_api je v tomto release odložené.",
        "tooltip": "Publish beží len na serveri. github_api vyžaduje token a outbound sieť — zatiaľ nie je aktívne."
      },
      "gitRepositoryPath": {
        "label": "Cesta k Git repozitáru",
        "help": "Absolútna serverová cesta k working tree so pages/ a blog/. Nikdy nie vo frontend API.",
        "tooltip": "Musí byť zapisovateľná pre PHP usera. Prázdna = Git publish sa preskočí aj keď je zapnutý."
      },
      "gitRemote": {
        "label": "Názov Git remote",
        "help": "Povolený názov remote (napr. origin)."
      },
      "gitBranch": {
        "label": "Git vetva",
        "help": "Povolený názov vetvy pre voliteľný push."
      },
      "gitPushEnabled": {
        "label": "Push po commite",
        "help": "Po úspešnom commite sa pokúsi git push na nakonfigurovaný remote/branch.",
        "tooltip": "Beží len na serveri. Vyžaduje SSH kľúče alebo credentials pre PHP usera v Dockeri — nikdy nie v admin UI."
      },
      "gitCommitMessageTemplate": {
        "label": "Šablóna commit správy",
        "help": "Použite placeholder {count} pre počet staged súborov."
      },
      "performanceGuardEnabled": {
        "label": "Povoliť Performance Guard (APM)",
        "help": "Ľahke vzorkovanie latencie a I/O v rámci requestu. Predvolene vypnuté.",
        "tooltip": "Ukladá časy podľa route do ring bufferu na Dashboarde. Overhead rastie so sample rate; obsah a nastavenia nemení automaticky okrem allow-list cache purge v remediation režime."
      },
      "performanceGuardSampleRate": {
        "label": "APM sample rate",
        "help": "1.0 = každý request keď je zapnuté; nižšie hodnoty znižujú overhead.",
        "tooltip": "Na vyťažených weboch skúste 0.1–0.3. Media/static trasy sú vylúčené zo skreslenia p95."
      },
      "performanceGuardLatencyMsWarning": {
        "label": "Varovanie latencie (ms)"
      },
      "performanceGuardLatencyMsCritical": {
        "label": "Kritická latencia (ms)"
      },
      "performanceGuardBreachCount": {
        "label": "Porušení pred incidentom"
      },
      "performanceGuardWindowMinutes": {
        "label": "Okno porušení (min)"
      },
      "performanceGuardRemediationMode": {
        "label": "Remediation režim",
        "help": "suggest = len incidenty; automatic = allow-list cache purge po probe.",
        "tooltip": "automatic nikdy nezapne Redis ani nemení engine režim — len bezpečné vymazanie cache po opakovaných porušeniach budgetu. suggest = manuálna kontrola incidentov."
      },
      "performanceGuardServerTiming": {
        "label": "Server-Timing hlavička",
        "help": "Fázy sess-lock/storage/app v DevTools (tiež pri APP_DEBUG).",
        "tooltip": "Pridá W3C Server-Timing do HTTP odpovedí. Viditeľné v Network tab prehliadača — na produkcii vypnite ak nechcete zverejňovať rozklad fáz klientom."
      }
    },
    "contentSecurity": {
      "sanitizeHtmlOnSave": {
        "label": "Sanitizovať HTML pri ukladaní",
        "help": "Odstráni nebezpečné tagy a atribúty z HTML/WYSIWYG výstupu."
      },
      "stripExternalEntities": {
        "label": "Odstrániť externé entity (XXE)",
        "help": "Pri parsovaní XML/HTML blokuje externé DTD a entity — odporúčané zapnuté."
      },
      "allowSvgInline": {
        "label": "Povoliť inline SVG v obsahu",
        "help": "SVG môže obsahovať skript — odporúčame vypnuté.",
        "tooltip": "Inline SVG môže niesť JavaScript a externé referencie. Nechajte vypnuté pokiaľ editori nie sú plne dôveryhodní."
      },
      "allowScriptTags": {
        "label": "Povoliť <script> v obsahu",
        "help": "Len pre dôveryhodných editorov; predvolene vypnuté.",
        "tooltip": "Script tagy v HTML obídu väčšinu XSS ochrany pri renderi. Verejný web vykoná JavaScript od editora."
      },
      "allowedHtmlTags": {
        "label": "Povolené HTML tagy",
        "help": "Whitelist oddelený čiarkou. Layout shortcodes potrebujú div/article/section/aside/span.",
        "tooltip": "Tagy mimo zoznamu sa pri save odstránia ak je sanitizácia zapnutá. Príliš voľný zoznam (iframe, object) zvyšuje XSS riziko."
      }
    },
    "uploadSecurity": {
      "scanMagicBytes": {
        "label": "Kontrolovať magic bytes súboru",
        "help": "Porovná hlavičku súboru s deklarovaným MIME typom.",
        "tooltip": "Blokuje premenované executable (napr. shell.php ako image/jpeg). Odporúčané zapnuté na internetovom upload povrchu."
      },
      "blockDoubleExtensions": {
        "label": "Blokovať dvojité prípony",
        "help": "Napr. shell.php.jpg — bežný upload útok.",
        "tooltip": "Odmietne názvy s viacerými príponami kde vnútorná segment je spustiteľný. Dopĺňa MIME a magic-byte kontrolu."
      },
      "blockExecutables": {
        "label": "Blokovať spustiteľné súbory",
        "help": "Odmieta upload PHP, shell skriptov a podobných typov bez ohľadu na príponu."
      },
      "allowedExtensions": {
        "label": "Povolené prípony",
        "help": "Bez bodky, oddelené čiarkou.",
        "tooltip": "Platí spolu s MIME typmi a media nastaveniami. Sprísnite pred povolením SVG/PDF ak uploadujú nedôveryhodní používatelia."
      },
      "maxUploadSizeKb": {
        "label": "Max. veľkosť uploadu (KB)"
      },
      "backupImportMaxSizeKb": {
        "label": "Max. veľkosť importu zálohy (KB)",
        "help": "Limit pre ZIP import zálohy cez administráciu."
      },
      "allowedMimeTypes": {
        "label": "Povolené MIME typy (upload)",
        "help": "Oddelené čiarkou. Dopĺňa kontrolu prípon a magic bytes."
      }
    },
    "accessControl": {
      "pathAclEnabled": {
        "label": "Povoliť path ACL",
        "help": "Obmedzí prístup k vybraným flat-file cestám podľa role alebo oprávnenia.",
        "tooltip": "Po zapnutí sa pravidlá vyhodnocujú pred read/write API obsahu. Cesty používajú glob pod content/pages a content/articles. SUPER_ADMIN path ACL obíde."
      }
    },
    "codePolicy": {
      "enabled": {
        "label": "Zapnúť kontrolu politiky kódu (jadro)",
        "help": "Platí pre zápisy v Code Editore jadra. Nedôveryhodné cesty (pluginy, témy, shortcodes) sa kontrolujú vždy, aj keď je toto vypnuté."
      },
      "strictMode": {
        "label": "Prísne pravidlá pre namespaces rozšírení",
        "help": "Zapnuté = prísnejšie pravidlá pre namespaces rozšírení. Vypnuté = uvoľnené pravidlá."
      },
      "maxFileSizeKb": {
        "label": "Max. veľkosť súboru (KB)"
      },
      "untrustedMaxFileSizeKb": {
        "label": "Max. veľkosť nedôveryhodného súboru (KB)",
        "help": "Limit pre pluginy, témy a layout shortcodes. Nemôže prekročiť max. veľkosť súboru vyššie."
      },
      "forbiddenPhpFunctions": {
        "label": "Zakázané PHP funkcie",
        "help": "Zoznam oddelený čiarkou kontrolovaný pred uložením."
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
      },
      "spamHeuristicsEnabled": {
        "label": "Spam heuristika",
        "help": "Honeypot + skóre (linky, disposable e-mail, rýchlosť). Vypnutím zostáva len honeypot."
      },
      "spamMaxLinks": {
        "label": "Spam: max. linkov v texte"
      },
      "spamVelocityMaxPerHour": {
        "label": "Spam: max. komentárov / IP / hod"
      },
      "spamQuarantineThreshold": {
        "label": "Spam: hranica karantény (skóre)"
      },
      "spamRejectThreshold": {
        "label": "Spam: hranica odmietnutia (skóre)"
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
        "help": "Nový účet vznikne až po overení e-mailového kódu.",
        "tooltip": "Registrácia uloží pending usera kým neoverí OTP z mailu. Vyžaduje funkčný SMTP/notifikácie — inak registrácie visia v pending stave."
      },
      "commentApprovalOtpEnabled": {
        "label": "OTP pri schválení komentára",
        "help": "Editor musí potvrdiť schválenie komentára kódom z mailu."
      },
      "publishApprovalOtpEnabled": {
        "label": "OTP pri publikácii",
        "help": "Editor musí potvrdiť publikáciu príspevku kódom z mailu.",
        "tooltip": "Platí pri zmene stavu na published. Druhý faktor cez e-mail pred akceptovaním publish zápisu — vhodné pre regulované redakčné workflow."
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
        "help": "Badge s počtom položiek pri moduloch v administrácii."
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
        "help": "Pri zapnutí nie je možné vypnúť 2FA pre roly EDITOR, ADMIN a SUPER_ADMIN.",
        "tooltip": "Editori a admini bez autentifikátora budú pri ďalšom prihlásení vyzvaní na nastavenie 2FA. Neovplyvňuje návštevníkov ani rolu USER."
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
        "help": "Voliteľné. Prázdne = vestavná stránka /cookies. Relatívna cesta alebo externá URL."
      },
      "cookieShowRejectButton": {
        "label": "Tlačidlo „Odmietnuť voliteľné“",
        "help": "Zapnuté = návštevník môže odmietnuť voliteľné cookies okrem technicky nutných."
      },
      "cookiePolicyPageTitle": {
        "label": "Nadpis stránky /cookies",
        "help": "Prázdne = predvolený preklad podľa jazyka webu."
      },
      "cookiePolicyIntro": {
        "label": "Úvodný text stránky /cookies",
        "help": "Vlastný úvod pod nadpisom. Prázdne = predvolený i18n text."
      },
      "cookiePolicySectionsJson": {
        "label": "GDPR bloky (JSON)",
        "help": "Spravované vizuálnym editorom nižšie."
      },
      "privacyContactName": {
        "label": "Meno alebo prevádzkovateľ",
        "help": "Zobrazí sa na /cookies. Prázdne = právny alebo zobrazovaný názov z Company."
      },
      "privacyContactEmail": {
        "label": "E-mail",
        "help": "Prázdne = e-mail z nastavení Company."
      },
      "privacyContactPhone": {
        "label": "Telefón",
        "help": "Prázdne = telefón z nastavení Company."
      },
      "privacyContactAddress": {
        "label": "Kontakt GDPR — adresa",
        "help": "Prázdne = adresa z nastavení Company."
      },
      "cookiePolicyShowCategoriesTable": {
        "label": "Zobraziť tabuľku kategórií cookies"
      },
      "cookiePolicyShowStorageInventory": {
        "label": "Zobraziť technický inventár úložísk"
      },
      "cookiePolicyShowDefaultRights": {
        "label": "Zobraziť sekciu „Vaše možnosti“"
      },
      "cookiePolicyShowManagePanel": {
        "label": "Zobraziť panel správy súhlasu"
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
        "help": "Zapnuté = WAF kontroluje telo mutujúcich requestov (editor API je vyňaté). Vypnuté = skenuje sa len URI a hlavičky.",
        "tooltip": "Skenuje JSON telá POST/PUT/PATCH na injection vzory. Editor trasy (/api/pages, /api/articles, drafts, code-editor) a multipart uploady sú vyňaté kvôli false positive na legitímny HTML/Markdown."
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
        "help": "Režim HTTP odpovede pre zablokované IP. Tarpit spomaľuje botov (max 2 s).",
        "tooltip": "forbidden = HTTP 403 JSON; empty = minimálne telo; tarpit = zámerné oneskorenie (viaže PHP-FPM worker — tarpitSeconds ≤ 2)."
      },
      "tarpitSeconds": {
        "label": "Tarpit oneskorenie (s)",
        "help": "Platí len pri jailMode=tarpit. Neodporúčame >2 s (FPM worker).",
        "tooltip": "Každý blokovaný request čaká v PHP pred odpoveďou. Vysoké hodnoty znižujú throughput pri bot floode ale spotrebujú worker sloty."
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
      },
      "includeResponseSize": {
        "label": "Logovať veľkosť odpovede (size_bytes)",
        "help": "Pridá size_bytes do http_access logu (Content-Length alebo veľkosť tela).",
        "tooltip": "Diagnostika odozvy. Pomáha odhaliť pomalé odpovede s malým objemom dát (session lock, cache miss). Net loguje telo odpovede — len počet bajtov."
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
      "storageDriver": {
        "label": "Ovládač úložiska médií",
        "help": "local = binárne súbory pod media/. S3 sa zobrazí v UI, ale fallbackuje na local, kým driver nie je aktívny."
      },
      "s3Endpoint": {
        "label": "S3 endpoint URL"
      },
      "s3Region": {
        "label": "S3 región"
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
        "help": "Zapnuté pre MinIO a kompatibilné služby bez virtuálneho hostovania bucketu."
      },
      "s3PublicBaseUrl": {
        "label": "Verejná base URL pre S3 objekty"
      },
      "s3Visibility": {
        "label": "S3 viditeľnosť objektov"
      },
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
        "help": "Zapnuté = externé prihlásenie je dostupné (podľa zapnutých providerov). Vypnuté = len lokálne účty.",
        "tooltip": "Vypnuté = len e-mail/heslo. Zapnuté = OAuth provideri na login stránke; noví SSO používatelia dostanú predvolenú rolu nižšie."
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
    },
    "gallery": {
      "enabled": {
        "label": "Zapnúť galériu na verejnom webe",
        "help": "Hlavný prepínač pre sekciu so screenshotmi administrácie."
      },
      "placement": {
        "label": "Umiestnenie",
        "help": "Kde sa galéria zobrazí: domovská stránka, samostatná route, oboje, alebo vypnuté."
      },
      "publicRoute": {
        "label": "Verejná route",
        "help": "Jednosegmentová cesta bez domény, napr. /features alebo /funkcie."
      },
      "layout": {
        "label": "Layout",
        "help": "Grid = dlaždice; slider = carousel s autoplay; hero-strip = široký pás screenshotov."
      },
      "effectPreset": {
        "label": "Efekt (preset)",
        "help": "subtle = fade+scale; cinematic = crossfade+vignette; minimal = okamžitá výmena (bez animácie)."
      },
      "autoplayEnabled": {
        "label": "Autoplay slidera",
        "help": "Platí pre layout slider a hero-strip. Pauza pri hover/focus; vypnuté pri prefers-reduced-motion."
      },
      "autoplayIntervalMs": {
        "label": "Autoplay interval (ms)",
        "help": "Interval medzi slidmi (4000–15000 ms)."
      },
      "showFeatureTags": {
        "label": "Zobraziť tagy modulov",
        "help": "Badge s názvom modulu (Analytics, Newsletter, …) pri položkách galérie."
      },
      "modalCaptionStyle": {
        "label": "Štýl popisu v modale",
        "help": "below = pod obrázkom; overlay = cez spodok; side = vedľa (široké obrazovky)."
      }
    },
    "systemUpdate": {
      "deployEnabled": {
        "label": "Povoliť admin deploy",
        "help": "SUPER_ADMIN môže spustiť deploy kódu z Platform → System update. Ignorované keď DEMO_MODE=true."
      },
      "githubOwner": {
        "label": "GitHub owner"
      },
      "githubRepo": {
        "label": "GitHub repozitár"
      },
      "githubToken": {
        "label": "GitHub token (repo read)",
        "help": "Fine-grained alebo classic token s read prístupom ku kódu a release."
      },
      "defaultBranch": {
        "label": "Predvolená vetva"
      },
      "allowDeployMain": {
        "label": "Povoliť deploy z vetvy (origin/…)"
      },
      "allowDeployTags": {
        "label": "Povoliť deploy zo semver tagov"
      },
      "webhookDeployEnabled": {
        "label": "Zapnúť GitHub release webhook deploy",
        "help": "Po zapnutí POST /api/webhooks/github/release zaradí deploy pri publikovaní release (vyžaduje HMAC secret)."
      },
      "githubWebhookSecret": {
        "label": "GitHub webhook secret",
        "help": "Rovnaký secret ako v GitHub → Settings → Webhooks. Nikdy sa neloguje."
      }
    }
  },
  "enum": {
    "language": {
      "sk": "Slovenčina",
      "en": "English"
    },
    "deploymentMode": {
      "classic": "Classic",
      "hybrid": "Hybrid",
      "git_headless": "Git headless"
    },
    "storageDriver": {
      "local": "Lokálny"
    },
    "cacheDriver": {
      "auto": "Automaticky",
      "memory": "Pamäť",
      "file": "Súbor",
      "redis": "Redis"
    },
    "gitPublishStrategy": {
      "disabled": "Vypnuté",
      "immediate": "Okamžite",
      "queued": "Dávkovo"
    },
    "gitPublisher": {
      "local": "Lokálny git",
      "github_api": "GitHub API"
    },
    "performanceGuardRemediationMode": {
      "off": "Vypnuté",
      "suggest": "Len návrhy",
      "automatic": "Automatické"
    },
    "encryption": {
      "none": "Bez šifrovania",
      "tls": "TLS",
      "ssl": "SSL"
    },
    "toastPosition": {
      "top-right": "Vpravo hore",
      "top-left": "Vľavo hore",
      "bottom-right": "Vpravo dole",
      "bottom-left": "Vľavo dole"
    },
    "logIncidentConnector": {
      "email": "E-mail",
      "ntfy": "ntfy",
      "discord": "Discord",
      "telegram": "Telegram",
      "webhook": "Webhook",
      "all": "Všetky kanály"
    },
    "reportConnector": {
      "email": "E-mail",
      "ntfy": "ntfy",
      "discord": "Discord",
      "telegram": "Telegram",
      "webhook": "Webhook",
      "all": "Všetky kanály"
    },
    "reportInterval": {
      "hourly": "Každú hodinu",
      "daily": "Denne",
      "weekly": "Týždenne"
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
      "draft": "Koncept",
      "published": "Publikované"
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
      "empty": "Prázdna odpoveď",
      "tarpit": "Tarpit"
    },
    "placement": {
      "home": "Domovská stránka",
      "route": "Samostatná route",
      "both": "Oboje",
      "off": "Vypnuté",
      "top": "Horné menu",
      "side": "Bočné menu"
    },
    "layout": {
      "grid": "Mriežka",
      "slider": "Slider",
      "hero-strip": "Hero pás"
    },
    "effectPreset": {
      "subtle": "Jemný",
      "cinematic": "Filmový",
      "minimal": "Minimálny"
    },
    "modalCaptionStyle": {
      "below": "Pod obrázkom",
      "overlay": "Overlay",
      "side": "Bočne"
    }
  },
  "maintenance": {
    "mode": {
      "off": {
        "title": "Vypnuté",
        "description": "Verejný web beží normálne."
      },
      "comingSoon": {
        "title": "Už čoskoro",
        "description": "Nová stránka pred spustením — teaser s newsletterom."
      },
      "underMaintenance": {
        "title": "Údržba",
        "description": "Dočasná údržba existujúceho webu so správou pre návštevníkov."
      }
    }
  }
};
