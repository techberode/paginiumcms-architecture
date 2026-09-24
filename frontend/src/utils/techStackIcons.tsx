import React, { type SVGProps } from 'react';
import { Box, Code2, Container, Database, GitBranch, Leaf, Server } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

export const TECH_STACK_ICON_IDS = [
  'php',
  'react',
  'vite',
  'typescript',
  'docker',
  'tailwind',
  'node',
  'mysql',
  'linux',
  'git',
] as const;

export type TechStackIconId = (typeof TECH_STACK_ICON_IDS)[number];

const BRAND_COLORS: Record<string, string> = {
  php: '#777BB4',
  react: '#61DAFB',
  vite: '#646CFF',
  typescript: '#3178C6',
  docker: '#2496ED',
  tailwind: '#06B6D4',
  node: '#339933',
  mysql: '#4479A1',
  linux: '#FCC624',
  git: '#F05032',
};

function BrandSvg({ children, ...props }: SVGProps<SVGSVGElement> & { children: React.ReactNode }) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden {...props}>
      {children}
    </svg>
  );
}

const SVG_ICONS: Partial<Record<TechStackIconId, React.FC<SVGProps<SVGSVGElement>>>> = {
  react: (props) => (
    <BrandSvg {...props}>
      <path d="M12 10.11c1.03 0 1.87.84 1.87 1.89 0 1.04-.84 1.89-1.87 1.89S10.13 13.04 10.13 12c0-1.05.84-1.89 1.87-1.89M7.37 20c.63.38 2.01-.2 3.6-1.7-.52-.45-1.03-.96-1.53-1.46a22.7 22.7 0 0 1-2.56.1c-1.78.06-2.93-.35-3.18-.85-.25-.5.35-1.22 1.62-2.06-.26-.58-.48-1.18-.64-1.8-.38-1.52-.2-2.66.45-2.95.52-.24 1.24.05 2.04.65a22.7 22.7 0 0 1 2.56-.1c.88-.03 1.72.02 2.49.14 1.78-.06 2.93.35 3.18.85.25.5-.35 1.22-1.62 2.06.26.58.48 1.18.64 1.8.38 1.52.2 2.66-.45 2.95-.52.24-1.24-.05-2.04-.65-.77.12-1.61.17-2.49.14a22.7 22.7 0 0 1-2.56.1c-1.59 1.5-2.97 2.08-3.6 1.7-.52-.31-.55-1.12-.1-2.24M12 3.5c.53 0 1.04.07 1.53.19.77-.6 1.52-.89 2.04-.65.65.29.83 1.43.45 2.95a8.9 8.9 0 0 0-.64 1.8c1.27.84 1.87 1.56 1.62 2.06-.25.5-1.4.91-3.18.85a22.7 22.7 0 0 1-2.49-.14c-.8.6-1.52.89-2.04.65-.65-.29-.83-1.43-.45-2.95.16-.62.38-1.22.64-1.8-1.27-.84-1.87-1.56-1.62-2.06.25-.5 1.4-.91 3.18-.85.77.12 1.61.17 2.49.14.49-.12 1-.19 1.53-.19m0-1.5C6.75 2 2 6.47 2 12s4.75 10 10 10 10-4.47 10-10S17.25 2 12 2Z" />
    </BrandSvg>
  ),
  vite: (props) => (
    <BrandSvg {...props}>
      <path d="m8.286 10.578 3.847 6.856 2.404-4.136 3.847-6.856H8.286ZM24 0 14.854 16.485 8.286 10.578 24 0ZM0 0l7.732 13.814L14.3 7.906 0 0Z" />
    </BrandSvg>
  ),
  typescript: (props) => (
    <BrandSvg {...props}>
      <path d="M1.125 0C.502 0 0 .502 0 1.125v21.75C0 23.498.502 24 1.125 24h21.75c.623 0 1.125-.502 1.125-1.125V1.125C24 .502 23.498 0 22.875 0H1.125zm17.363 9.75c.612 0 1.154.037 1.627.111a6.38 6.38 0 0 1 1.306.34v2.458a3.95 3.95 0 0 0-.643-.361 5.093 5.093 0 0 0-.717-.26 5.453 5.453 0 0 0-1.426-.2c-.3 0-.573.028-.819.086a2.1 2.1 0 0 0-.623.242c-.17.104-.3.229-.393.374a.888.888 0 0 0-.14.49c0 .19.053.37.156.53.104.16.252.304.443.431.19.127.413.264.668.411.467.282.866.55 1.2.804.334.255.612.533.833.833.22.3.39.633.512.998.122.365.183.776.183 1.236 0 .657-.125 1.21-.373 1.656a3.033 3.033 0 0 1-1.012 1.085 4.38 4.38 0 0 1-1.487.596c-.566.12-1.163.18-1.79.18a9.916 9.916 0 0 1-1.84-.164 5.544 5.544 0 0 1-1.512-.493v-2.63a5.033 5.033 0 0 0 3.237 1.2c.333 0 .624-.03.872-.09.249-.06.456-.144.623-.25.166-.108.29-.234.373-.38a1.023 1.023 0 0 0-.074-1.089 2.12 2.12 0 0 0-.537-.5 5.597 5.597 0 0 0-.807-.444 27.72 27.72 0 0 0-1.007-.436c-.918-.383-1.602-.852-2.053-1.405-.45-.553-.676-1.222-.676-2.005 0-.614.123-1.141.369-1.582.246-.441.58-.804 1.004-1.089a4.494 4.494 0 0 1 1.47-.629 7.536 7.536 0 0 1 1.77-.201zm-15.113.188h9.563v2.166H9.506v9.646H6.789v-9.646H3.375V9.938z" />
    </BrandSvg>
  ),
};

const LUCIDE_ICONS: Partial<Record<TechStackIconId, LucideIcon>> = {
  php: Code2,
  docker: Container,
  tailwind: Leaf,
  node: Server,
  mysql: Database,
  linux: Server,
  git: GitBranch,
};

export function isTechStackIconId(value: string): value is TechStackIconId {
  return (TECH_STACK_ICON_IDS as readonly string[]).includes(value);
}

export function resolveTechStackIconId(id: string, icon?: string): TechStackIconId | 'generic' {
  const normalized = (icon ?? id).trim().toLowerCase();
  if (isTechStackIconId(normalized)) {
    return normalized;
  }
  return 'generic';
}

export function techStackBrandColor(iconId: string): string {
  return BRAND_COLORS[iconId] ?? 'var(--public-footer-link)';
}

export const TechStackBrandIcon: React.FC<{ iconId: string; className?: string }> = ({
  iconId,
  className = 'h-4 w-4',
}) => {
  const color = techStackBrandColor(iconId);
  if (isTechStackIconId(iconId) && SVG_ICONS[iconId]) {
    const Svg = SVG_ICONS[iconId]!;
    return <Svg className={className} style={{ color }} />;
  }
  if (isTechStackIconId(iconId) && LUCIDE_ICONS[iconId]) {
    const Lucide = LUCIDE_ICONS[iconId]!;
    return <Lucide className={className} style={{ color }} aria-hidden />;
  }
  return <Box className={className} style={{ color }} aria-hidden />;
};
