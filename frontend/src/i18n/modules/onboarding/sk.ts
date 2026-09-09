import type { MessageTree } from '../../types';

export const onboardingSk: MessageTree = {
  badge: 'Začíname :current / :total',
  next: 'Ďalej',
  back: 'Späť',
  skip: 'Preskočiť',
  finish: 'Hotovo',
  open: 'Otvoriť',
  dontShow: 'Už nezobrazovať',
  steps: {
    dashboard: {
      title: 'Riadiace centrum',
      body: 'Dashboard ukazuje zdravie, zastaralý obsah a KPI plánovača. Sem sa vraciate po každom prihlásení.',
    },
    pages: {
      title: 'Podstránky',
      body: 'Vytvorte štruktúru webu — úvod, kontakt, landing — a publikujte, keď je text hotový.',
    },
    articles: {
      title: 'Články',
      body: 'Píšte príspevky ako koncepty, naplánujte ich, alebo ich prepojte s míľnikom v plánovači.',
    },
    media: {
      title: 'Knižnica médií',
      body: 'Nahrajte obrázky raz; použijú sa v editore, OG tagoch a verejných hero s responzívnymi náhľadmi.',
    },
    planner: {
      title: 'Plánovač projektu',
      body: 'Naplánujte termíny relaunchu skôr, než obsah existuje. Prepojenie na stránku/článok označí položku hotovou pri publikovaní.',
    },
    settings: {
      title: 'Nastavenia',
      body: 'Názov webu, vzhľad a oprávnenia. Táto prehliadka nie je sprievodca prvého spustenia.',
    },
  },
};
