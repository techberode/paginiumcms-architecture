// frontend/src/components/frontend/ContactForm.tsx
import React, { useState } from 'react';
import { MessageSquare, Send, Loader2 } from 'lucide-react';
import { submitContactForm } from '../../api/contact';
import { useToast } from '../../hooks/useToast';

export const ContactForm: React.FC = () => {
  const toast = useToast();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [sending, setSending] = useState(false);
  const [sent, setSent] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSending(true);
    const result = await submitContactForm({ name, email, subject, message });
    setSending(false);

    if (result.ok) {
      setSent(true);
      toast.success(result.message ?? 'Message sent successfully.');
      setName('');
      setEmail('');
      setSubject('');
      setMessage('');
    } else {
      toast.error(result.error);
    }
  };

  if (sent) {
    return (
      <div className="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-8 text-center">
        <p className="text-lg font-bold text-slate-900 dark:text-white">Thank you!</p>
        <p className="text-sm text-slate-500 mt-2">We received your message and will get back to you soon.</p>
        <button type="button" className="btn btn-secondary mt-4" onClick={() => setSent(false)}>
          Send another message
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
          <h3 className="text-xl font-extrabold text-slate-900 dark:text-white">Contact form</h3>
          <p className="text-xs text-slate-500 dark:text-slate-400">Messages are stored in the admin inbox.</p>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <input className="form-input" required minLength={2} placeholder="Your name" value={name} onChange={(e) => setName(e.target.value)} />
        <input className="form-input" required type="email" placeholder="Email" value={email} onChange={(e) => setEmail(e.target.value)} />
      </div>
      <input className="form-input" placeholder="Subject (optional)" value={subject} onChange={(e) => setSubject(e.target.value)} />
      <textarea
        className="form-input min-h-[140px]"
        required
        minLength={10}
        placeholder="Your message…"
        value={message}
        onChange={(e) => setMessage(e.target.value)}
      />
      <button type="submit" className="btn btn-primary w-full sm:w-auto" disabled={sending}>
        {sending ? <Loader2 className="w-4 h-4 animate-spin inline mr-2" /> : <Send className="w-4 h-4 inline mr-2" />}
        Send message
      </button>
    </form>
  );
};

export default ContactForm;
