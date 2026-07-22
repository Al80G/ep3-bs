# Update of the ep-3 Bookingsystem from an existing/older version

## 1. Backup

First and most importantly: Backup your database and entire project directory!


## 2. Clear cache

After every update you should delete all files within the

- `data/cache/`

directory.

If you haven't made any changes to the core files, your configuration, customizations and data should stay intact
after the update.


## Championship module (Vereinsmeisterschaft)

Adds a club championship feature (module `Championship`). For an existing installation:

- Replace the `module/` directory (or add the new `module/Championship/` directory)
- Replace `config/application.php` (adds `'Championship'` to the module list)
- Apply the schema addition to your existing database: run the SQL block starting at
  `--- changes for Championship module (Vereinsmeisterschaft)` at the end of `data/db/ep3-bs.sql`
  against your database (creates the `bs_championships`, `bs_championship_categories`,
  `bs_championship_participants`, `bs_championship_groups`, `bs_championship_group_members`,
  `bs_championship_matches` and `bs_championship_match_sets` tables)

### Admin-assigned partners for doubles categories (added later)

If you already applied the Championship schema above, additionally run:

```sql
ALTER TABLE `bs_championship_categories`
  ADD COLUMN `admin_assigns_partners` tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER `advance_per_group`;
```

### Info text for the championship overview page (added later)

If you already applied the Championship schema above, additionally run:

```sql
ALTER TABLE `bs_championships`
  ADD COLUMN `info_text` varchar(1000) DEFAULT NULL AFTER `datetime_registration_end`;
```


## Update from 1.8.1 to `1.9.0`

- Replace the `module/` directory
- Replace the `src/` directory


## Update from 1.8.0 to `1.8.1`

- Replace the `vendor/` directory


## Update from 1.7.0 to `1.8.0`

🚨 Warnings:

- This version is only compatible with **PHP 8.1** or higher
- Due to extensive code changes for this compatibility, there may appear new (and not yet "issued") bugs.  
  Please test this version thoroughly before deploying in production.  
  Also, please report any PHP 8.1 related bugs in our [GitHub issue section](https://github.com/tkrebs/ep3-bs/issues).
- The bundled *file manager* has been removed in this version, as it was outdated and considered insecure.  
  Until we have implemented a new solution, you may have to upload files the old-fashioned way ((S)FTP or similar).

Update steps:

- Replace the `data/docs/` directory
- Create a `data/mails` directory
- Replace the `module/` directory
  - If you made custom changes to the code, you have to migrate them manually 
- Replace files:
  - `public/index.php`
  - `public/setup.php`
- Delete the directory `public/vendor/filemanager`:  
  This is technically not necessary, but recommended. 
- Paste the new `src/` directory
- Replace the `vendor/` directory
- Technically not necessary, but for consistency reasons:  
  Replace the files in the project directory:
  - `composer.json` 
  - `composer.lock` 
  - `CONTRIBUTE.md` 
  - `INSTALL.md` 
  - `LICENSE` 
  - `README.md` 
  - `UPDATE.md` 
  - `VERSION`
  - Delete files:
    - `INSTALL` (without file extension)
    - `README` (without file extension)


## Update from 1.6.4 to `1.7.0`

Replace the following directories:

- `module/`
- `vendor/`


## Update from 1.6.3 to `1.6.4`

There are no steps necessary when updating from version `1.6.3`.


## Update from 1.6.2 to `1.6.3`

Replace the following directories:

- `data/docs/`
- `data/res/i18n/`

- `module/`

- `public/js/jquery/`

- `vendor/`  
  (alternatively, you may update dependencies via Composer after replacing the `composer.json`)

- All single files in the project root directory


## Update from 1.6 to `1.6.2`

There have been some internal changes to the configuration directory. Replace the following files:

- `config/init.php.dist`
- `config/init.php`  
  (and edit it according to your needs; if it does not yet exist, create it by copying `init.php.dist`)

- `data/docs/*`

- `public/index.php`

- `vendor/*`  
  (alternatively, you may update dependencies via Composer after replacing the `composer.json`)

- All single files in the project root directory


## Update from 1.4 or 1.5 to `1.6`

Replace the following directories and files with the new ones:

- `config/application.php`
- `config/modules.php`
- `config/setup.php`

- `data/res/`  
  (if you have custom translations you ~~can~~ should now place them in `data/res/i18n-custom/`)

- `module/`
- `modulex/`

- `public/js/`
- `public/index.php`


## Update from older versions to `1.4` or `1.5`

Replace the following directories and files with the new ones:

- `data/res/`

- `module/`

- `public/css/`
- `public/docs/`
- `public/imgs/`
- `public/js/`
- `public/misc/`
- `public/vendor/`
- `index.php`

- `vendor/`  
  (alternatively, you may update dependencies via Composer after replacing the `composer.json`)

- All single files in the project root directory
