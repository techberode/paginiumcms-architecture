import type { SystemUpdateRunResult } from '../api/systemUpdate';

export interface DeployRunInterpretation {
  ok: boolean;
  skipped?: boolean;
  error?: string;
}

export function interpretDeployRunResult(
  data: SystemUpdateRunResult | null | undefined
): DeployRunInterpretation {
  if (!data) {
    return { ok: false, error: 'missing_response' };
  }

  if (data.skipped) {
    return { ok: true, skipped: true };
  }

  const result = data.result;
  if (result && result.success === false) {
    const output =
      result.data && typeof result.data.output === 'string' ? result.data.output.trim() : '';
    const message = typeof result.message === 'string' ? result.message : 'Deploy failed';

    return {
      ok: false,
      error: output !== '' ? `${message}\n${output}` : message,
    };
  }

  return { ok: true };
}
