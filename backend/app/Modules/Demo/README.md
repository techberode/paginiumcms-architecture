# Demo modul (Iterácia 13)

Izolované MOCK dáta pre testovanie a ukážky. **Nikdy nezapisujú do reálneho obsahu.**

## Aktivácia

```bash
export DEMO_MODE=true
```

## Úložisko

- Demo fixtures: `Data/DemoFixtures.php`
- Budúce demo súbory: `storage/app/demo/` (oddelené od `storage/app/content/`)

## Použitie

`DemoDataProvider` vracia read-only MOCK dáta len keď `DEMO_MODE=true`.
Produkčné API a verejný frontend tieto dáta nepoužívajú.
