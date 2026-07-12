// frontend/src/App.tsx
import React, { useEffect, useState } from 'react';
import { AuthProvider, useAuth } from './context/AuthContext';
import api from './api/client';

interface Page {
  id: number;
  title: string;
  slug: string;
  content: string;
}

function App() {
  const [pages, setPages] = useState<Page[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetch('http://localhost:8080/api/pages')
    .then(res => res.json())
    .then(data => {
      setPages(data);
      setLoading(false);
    })
    .catch(err => {
      console.error('Chyba načítania:', err);
      setError('Nepodarilo sa načítať obsah');
      setLoading(false);
    });
  }, []);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
      <div className="text-xl text-gray-600">Načítavam obsah...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center min-h-screen">
      <div className="text-xl text-red-600">{error}</div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
    <header className="bg-white shadow-sm border-b">
    <div className="max-w-7xl mx-auto px-4 py-4">
    <h1 className="text-2xl font-bold text-blue-600">PaginiumCMS</h1>
    </div>
    </header>

    <main className="max-w-7xl mx-auto px-4 py-8">
    <div className="grid gap-6 md:grid-cols-2">
    {pages.map(page => (
      <div key={page.id} className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
      <h2 className="text-xl font-semibold text-gray-800 mb-2">{page.title}</h2>
      <div
      className="text-gray-600 prose"
      dangerouslySetInnerHTML={{ __html: page.content }}
      />
      <div className="mt-4 text-sm text-gray-400">
      Slug: /{page.slug}
      </div>
      </div>
    ))}
    </div>
    </main>
    </div>
  );
}

export default App;
