# Demo modul (Iterácia 13)

Izolované MOCK dáta pre testovanie a ukážky. **Nikdy nezapisujú do produkčného obsahu** (`storage/app/content/`).

## Aktivácia

```bash
export DEMO_MODE=true
```

## Úložisko

| Cesta | Účel |
|-------|------|
| `storage/app/demo/` | Seed stránky/články (resetovateľné) |
| `Data/DemoFixtures.php` | MOCK komentáre, správy, newsletter |

## API

- `GET /api/admin/demo/status`
- `POST /api/admin/demo/reset` — len **SUPER_ADMIN**, len keď `DEMO_MODE=true`

## Frontend

- Banner v admin shell (`demo.enabled` z `/api/settings/public`)
- `/demo` — DemoManager (stav + reset)

## Použitie

`DemoDataProvider` vracia read-only MOCK dáta len keď `DEMO_MODE=true`.  
`DemoStorageService::reset()` zapisuje len do `storage/app/demo/`.
