// frontend/src/components/backend/PreviewPage.tsx
import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useApi } from '../../hooks/useApi';
import { PageRenderer } from '../frontend/PageRenderer';
import type { Page } from '../../api/types';

export const PreviewPage: React.FC = () => {
  const { slug } = useParams<{ slug: string }>();
  const { get } = useApi();
  const [page, setPage] = useState<Page | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!slug) {
      setLoading(false);
      return;
    }

    let cancelled = false;

    (async () => {
      setLoading(true);
      setError(null);
      try {
        const response = await get<Page>(`/api/pages/${slug}`);
        if (!cancelled) {
          setPage(response.data ?? null);
        }
      } catch {
        if (!cancelled) {
          setError('Stránku sa nepodarilo načítať (skontrolujte prihlásenie a slug).');
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [slug, get]);

  if (loading) {
    return (
      <div className="min-h-[50vh] flex items-center justify-center">
        <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600" />
      </div>
    );
  }

  if (error || !page) {
    return (
      <div className="p-8 text-center">
        <p className="text-slate-500">{error ?? 'Stránka nenájdená'}</p>
        <Link to="/pages" className="mt-4 inline-block text-indigo-600 font-semibold">
          ← Späť na zoznam
        </Link>
      </div>
    );
  }

  return (
    <div>
      <div className="bg-amber-50 dark:bg-amber-950/40 border-b border-amber-200 dark:border-amber-800 px-4 py-2 text-sm text-amber-900 dark:text-amber-100 flex items-center justify-between">
        <span>
          Náhľad · stav: <strong>{page.status ?? 'draft'}</strong>
        </span>
        <Link to={`/pages/${slug}`} className="font-semibold underline">
          Upraviť
        </Link>
      </div>
      <PageRenderer page={page} />
    </div>
  );
};

export default PreviewPage;
