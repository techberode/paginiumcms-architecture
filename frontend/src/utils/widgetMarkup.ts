import type { LucideIcon } from 'lucide-react';
import {
  Activity,
  BarChart2,
  Bookmark,
  Box,
  LayoutGrid,
  List,
  Megaphone,
  Package,
  Quote,
  Sparkles,
  User,
} from 'lucide-react';
import type { WidgetTypeDefinition } from '../api/widgets';

const KPI_ROW_SAMPLE = `[widget type="kpi" title="Visitors" value="12.4k" delta="+8%" tone="primary" /]
[widget type="kpi" title="Pages" value="48" tone="success" /]`;

const WIDGET_ICONS: Record<string, LucideIcon> = {
  kpi: Activity,
  progress: BarChart2,
  brand: Sparkles,
  quote: Quote,
  cta: Megaphone,
  timeline: Bookmark,
  'icon-box': Box,
  profile: User,
  list: List,
  'kpi-row': LayoutGrid,
};

export function widgetTypeIcon(id: string, source?: WidgetTypeDefinition['source']): LucideIcon {
  if (source === 'custom') {
    return Package;
  }
  return WIDGET_ICONS[id] ?? LayoutGrid;
}

export function widgetTypeLabel(
  item: WidgetTypeDefinition,
  translate: (key: string) => string
): string {
  if (item.source === 'custom') {
    return item.label?.trim() || item.id;
  }
  const key = `platform.widgets.types.${item.id}`;
  const translated = translate(key);
  return translated === key ? item.id : translated;
}

export function escapeWidgetAttr(value: string): string {
  return value.replace(/["\n\r]/g, ' ').trim();
}

export function buildWidgetMarkup(
  type: WidgetTypeDefinition,
  values: Record<string, string>,
  inner = ''
): string {
  const parts = [`type="${escapeWidgetAttr(type.id)}"`];
  for (const field of type.fields) {
    const raw = values[field.key] ?? type.defaults[field.key] ?? '';
    parts.push(`${field.key}="${escapeWidgetAttr(raw)}"`);
  }

  const attrs = parts.join(' ');
  if (type.selfClosing) {
    return `[widget ${attrs} /]`;
  }

  const body = inner.trim() === '' && type.id === 'kpi-row' ? KPI_ROW_SAMPLE : inner.trim();
  return `[widget ${attrs}]\n${body}\n[/widget]`;
}

export function sampleInnerFor(type: WidgetTypeDefinition): string {
  if (type.id === 'kpi-row') {
    return KPI_ROW_SAMPLE;
  }

  return '';
}
