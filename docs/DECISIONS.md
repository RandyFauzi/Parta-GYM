# Decisions

- Explicit user Laravel request overrides original Next/Nest/Prisma preference.
- Preserve existing Laravel 13/PHP 8.3 project, Vite/Tailwind dependencies and integer user IDs. Domain records UUID.
- Blade/session web app minimizes client/server duplication and protects secrets; JSON REST API deferred.
- Single fixed role per account sufficient for current three roles; no roles join table until multi-role requirement exists.
- SQLite local only, PostgreSQL recommended for production row locks. Existing local data not reset. Demo sample data added only after verifying zero users and uses explicit Demo labels/generated credentials.
- Single plan per invoice stored as terms snapshot; no floating arithmetic or historical price rewrites.
- All valid queued access periods serialize at member level, including plan changes. Financial settlement full only.
- No new UI/gateway/QR dependency; production external integrations remain explicit gaps. Laravel Boost installed per repository instruction.
- UI guidance selected for business dashboard; user palette and minimalist direction override unrelated marketing/glass recommendations. Existing self-hosted Instrument Sans chosen.
- This directory is not a Git repository. No branch/commit/PR created; formatter run over project files.
