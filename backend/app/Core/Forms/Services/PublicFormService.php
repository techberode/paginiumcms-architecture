<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Forms\Services;

use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Core\Validation\VisitorEmailGuard;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Modules\Messages\Services\MessageDeskService;
use PaginiumCMS\Support\LogSanitizer;
use InvalidArgumentException;

/**
 * Public form schema + submission (It.101d).
 */
final class PublicFormService
{
    public function __construct(
        private FormDefinitionRepository $forms,
        private Validator $validator,
        private MessageDeskService $desk,
        private VisitorEmailGuard $visitorEmail,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function publicSchema(string $id): array
    {
        $definition = $this->requireEnabled($id);

        return [
            'id' => $definition['id'],
            'title' => $definition['title'],
            'description' => $definition['description'] ?? '',
            'fields' => $this->publicFields($definition),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{message: ContactMessage, appended: bool}
     */
    public function submit(string $id, array $data, string $clientIp): array
    {
        $definition = $this->requireEnabled($id);

        $rules = $this->buildRules($definition);
        try {
            $validated = $this->validator->validate($data, $rules);
        } catch (ValidationException $e) {
            throw $e;
        }

        $name = trim((string) ($validated['name'] ?? ''));
        $emailRaw = trim((string) ($validated['email'] ?? ''));
        if ($name === '' || $emailRaw === '') {
            throw new InvalidArgumentException('Form requires name and email fields.');
        }

        $email = $this->visitorEmail->normalize($emailRaw, 'public-form:' . $id);

        $lines = [];
        foreach ($definition['fields'] as $field) {
            if (!is_array($field)) {
                continue;
            }
            $key = (string) ($field['key'] ?? '');
            if ($key === '' || in_array($key, ['name', 'email'], true)) {
                continue;
            }
            $value = $validated[$key] ?? '';
            if (is_bool($value)) {
                $value = $value ? 'yes' : 'no';
            }
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            $label = trim((string) ($field['label'] ?? $key));
            $lines[] = $label . ': ' . LogSanitizer::value($value);
        }

        $body = implode("\n", $lines);
        if ($body === '') {
            $body = '(no additional fields)';
        }

        $message = new ContactMessage($name, $email, $body);
        $routing = is_array($definition['routing'] ?? null) ? $definition['routing'] : [];
        $subject = trim((string) ($routing['subject'] ?? ''));
        if ($subject !== '') {
            $message->setSubject($subject);
        }
        $channel = trim((string) ($routing['channel'] ?? 'contact'));
        if ($channel !== '') {
            $message->setChannel($channel);
        }
        $priority = strtolower(trim((string) ($routing['priority'] ?? 'normal')));
        if (in_array($priority, ContactMessage::PRIORITIES, true)) {
            $message->setPriority($priority);
        }

        $message->setIp(LogSanitizer::value($clientIp));

        return $this->desk->ingest($message);
    }

    /**
     * @return array<string, mixed>
     */
    private function requireEnabled(string $id): array
    {
        $definition = $this->forms->get($id);
        if ($definition === null) {
            throw new InvalidArgumentException('Form not found.');
        }
        if (!(bool) ($definition['enabled'] ?? false)) {
            throw new InvalidArgumentException('Form is disabled.');
        }

        return $definition;
    }

    /**
     * @param array<string, mixed> $definition
     * @return list<array<string, mixed>>
     */
    private function publicFields(array $definition): array
    {
        $out = [];
        foreach ($definition['fields'] as $field) {
            if (!is_array($field) || ($field['type'] ?? '') === 'hidden') {
                continue;
            }
            $out[] = [
                'key' => $field['key'],
                'type' => $field['type'],
                'label' => $field['label'] ?? '',
                'required' => (bool) ($field['required'] ?? false),
                'placeholder' => $field['placeholder'] ?? '',
                'minLength' => $field['minLength'] ?? null,
                'maxLength' => $field['maxLength'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, list<string>>
     */
    private function buildRules(array $definition): array
    {
        /** @var array<string, list<string>> $rules */
        $rules = ['_hp' => ['string', 'max:200']];
        foreach ($definition['fields'] as $field) {
            if (!is_array($field)) {
                continue;
            }
            $key = (string) ($field['key'] ?? '');
            $type = (string) ($field['type'] ?? 'text');
            if ($key === '' || $type === 'hidden') {
                continue;
            }

            $required = (bool) ($field['required'] ?? false);
            if ($type === 'checkbox') {
                $rules[$key] = [$required ? 'required' : 'nullable', 'bool'];
                continue;
            }

            $presence = $required ? ['required'] : ['nullable'];
            $base = $type === 'email' ? ['email', 'max:255'] : ['string'];
            $fieldRules = array_merge($presence, $base);

            $min = (int) ($field['minLength'] ?? 0);
            $max = (int) ($field['maxLength'] ?? 0);
            if ($min > 0) {
                $fieldRules[] = 'min:' . $min;
            }
            if ($max > 0) {
                $fieldRules[] = 'max:' . $max;
            } elseif ($type === 'textarea') {
                $fieldRules[] = 'max:5000';
            } elseif ($type === 'url') {
                $fieldRules[] = 'max:500';
            } elseif ($type === 'text' || $type === 'tel') {
                $fieldRules[] = 'max:500';
            }

            $rules[$key] = $fieldRules;
        }

        return $rules;
    }
}
