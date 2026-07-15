.PHONY: help install test stan cs cs-fix check frontend-install frontend-test frontend-build

help: ## Zobrazí tento zoznam príkazov
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

install: ## Nainštaluje PHP závislosti (composer)
	composer install --no-interaction --prefer-dist

test: ## Spustí PHPUnit testy
	composer test

stan: ## Spustí PHPStan (statická analýza)
	composer stan

cs: ## Skontroluje coding standard (PHPCS)
	composer cs

cs-fix: ## Automaticky opraví coding standard (PHPCBF)
	composer cs:fix

check: stan test ## Spustí statickú analýzu + testy (to čo beží v CI)

frontend-install: ## Nainštaluje frontend závislosti
	cd frontend && npm ci

frontend-test: ## Spustí frontend testy + type-check
	cd frontend && npm run type-check && npm run test

frontend-build: ## Zostaví frontend
	cd frontend && npm run build
