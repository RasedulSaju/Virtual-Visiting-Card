# MDB Assets

## mdb-free/  (committed to git)
The free, MIT-licensed MDB UI Kit build, bundled directly in this repo.
This is what the site actually runs on by default — no setup step needed,
no CDN dependency. Safe to commit since it's MIT licensed.

Source: https://www.npmjs.com/package/mdb-ui-kit
See `mdb-free/LICENSE.txt` for the full license text.

To update to a newer version:
```bash
npm pack mdb-ui-kit@<version>
tar -xzf mdb-ui-kit-<version>.tgz
cp package/css/mdb.min.css assets/mdb-free/
cp package/js/mdb.min.js   assets/mdb-free/
```

## mdb-pro/  (gitignored — NOT committed)
Your paid MDB Pro license, if you have one. Upload your own licensed files
here to unlock Pro features (extra components, animations, datatable,
etc.) — see docs/developer.md for the full module list.

This folder is intentionally excluded from git since MDB Pro is a
commercial license tied to your account — it must never be redistributed
in a shared repository.

## Loading Priority
Both layouts check in this order, loading only ONE:
1. mdb-pro/ (if you've uploaded your license)
2. mdb-free/ (bundled, always available)
3. CDN (last-resort fallback — only used if both folders above are empty)
