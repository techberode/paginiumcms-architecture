import React, { useEffect, useMemo, useRef, useState } from 'react';
import { ChevronDown, Search } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { ADMIN_INPUT } from '../../theme/adminUiClasses';
import { filterLucideIconCatalog, resolveNavigationIconComponent } from '../../utils/navigationRich';

interface LucideIconPickerProps {
  value: string;
  onChange: (name: string) => void;
}

export const LucideIconPicker: React.FC<LucideIconPickerProps> = ({ value, onChange }) => {
  const { t } = useI18n();
  const rootRef = useRef<HTMLDivElement>(null);
  const searchRef = useRef<HTMLInputElement>(null);
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');
  const selected = value.trim();
  const SelectedIcon = selected ? resolveNavigationIconComponent(selected) : null;

  const names = useMemo(() => {
    const matches = filterLucideIconCatalog(query);
    if (selected === '' || matches.includes(selected) || query.trim() !== '') {
      return matches;
    }
    return [selected, ...matches];
  }, [query, selected]);

  useEffect(() => {
    if (!open) {
      return;
    }

    searchRef.current?.focus();

    const onPointerDown = (event: MouseEvent) => {
      if (rootRef.current && !rootRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    };
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setOpen(false);
      }
    };

    document.addEventListener('mousedown', onPointerDown);
    document.addEventListener('keydown', onKeyDown);
    return () => {
      document.removeEventListener('mousedown', onPointerDown);
      document.removeEventListener('keydown', onKeyDown);
    };
  }, [open]);

  const pick = (name: string) => {
    onChange(name);
    setQuery('');
    setOpen(false);
  };

  return (
    <div className="space-y-1" data-testid="nav-lucide-picker" ref={rootRef}>
      <label className="form-label text-xs">{t('navigation.fields.iconValueLucide')}</label>
      <div className="relative">
        <button
          type="button"
          className={`${ADMIN_INPUT} flex items-center gap-2 text-left`}
          aria-expanded={open}
          aria-haspopup="listbox"
          data-testid="nav-lucide-toggle"
          onClick={() =>
            setOpen((current) => {
              const next = !current;
              if (next) {
                setQuery('');
              }
              return next;
            })
          }
        >
          {SelectedIcon ? <SelectedIcon className="h-4 w-4 shrink-0 text-admin-primary" aria-hidden /> : null}
          <span className={`flex-1 truncate ${selected ? 'text-admin-text' : 'text-admin-muted'}`}>
            {selected || t('navigation.iconPicker.placeholder')}
          </span>
          <ChevronDown className={`h-4 w-4 shrink-0 text-admin-muted transition-transform ${open ? 'rotate-180' : ''}`} />
        </button>

        {open ? (
          <div
            className="admin-popover absolute left-0 right-0 mt-1 overflow-hidden rounded-lg"
            data-testid="nav-lucide-menu"
          >
            <div className="relative border-b border-admin-border p-2">
              <Search className="pointer-events-none absolute left-5 top-1/2 h-4 w-4 -translate-y-1/2 text-admin-muted" />
              <input
                ref={searchRef}
                className={`${ADMIN_INPUT} pl-9`}
                value={query}
                onChange={(event) => setQuery(event.target.value)}
                placeholder={t('navigation.iconPicker.search')}
                data-testid="nav-lucide-search"
                autoComplete="off"
              />
            </div>
            <div className="max-h-52 overflow-y-auto" role="listbox" aria-label={t('navigation.fields.iconValueLucide')}>
              {names.length === 0 ? (
                <p className="px-3 py-3 text-sm text-admin-muted">{t('navigation.iconPicker.empty')}</p>
              ) : (
                names.map((name) => {
                  const Icon = resolveNavigationIconComponent(name);
                  const active = selected === name;
                  return (
                    <button
                      key={name}
                      type="button"
                      role="option"
                      aria-selected={active}
                      data-testid={`nav-lucide-icon-${name}`}
                      onClick={() => pick(name)}
                      className={`flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm ${
                        active ? 'admin-popover-active font-semibold' : ''
                      }`}
                    >
                      {Icon ? <Icon className="h-4 w-4 shrink-0" aria-hidden /> : null}
                      <span>{name}</span>
                    </button>
                  );
                })
              )}
            </div>
          </div>
        ) : null}
      </div>
    </div>
  );
};

export default LucideIconPicker;
