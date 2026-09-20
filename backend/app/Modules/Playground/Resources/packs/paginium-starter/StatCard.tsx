import React from 'react';

export function StatCard({
  label,
  value,
}: {
  label: string;
  value: string;
}): React.ReactElement {
  return (
    <article
      style={{
        border: '1px solid #c7d2fe',
        borderRadius: 16,
        padding: 16,
        fontFamily: 'system-ui, sans-serif',
        background: '#eef2ff',
      }}
    >
      <p style={{ margin: 0, fontSize: 12, letterSpacing: '0.08em', textTransform: 'uppercase' }}>
        {label}
      </p>
      <p style={{ margin: '8px 0 0', fontSize: 28, fontWeight: 700 }}>{value}</p>
    </article>
  );
}
