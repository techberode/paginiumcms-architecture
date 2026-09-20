import React from 'react';
import { StatCard } from './StatCard';

export default function App(): React.ReactElement {
  return (
    <main style={{ padding: 24 }}>
      <StatCard label="Published pages" value="12" />
    </main>
  );
}
