-- Extensions required by Orbita.
-- pg_trgm + unaccent back the global command palette search.
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS unaccent;

-- Separate database for the test suite, on this same server.
-- RefreshDatabase runs migrate:fresh, so the suite must never point at the
-- development database or every `make test` would wipe it.
SELECT 'CREATE DATABASE orbita_test OWNER orbita'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'orbita_test')\gexec

\connect orbita_test
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS unaccent;
