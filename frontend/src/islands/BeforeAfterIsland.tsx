import React, { useCallback, useMemo, useState } from 'react';
import { resolvePublicMediaUrl } from '../api/media';
import type { IslandProps } from './publicIslandDefinitions';

export const BeforeAfterIsland: React.FC<{ attrs: IslandProps }> = ({ attrs }) => {
  const before = useMemo(() => resolvePublicMediaUrl(attrs.before ?? ''), [attrs.before]);
  const after = useMemo(() => resolvePublicMediaUrl(attrs.after ?? ''), [attrs.after]);
  const labelBefore = attrs.labelBefore ?? 'Before';
  const labelAfter = attrs.labelAfter ?? 'After';
  const [position, setPosition] = useState(50);

  const onInput = useCallback((event: React.ChangeEvent<HTMLInputElement>) => {
    setPosition(Number(event.target.value));
  }, []);

  if (before === '' || after === '') {
    return null;
  }

  return (
    <div className="pg-before-after" data-testid="before-after-island">
      <div className="pg-before-after__frame">
        <img className="pg-before-after__image pg-before-after__image--after" src={after} alt="" loading="lazy" decoding="async" />
        <div className="pg-before-after__before-clip" style={{ width: `${position}%` }} aria-hidden>
          <img
            className="pg-before-after__image pg-before-after__image--before"
            src={before}
            alt=""
            loading="lazy"
            decoding="async"
            style={{ width: position > 0 ? `${(100 / position) * 100}%` : '100%' }}
          />
        </div>
        <div className="pg-before-after__handle" style={{ left: `${position}%` }} aria-hidden />
      </div>
      <label className="pg-before-after__slider-label">
        <span className="sr-only">{labelBefore} / {labelAfter}</span>
        <input
          type="range"
          min={0}
          max={100}
          value={position}
          onChange={onInput}
          className="pg-before-after__slider"
          data-testid="before-after-slider"
        />
      </label>
      <div className="pg-before-after__labels" aria-hidden>
        <span>{labelBefore}</span>
        <span>{labelAfter}</span>
      </div>
    </div>
  );
};
