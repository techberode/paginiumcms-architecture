// frontend/src/utils/merge3.ts
// === 3-way merge (diff3) – Iterácia 3 ===
// Riadkovo orientovaný trojcestný zlučovací algoritmus nad flat-file obsahom.
//
// Vstup:
//   base   – pôvodne načítaný obsah (spoločný predok)
//   mine   – moje lokálne úpravy
//   theirs – aktuálny obsah na serveri (z 409 konfliktu)
//
// Výstup: zoznam blokov (chunks). Blok je buď 'stable' (jednoznačný výsledok),
// alebo 'conflict' (base/mine/theirs sa rozchádzajú a treba manuálne rozhodnutie).
//
// Princíp: base sa použije ako kotva. Riadky, ktoré ostali nezmenené v `mine` aj
// `theirs`, sú stabilné kotvy. Medzi kotvami sa porovná, kto čo zmenil:
//   - zmenil len mine  → výsledok = mine
//   - zmenil len theirs→ výsledok = theirs
//   - obaja rovnako     → výsledok = mine (== theirs)
//   - obaja inak        → konflikt

export type MergeChunkStable = {
  type: 'stable';
  lines: string[];
};

export type MergeChunkConflict = {
  type: 'conflict';
  base: string[];
  mine: string[];
  theirs: string[];
};

export type MergeChunk = MergeChunkStable | MergeChunkConflict;

export interface MergeResult {
  chunks: MergeChunk[];
  /** true ak žiadny konflikt – dá sa zlúčiť automaticky. */
  clean: boolean;
  conflictCount: number;
}

/** Zarovnanie spoločných riadkov dvoch polí (LCS) ako dvojice indexov. */
interface Anchor {
  o: number; // index v base
  x: number; // index v druhom poli
}

/**
 * Najdlhšia spoločná podpostupnosť – vráti zarovnané indexy spoločných riadkov.
 */
function longestCommonSubsequence(a: string[], b: string[]): Anchor[] {
  const m = a.length;
  const n = b.length;

  // dp[i][j] = dĺžka LCS pre a[i..] a b[j..]
  const dp: number[][] = Array.from({ length: m + 1 }, () => new Array<number>(n + 1).fill(0));
  for (let i = m - 1; i >= 0; i--) {
    for (let j = n - 1; j >= 0; j--) {
      dp[i][j] = a[i] === b[j] ? dp[i + 1][j + 1] + 1 : Math.max(dp[i + 1][j], dp[i][j + 1]);
    }
  }

  const anchors: Anchor[] = [];
  let i = 0;
  let j = 0;
  while (i < m && j < n) {
    if (a[i] === b[j]) {
      anchors.push({ o: i, x: j });
      i++;
      j++;
    } else if (dp[i + 1][j] >= dp[i][j + 1]) {
      i++;
    } else {
      j++;
    }
  }
  return anchors;
}

function arraysEqual(a: string[], b: string[]): boolean {
  if (a.length !== b.length) {
    return false;
  }
  for (let i = 0; i < a.length; i++) {
    if (a[i] !== b[i]) {
      return false;
    }
  }
  return true;
}

function splitLines(text: string): string[] {
  // Prázdny reťazec = žiadne riadky (nie [""]).
  return text.length === 0 ? [] : text.split('\n');
}

/**
 * Vykoná trojcestný merge a vráti bloky.
 */
