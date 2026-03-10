# AGENTS.md - Prdolotoc Project

## Project
- Name: Prďolotoč (prdolotoc)
- Purpose: Simple web "kolotoč" for random selection from a user-provided list, with shareable configurations.

## Stack
- Backend: PHP 8.3+, Symfony (latest LTS), pure JSON API
- Frontend: Next.js (latest) + React + TypeScript, SPA-style UI
- Infra: Docker Compose (PHP-FPM + nginx + Node/Next), Makefile helpers

## High-Level Domains
- SpinConfig: named configuration of options and behaviour (removal after pick, visual mode)
- SpinSession (implicit on frontend): client-side running spin based on a SpinConfig

## Conventions
- Backend API under `/api/...`, JSON only
- Shareable configs via slug in URL (no auth)
- All long-running services log under `logs/` (docker compose or dev servers)

## Golden Examples
- To be populated after first implementation PR.
