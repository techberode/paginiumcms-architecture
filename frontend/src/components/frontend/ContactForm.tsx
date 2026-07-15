// frontend/src/components/frontend/ContactForm.tsx
import React from 'react';
import { MessageSquare } from 'lucide-react';

export const ContactForm: React.FC = () => (
  <div className="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 sm:p-10 shadow-xl shadow-slate-100 dark:shadow-none">
    <div className="flex items-center gap-3 mb-4">
      <div className="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-bold">
        <MessageSquare className="w-6 h-6" />
      </div>
      <div>
        <h3 className="text-xl font-extrabold text-slate-900 dark:text-white">Kontaktný formulár</h3>
        <p className="text-xs text-slate-500 dark:text-slate-400">
          API endpoint ešte nie je implementovaný. Ukážkové správy sú v Demo module (DEMO_MODE).
        </p>
      </div>
    </div>
    <p className="text-sm text-slate-600 dark:text-slate-400">
      Pre kontakt použite administrátorský e-mail z nastavení CMS alebo počkajte na budúcu iteráciu
      kontaktného API.
    </p>
  </div>
);

export default ContactForm;
