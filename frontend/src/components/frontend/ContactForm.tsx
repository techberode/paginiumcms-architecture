import React, { useMemo, useState } from 'react';
import { MessageSquare, Send, Loader2 } from 'lucide-react';
import { submitContactForm } from '../../api/contact';
import { useToast } from '../../hooks/useToast';
import { useSettingsContext } from '../../context/SettingsContext';
import { parseContactSubjects } from '../../utils/contactSubjects';

const CUSTOM_SUBJECT_VALUE = '__custom__';

export const ContactForm: React.FC = () => {
  const toast = useToast();
  const { settings } = useSettingsContext();
  const subjects = useMemo(
    () => parseContactSubjects(settings.contact?.subjects),
    [settings.contact?.subjects]
  );
  const allowCustomSubject = settings.contact?.allowCustomSubject !== false;

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [subjectChoice, setSubjectChoice] = useState(subjects[0] ?? '');
  const [customSubject, setCustomSubject] = useState('');
  const [message, setMessage] = useState('');
  const [sending, setSending] = useState(false);
  const [sent, setSent] = useState(false);

  const resolvedSubject =
    subjectChoice === CUSTOM_SUBJECT_VALUE ? customSubject.trim() : subjectChoice;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!resolvedSubject) {
      toast.error('Vyberte alebo zadajte predmet správy.');
      return;
    }

    setSending(true);
    const result = await submitContactForm({ name, email, subject: resolvedSubject, message });
    setSending(false);

    if (result.ok) {
      setSent(true);
      toast.success(result.message ?? 'Správa bola odoslaná.');
      setName('');
      setEmail('');
      setSubjectChoice(subjects[0] ?? '');
      setCustomSubject('');
      setMessage('');
    } else {
      toast.error(result.error);
    }
  };

  if (sent) {
    return (
      <div className="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-8 text-center">
        <p className="text-lg font-bold text-slate-900 dark:text-white">Ďakujeme!</p>
        <p className="text-sm text-slate-500 mt-2">Správu sme prijali a čoskoro sa ozveme.</p>
        <button type="button" className="btn btn-secondary mt-4" onClick={() => setSent(false)}>
          Odoslať ďalšiu správu
        </button>
      </div>
    );
  }

  return (
    <form
      onSubmit={(e) => void handleSubmit(e)}
      className="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 sm:p-10 shadow-xl shadow-slate-100 dark:shadow-none space-y-4"
    >
      <div className="flex items-center gap-3 mb-2">
        <div className="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400">
          <MessageSquare className="w-6 h-6" />
        </div>
        <div>
          <h3 className="text-xl font-extrabold text-slate-900 dark:text-white">Kontaktný formulár</h3>
          <p className="text-xs text-slate-500 dark:text-slate-400">Správy sa ukladajú do admin inboxu.</p>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <input className="form-input" required minLength={2} placeholder="Vaše meno" value={name} onChange={(e) => setName(e.target.value)} />
        <input className="form-input" required type="email" placeholder="E-mail" value={email} onChange={(e) => setEmail(e.target.value)} />
      </div>

      <div className="space-y-2">
        <label className="form-label" htmlFor="contact-subject">
          Predmet
        </label>
        <select
          id="contact-subject"
          className="form-input"
          value={subjectChoice}
          onChange={(e) => setSubjectChoice(e.target.value)}
        >
          {subjects.map((subject) => (
            <option key={subject} value={subject}>
              {subject}
            </option>
          ))}
          {allowCustomSubject && <option value={CUSTOM_SUBJECT_VALUE}>Vlastný predmet…</option>}
        </select>
        {subjectChoice === CUSTOM_SUBJECT_VALUE && (
          <input
            className="form-input"
            required
            minLength={3}
            placeholder="Vlastný predmet správy"
            value={customSubject}
            onChange={(e) => setCustomSubject(e.target.value)}
          />
        )}
      </div>

      <textarea
        className="form-input min-h-[140px]"
        required
        minLength={10}
        placeholder="Vaša správa…"
        value={message}
        onChange={(e) => setMessage(e.target.value)}
      />
      <button type="submit" className="btn btn-primary w-full sm:w-auto" disabled={sending}>
        {sending ? <Loader2 className="w-4 h-4 animate-spin inline mr-2" /> : <Send className="w-4 h-4 inline mr-2" />}
        Odoslať správu
      </button>
    </form>
  );
};

export default ContactForm;
