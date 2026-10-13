# Releasing a new shop version

Checklist for cutting a tenant release (e.g. `3.21.0`). Tenants run
versioned zips, never master — nothing on master reaches a shop until a
release ships it.

## 1. Freeze and bump

1. Merge everything the release should contain (all green on master).
2. Bump the version **before tagging** — two files travel together:
   - `confidential/.meta`: `VERSION=x.y.z`
   - `tests/Unit/Support/MetaTest.php`: the `version_is_ok` expectation
     (it asserts the literal string; CI fails otherwise).
   - Open a PR for the bump, merge it. Tagging first ships zips
     stamped with the old version (cosmetic only — nothing gates on it —
     but avoid it).

## 2. Publish the release

1. Create and publish a GitHub Release: tag `x.y.z`, title `x.y.z`,
   notes in Polish following the `3.20.0` format (`### Lista zmian`
   numbered list + `### Wsparcie różnych wersji PHP` with per-version
   badge download links pointing at `.../releases/download/x.y.z/build-8.N.zip`).
2. Publish as **pre-release** first; promote (uncheck pre-release) once soaked.
3. Publishing (including pre-release) triggers `release-workflow.yml`, which
   builds `build-8.0.zip` … `build-8.6.zip` (~10 min) and attaches them to
   the release. Verify all 7 assets are present and the run is green before
   announcing.
4. Release-matrix gotchas (all fixed, listed so they stay fixed):
   - the 8.0/8.1 legs must use `composer-v8.0.json` / `composer-v8.1.json`
     (main deps require newer PHP and would ship fatally broken zips despite
     `--ignore-platform-reqs`);
   - the workflow runs only on `published` — draft releases build nothing.

## 3. Roll out to tenants

1. Overlay the zip matching the runtime PHP onto the saas copy (see infra
   runbook for the exact commands): back up `confidential/.env`, `unzip -o`,
   verify the env is byte-identical, clear caches (keep `data/transactions/`).
2. Converge tenant schemas with the playbook (`scripts/migrate-tenants.sh`
   in the infra repo): trial on one orphan tenant, then the fleet,
   then spot-checks (tenant login, license-token check).
3. New provisions from then on run the released code.
