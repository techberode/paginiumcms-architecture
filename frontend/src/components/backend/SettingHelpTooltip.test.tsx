import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { SettingHelpTooltip } from './SettingHelpTooltip';
import { TestI18nProvider } from '../../context/I18nContext';

function renderTooltip(content: string) {
  return render(
    <TestI18nProvider locale="en">
      <SettingHelpTooltip content={content} />
    </TestI18nProvider>
  );
}

describe('SettingHelpTooltip', () => {
  it('toggles detailed help on button click', async () => {
    const user = userEvent.setup();
    renderTooltip('Extended setting explanation.');

    expect(screen.queryByText('Extended setting explanation.')).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: /detailed help/i }));

    expect(screen.getByText('Extended setting explanation.')).toBeInTheDocument();
  });
});
