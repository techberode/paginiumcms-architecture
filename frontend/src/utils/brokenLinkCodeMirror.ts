import { Facet, RangeSetBuilder, StateField, type EditorState, type Extension } from '@codemirror/state';
import { Decoration, DecorationSet, EditorView } from '@codemirror/view';

export const brokenLinkLinesFacet = Facet.define<number[], number[]>({
  combine: (values) => values[values.length - 1] ?? [],
});

const brokenLinkLineDecoration = Decoration.line({ attributes: { class: 'cm-broken-link-line' } });

function buildBrokenLinkDecorations(state: EditorState, lineNumbers: number[]): DecorationSet {
  const builder = new RangeSetBuilder<Decoration>();
  const unique = [...new Set(lineNumbers)].sort((a, b) => a - b);

  for (const lineNo of unique) {
    if (lineNo < 1 || lineNo > state.doc.lines) {
      continue;
    }
    const line = state.doc.line(lineNo);
    builder.add(line.from, line.from, brokenLinkLineDecoration);
  }

  return builder.finish();
}

const brokenLinkLineField = StateField.define<DecorationSet>({
  create(state) {
    return buildBrokenLinkDecorations(state, state.facet(brokenLinkLinesFacet));
  },
  update(deco, tr) {
    const prev = tr.startState.facet(brokenLinkLinesFacet);
    const next = tr.state.facet(brokenLinkLinesFacet);
    const linesChanged =
      prev.length !== next.length || prev.some((line, index) => line !== next[index]);
    if (tr.docChanged || linesChanged) {
      return buildBrokenLinkDecorations(tr.state, next);
    }
    return deco.map(tr.changes);
  },
  provide: (field) => EditorView.decorations.from(field),
});

export function brokenLinkLineExtension(brokenLines: number[]): Extension {
  return [brokenLinkLinesFacet.of(brokenLines), brokenLinkLineField];
}
