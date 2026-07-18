// frontend/src/components/CodeEditor/CodeEditorSafetyBanner.tsx
import React from 'react';
import { Link } from 'react-router-dom';
import { AlertTriangle } from 'lucide-react';

export const CodeEditorSafetyBanner: React.FC = () => {
  return (
    <div className="mx-4 mt-3 rounded-xl border border-amber-300/80 dark:border-amber-700/60 bg-amber-50 dark:bg-amber-950/30 p-4">
      <div className="flex gap-3">
        <AlertTriangle className="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
        <div className="text-sm text-amber-950 dark:text-amber-100 space-y-2">
          <p className="font-semibold">Nebezpečný nástroj — len pre skúsených vývojárov</p>
          <p className="text-amber-900/90 dark:text-amber-100/90 leading-relaxed">
            Tu sa mení <strong>PHP kód</strong>, nie obsah stránok. Chybný súbor môže spadnúť celý CMS (500, biela obrazovka,
            nefunkčné moduly). Na bežnú prácu používaj{' '}
            <Link to="/pages" className="underline font-medium">
              Podstránky
            </Link>
            ,{' '}
            <Link to="/articles" className="underline font-medium">
              Články
            </Link>{' '}
            a{' '}
            <Link to="/settings" className="underline font-medium">
              Nastavenia
            </Link>
            .
          </p>
          <ul className="list-disc pl-5 text-xs text-amber-900/80 dark:text-amber-100/80 space-y-1">
            <li>Nikdy neupravuj súbory, ktorým nerozumieš.</li>
            <li>Pred Save skontroluj syntax — backend časť chýb zablokuje, nie všetko.</li>
            <li>Pred zmenou si pozri zálohu v admin sekcii{' '}
              <Link to="/backups" className="underline">
                Zálohy
              </Link>
              .
            </li>
            <li>Po práci stlač <strong>Zamknúť editor</strong> — bez TOTP sa kód znova neuloží.</li>
            <li>Core, bootstrap a vendor sú zámerne nedostupné — neobchádzaj to.</li>
          </ul>
        </div>
      </div>
    </div>
  );
};

export default CodeEditorSafetyBanner;
