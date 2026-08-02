# Post-It.15 vlna — editor, navigácia a layout

> **Stav:** ✅ historická vlna prevažne dokončená  
> **Pôvodný gate:** It.15 PluginManager a extension runtime  
> **Zostávajúci aktívny rozsah:** It.58d a súvisiaci security gate It.67

Tento dokument už nie je plán nezačatej vlny. Zachytáva, čo závislosť na It.15 odomkla, čo bolo dodané a čo sa presunulo do nového Hybrid Engine smerovania.

---

## 1. Pôvodná závislosť

```text
It.15 PluginManager ✅
    ├─► It.53 Smooth SPA reload
    ├─► It.54 Modular editor profiles
    │      └─► It.55 Tiptap JSON + media upload
    ├─► It.56 Rich navigation
    ├─► It.57 Auto tags & description
    └─► It.58 Layout and appearance
```

It.15 zaviedol pravidlá externých rozšírení, registry a hook runtime. Tým odstránil architektonický blocker pre modulárny editor a ďalšie UX rozšírenia.

---

## 2. Výsledok vlny

| It. | Téma | Stav | Release / poznámka |
|-----|------|------|--------------------|
| **53** | Smooth SPA reload | ✅ | `2.0.39` |
| **54** | Modulárne Markdown/WYSIWYG profily | ✅ | `2.0.42` |
| **55** | Tiptap JSON a upload obrázkov | ✅ | `2.0.43` |
| **56** | Rich navigation items | ✅ | `v2.1.0-beta.5` |
| **57** | Auto tags a meta description | ✅ | `v2.1.0-beta.4` |
| **58b** | Color schemes a themed public site | ✅ | `v2.1.0-beta.8` |
| **58c** | Layout Switch a page templates | ✅ | `v2.1.0-beta.23` |
| **58d** | Zostávajúce layout bloky/polish | ⏳ | rozsah treba presne uzamknúť |

Rozšírené nad pôvodnú mapu:

| It. | Téma | Stav |
|-----|------|------|
| **59** | Scheduled publishing | ✅ |
| **60** | Custom editor components | ✅ |
| **61** | Newsletter footer + admin subscribers | ✅ |

---

## 3. Čo sa nesmie znovu plánovať

Nasledujúce položky už nie sú aktívny backlog:

- všeobecný „smooth reload“ bez konkrétnej regresie,
- základné editor profiles,
- Tiptap JSON storage,
- rich navigation descriptions/icons,
- základný auto-tags/meta generator,
- color schemes,
- základný layout template picker,
- custom editor component foundation,
- newsletter footer foundation.

Nová požiadavka musí byť opísaná ako konkrétne rozšírenie alebo bugfix, nie opätovné otvorenie celej dodanej iterácie.

---

## 4. Zostávajúci It.58d

Pred implementáciou treba uzamknúť:

- ktoré layout bloky ešte chýbajú,
- či patria do page modelu, theme runtime alebo static render pipeline,
- kompatibilitu s `layoutTemplate` z It.58c,
- schema/policy pravidlá pre používateľom editovateľný layout JSON,
- preview a fallback pri neplatnej konfigurácii,
- vzťah k It.48 static render a It.70 Git publish.

It.58d nesmie vytvoriť druhý nezávislý page-builder model.

---

## 5. Bezpečnostná nadväznosť — It.67

Čím viac je editor a layout rozšíriteľný, tým dôležitejšie je oddeliť dôveryhodný obsah od nedôveryhodného kódu. It.67 má pokryť najmä:

- shortcode a custom component vstupy,
- Monaco/Code Editor write gate,
- import tém a layout balíkov,
- CSP a závislosti,
- deny-list/allow-list kombinovanú so schema validáciou,
- regresné security packs.

---

## 6. Vzťah k Hybrid Engine

| Staršia vlna | Hybrid Engine pokračovanie |
|--------------|----------------------------|
| Tiptap JSON | It.68 schema registry a bezpečný storage kontrakt |
| Layout templates | It.48 static render + It.70 publish pipeline |
| Media upload | It.72 media drivers |
| i18n UI | It.73 multi-locale content document |
| Meta generator | It.75 AI agent návrhy, stále s human approval |

Pôvodná vlna preto nie je slepá ulička; je frontendový a UX základ novej architektúry.

---

## 7. Archívne pravidlo

Tento dokument sa udržiava ako **wave outcome**. Aktívne priority sú v [ROADMAP.md](ROADMAP.md) a [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md).