export function merge3(mine: string, base: string, theirs: string): MergeResult {
  const o = splitLines(base);
  const a = splitLines(mine);
  const b = splitLines(theirs);

  // Kotvy: base index → mine index / theirs index (len spoločné riadky).
  const mapA = new Map<number, number>();
  for (const anchor of longestCommonSubsequence(o, a)) {
    mapA.set(anchor.o, anchor.x);
  }
  const mapB = new Map<number, number>();
  for (const anchor of longestCommonSubsequence(o, b)) {
    mapB.set(anchor.o, anchor.x);
  }

  // Stabilné kotvy = base riadky nezmenené v mine aj theirs.
  const stableAnchors: Array<{ o: number; a: number; b: number }> = [];
  for (let idx = 0; idx < o.length; idx++) {
    if (mapA.has(idx) && mapB.has(idx)) {
      stableAnchors.push({ o: idx, a: mapA.get(idx)!, b: mapB.get(idx)! });
    }
  }

  // Sentinely na začiatok a koniec.
  const anchors = [
    { o: -1, a: -1, b: -1 },
    ...stableAnchors,
    { o: o.length, a: a.length, b: b.length },
  ];

  const chunks: MergeChunk[] = [];
  let conflictCount = 0;

  const pushStable = (lines: string[]): void => {
    if (lines.length === 0) {
      return;
    }
    const last = chunks[chunks.length - 1];
    if (last && last.type === 'stable') {
      last.lines.push(...lines);
    } else {
      chunks.push({ type: 'stable', lines: [...lines] });
    }
  };

  for (let k = 0; k < anchors.length - 1; k++) {
    const prev = anchors[k];
    const cur = anchors[k + 1];

    const gapBase = o.slice(prev.o + 1, cur.o);
    const gapMine = a.slice(prev.a + 1, cur.a);
    const gapTheirs = b.slice(prev.b + 1, cur.b);

    if (gapBase.length > 0 || gapMine.length > 0 || gapTheirs.length > 0) {
      const mineChanged = !arraysEqual(gapBase, gapMine);
      const theirsChanged = !arraysEqual(gapBase, gapTheirs);

      if (!mineChanged && !theirsChanged) {
        pushStable(gapBase);
      } else if (mineChanged && !theirsChanged) {
        pushStable(gapMine);
      } else if (!mineChanged && theirsChanged) {
        pushStable(gapTheirs);
      } else if (arraysEqual(gapMine, gapTheirs)) {
        // Obaja spravili tú istú zmenu.
        pushStable(gapMine);
      } else {
        conflictCount++;
        chunks.push({ type: 'conflict', base: gapBase, mine: gapMine, theirs: gapTheirs });
      }
    }

    // Pridaj kotvový riadok (ak nie je koncový sentinel).
    if (cur.o >= 0 && cur.o < o.length) {
      pushStable([o[cur.o]]);
    }
  }

  return { chunks, clean: conflictCount === 0, conflictCount };
}

/** Voľba riešenia konfliktného bloku. */
export type ConflictChoice = 'mine' | 'theirs' | 'both-mt' | 'both-tm' | 'base';

/**
 * Poskladá výsledný text z blokov. Pre konfliktné bloky použije zvolené riešenie
 * (`choices[i]`), alebo manuálne prepísaný text (`manual[i]`), ak je prítomný.
 */
export function assembleMerged(
  result: MergeResult,
  choices: Record<number, ConflictChoice>,
  manual: Record<number, string> = {}
): string {
  const out: string[] = [];
  let conflictIndex = 0;

  for (const chunk of result.chunks) {
    if (chunk.type === 'stable') {
      out.push(...chunk.lines);
      continue;
    }

    const i = conflictIndex++;
    if (Object.prototype.hasOwnProperty.call(manual, i)) {
      const manualText = manual[i];
      if (manualText.length > 0) {
        out.push(...manualText.split('\n'));
      }
      continue;
    }

    const choice = choices[i] ?? 'mine';
    switch (choice) {
      case 'mine':
        out.push(...chunk.mine);
        break;
      case 'theirs':
        out.push(...chunk.theirs);
        break;
      case 'both-mt':
        out.push(...chunk.mine, ...chunk.theirs);
        break;
      case 'both-tm':
        out.push(...chunk.theirs, ...chunk.mine);
        break;
      case 'base':
        out.push(...chunk.base);
        break;
    }
  }

  return out.join('\n');
}
