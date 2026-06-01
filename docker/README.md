# Local Piwigo dev environment

```bash
docker compose -f docker/compose.yaml up -d
```

Open http://localhost:8080 and run the installer once:
- Database host: `db`, name `piwigo`, user `piwigo`, password `piwigo`.
- Create an admin account (remember the credentials).

Then in the Piwigo admin:
- Plugins → activate **Modern Formats**.
- (The plugin dir is mounted read-only; for a writable dev loop, drop `:ro` in compose.yaml.)

Run the integration test (after install + activation):

```bash
PWG_URL=http://localhost:8080 PWG_USER=admin PWG_PASS=yourpass \
  bash tests/Integration/upload_convert.sh
```
