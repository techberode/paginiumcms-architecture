import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { MailSignaturePanel } from './MailSignaturePanel';
import { renderWithProviders } from '../../test/renderWithProviders';

describe('MailSignaturePanel', () => {
  it('calls save with edited display name', () => {
    const onSave = vi.fn();
    const onChangeFields = vi.fn();
    renderWithProviders(
      <MemoryRouter>
      <MailSignaturePanel
        mailbox="info@paginium.test"
        templates={[{ id: 'classic' }, { id: 'minimal' }]}
        prefs={{ enabled: true, templateId: 'classic', overrides: {} }}
        fields={{
          displayName: 'Desk',
          jobTitle: '',
          phone: '',
          contactEmail: 'info@paginium.test',
          bio: '',
          companyName: 'Paginium',
          website: '',
          avatarUrl: '',
        }}
        previewHtml="<div>ok</div>"
        saving={false}
        onChangePrefs={vi.fn()}
        onChangeFields={onChangeFields}
        onSave={onSave}
        onImportProfile={vi.fn()}
      />
      </MemoryRouter>
    );
    fireEvent.change(screen.getByTestId('mail-signature-field-displayName'), { target: { value: 'Support' } });
    expect(onChangeFields).toHaveBeenCalled();
    fireEvent.click(screen.getByTestId('mail-signature-save'));
    expect(onSave).toHaveBeenCalled();
  });
});
