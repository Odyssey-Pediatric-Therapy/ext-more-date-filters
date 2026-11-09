# More Date Filters for EspoCRM

Adds date and datetime search filters for complete calendar periods, elapsed or remaining
parts of a period, and week-based cutoffs. Requires EspoCRM 9.1+ and PHP 8.2+.

**[Read the filter guide: behavior, examples, and X values](INFO.md).**

## How the filters work

- **Extension Last/Next X periods** select complete periods and exclude the current one. At 12:22,
  Last X Hours with X=3 selects 09:00 up to 12:00; Next X Hours with X=3 selects 13:00 up to 16:00.
- **Current period (Past)** runs from the period's start up to now. **Current period (Future)**
  runs from now to the next period boundary. An exact match at now belongs to the Future part.
- Added ranges **include their start and exclude their end**: `start <= value < end`.
  An upper boundary of September 10 at midnight does not include September 10.
- The **“excl. Today”** day filters exclude today entirely. Espo's built-in day filters remain
  available and can include today; the guide explains their different boundaries.
- **Weeks follow the application's configured week start** (Sunday by default). “After X Weeks Ago”
  starts after the end of the referenced calendar week, rather than exactly X times seven days ago.
- **X is a whole number.** Most extension filters accept 1–10,000. Five week cutoff filters also
  accept zero; see [X and zero values](INFO.md#x-and-zero-values) for their exact meaning.
- **Date-only fields have no time of day.** For their current-week/month/quarter/year filters,
  Past excludes today and Future includes today. Minute/hour filters apply to timed values;
  all-day Tasks and Meetings are excluded from those sub-day filters.
- Relative filters are recalculated whenever a search runs. Saving “Last Week” does not freeze
  the dates shown when the filter was saved.

For complete examples, timezone/DST rules, and the distinction between filtering a single
field and showing overlapping calendar events, use the [filter guide](INFO.md).

## Development

The remaining sections cover the local development instance, builds, and tests.
For manual test data, use [the seeding guide](tests/README-seeding.md) (`npm run seed` / `npm run empty`).

## Configuration

Create `config.json` file in the root directory. You can copy `config-default.json` and rename it to `config.json`.

When reading, this config will be merged with `config-default.json`. You can override default parameters in the created config.

Parameters:

* espocrm.repository – from what repository to fetch EspoCRM;
* espocrm.branch – what branch to fetch (`stable` is set by default); you can specify version number instead (e.g. `9.1.0`);
* database - credentials of the dev database;
* install.siteUrl – site url of the dev instance;
* install.defaultOwner – a webserver owner (important to be set right);
* install.defaultGroup – a webserver group (important to be set right).


## Config for EspoCRM instance

You can override EspoCRM config. Create `config.php` in the root directory of the repository. This file will be applied after EspoCRM installation (when building).

Example:

```php
<?php
return [
    'useCacheInDeveloperMode' => true,
];
```

## Building

After building, EspoCRM instance with installed extension will be available at `site` directory. You will be able to access it with credentials:

* Username: admin
* Password: 1

### Preparation

1. You need to have *node*, *npm*, *composer* installed.
2. Run `npm install`.
3. Create a database. Note that without the created database instance building will fail. The database name is set in the config file. You can change it.

### Full EspoCRM instance building

It will download EspoCRM (from the repository specified in the config), then build and install it. Then it will install the extension.

Command:

```
npm run all
```

Note: It will remove a previously installed EspoCRM instance, but keep the database intact.

Note: If an error occurred, check `site/data/logs/` for details. It's often a database is not created.

### Copying extension files to EspoCRM instance

You need to run this command every time you make changes in `src` directory, and you want to try these changes on Espo instance.

Command:

```
npm run sync
```

To avoid running this command manually, use a file watcher in your IDE. The configuration for PhpStorm is included in this repository. See below about the file watcher.

### Running after-install script

AfterInstall.php will be applied for EspoCRM instance.

Command:

```
node build --after-install
```

### Extension package building

Command:

```
npm run extension
```

The package will be created in `build` directory.

Note: The version number is taken from `package.json`.

### Installing addition extensions

If your extension requires other extensions, there is a way to install them automatically while building the instance.

Necessary steps:

1. Add the current EspoCRM version to the `config.php`:

```php
<?php
return [
    'version' => '9.0.0',
];

```

2. Create the `extensions` directory in the root directory of your repository.
3. Put needed extensions (e.g. `my-extension-1.0.0.zip`) in this directory.

Extensions will be installed automatically after running the command `node build --all` or `node build --install`.

## Development workflow

1. Do development in `src` dir.
2. Run `npm run sync`.
3. Test changes in EspoCRM instance at `site` dir.

## Using entity manager to create entities

You can block out new entity types right in Espo (using Entity Manager) and then copy generated custom files (`site/custom` dir) to the repository (`src` dir) using `copy-custom.js` script.

1. Create entity types, fields, layouts, relationships in Espo (it should be available in `site` dir after building).
2. Run `node copy-custom.js`. It will copy all files from `site/custom` to `src/files/custom/Espo/Modules/{ModuleName}` and apply needed modifications to files.
3. Remove files from `site/custom`.
4. Run `npm run sync`. It will copy files from the repository to Espo build (`site/custom//Espo/Modules/{ModuleName}` dir).
5. Clear cache in Espo.
6. Test in Espo.
7. Commit changes.

You can remove `copy-custom.js` from the repository if you don't plan to use it future.

## Using composer in extension

If your extension requires additional libraries, they can be installed by composer:

1. Create a file `src/files/custom/Espo/Modules/{ModuleName}/composer.json` with your dependencies.
2. Once you run `node build --all` or `node build --composer-install`, composer dependencies will be automatically installed.
3. Create a file `src/files/custom/Espo/Modules/{ModuleName}/Resources/autoload.json`.

Note: The extension build will contain only the `vendor` directory without the `composer.json` file.

The `autoload.json` file defines paths for namespaces:

```json
{
    "psr-4": {
        "LibraryNamespace\\": "custom/Espo/Modules/{ModuleName}/vendor/<vendor-name>/<library-name>/path/to/src"
    }
}
```

## Versioning

The version number is stored in `package.json` and `package-lock.json`.

Bumping version:

```
npm version patch
npm version minor
npm version major
```

## Tests

To prepare the Espo instance:

```
npm run prepare-test
```

Fetches the instance and runs composer install. To be used for unit tests and static analysis in CI environment. Takes less time than the full installation.


### Unit

Prepare the Espo instance and its Composer dependencies with `npm run prepare-test` if needed.

Run the PHP regression suite:

```
npm run unit-tests
```

The test bootstrap loads extension classes directly from `src`, so a stale copy in `site`
does not affect the results. Tests cover range boundaries, converter validation, metadata,
and Espo's factory and datetime transformation pipeline without connecting a database.

Run the frontend regression suite:

```
npm run frontend-tests
```

This exercises the extension views with the local Espo date and datetime view implementations.

### Integration

You need to build a test instance first:

1. `npm run sync`
2. `(cd site; grunt test)`

You need to create a config file `tests/integration/config.php`:

```php
<?php

return [
    'database' => [
        'driver' => 'pdo_mysql',
        'host' => 'localhost',
        'charset' => 'utf8mb4',
        'dbname' => 'TEST_DB_NAME',
        'user' => 'YOUR_DB_USER',
        'password' => 'YOUR_DB_PASSWORD',
    ],
];
```

Command to run integration tests:

```
(npm run sync; cd site; vendor/bin/phpunit tests/integration/Espo/Modules/MoreDateFilters)
```

or

```
npm run integration-tests
```

Note that integration tests needs the full Espo installation.

### Static analysis

Command to run:

```
npm run sa
```

If your extension contains additional PHP packages, you also need to add `site/custom/Espo/Modules/MoreDateFilters/vendor` to the *scanDirectories* section in *phpstan.neon* config.

Note: You can omit *composer-install* command if your extension does not contain PHP packages.

## Configuring IDE

You need to set the following paths to be ignored in your IDE:

* `build`
* `site/build`
* `site/custom/`
* `site/client/custom/`
* `site/tests/unit/Espo/Modules/MoreDateFilters`
* `site/tests/integration/Espo/Modules/MoreDateFilters`

### File watcher

You can set up a file watcher in the IDE to automatically copy and transpile files upon saving.

File watcher parameters for PhpStorm:

* Program: `node`
* Arguments: `build --copy-file --file=$FilePathRelativeToProjectRoot$`
* Working Directory: `$ProjectFileDir$`

Note: The File Watcher configuration for PhpStorm is included in this repository.

## Using ES modules

The initialization script asks whether you want to use ES6 modules. It's recommended to choose "YES".

If you want to switch to ES6 later:

1. Set *bundled* to true in `extension.json`.
2. Set *bundled* and *jsTranspiled* to true in `src/files/custom/Espo/Modules/MoreDateFilters/Resources/module.json`.
3. Add `src/files/custom/Espo/Modules/MoreDateFilters/Resources/metadata/app/client.json`
    ```json
    {
        "scriptList": [
            "__APPEND__",
            "client/custom/modules/more-date-filters/lib/init.js"
        ]
    }
    ```

## Javascript frontend libraries

Install *rollup*.

In `extension.json`, add a command that will bundle the needed library into an AMD module. Example:

```json
{
    "scripts": [
        "npx rollup node_modules/some-lib/build/esm/index.mjs --format amd --file build/assets/lib/some-lib.js --amd.id some-lib"
    ]
}
```

Add the library module path to `src/files/custom/Espo/Modules/MoreDateFilters/Resources/metadata/app/jsLibs.json`

```json
{
    "some-lib": {
        "path": "client/custom/modules/more-date-filters/lib/some-lib.js"
    }
}
```

When you build, the library module will be automatically included in the needed location.

Note that you may also need to create *rollup.config.js* to set some additional Rollup parameters that are not supported via CLI usage.

## Updating tooling libraries

Update the version number of espo-extension-tools in package.json to the [latest one](https://github.com/espocrm/extension-tools/releases).

Run:

```
npm update espo-extension-tools
npm update espo-frontend-build-tools
```

Or just update everything:

```
npm update
```

## License

Change a license in `LICENSE` file. The current license is intended for scripts of this repository. It's not supposed to be used for code of your extension.
