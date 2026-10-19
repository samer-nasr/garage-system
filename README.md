# MB Greičio Auto

Laravel 13 + MySQL website for garage services, car rental inquiries, and a new/used parts catalog. Public interface: Lithuanian, English, Russian. Admin interface: English.

## Local development

PHP 8.3+ with PDO MySQL, Composer, and MySQL 8 are required. No Node build is needed: the interface uses Blade, local CSS, and minimal JavaScript.

1. Install dependencies: `composer install`.
2. Copy `.env.example` to `.env`, set your MySQL database and credentials, and run `php artisan key:generate`.
3. Create the named MySQL database. Run `php artisan migrate` and `php artisan storage:link`.
4. Create an admin: `php artisan shop:admin your@email.com`. Enter a password at the hidden prompt.
5. Optionally add clearly labeled demonstration entries: `php artisan db:seed --class=DemoCatalogSeeder`. Normal database seeding does not add demo inventory.
6. Run `php artisan serve --host=127.0.0.1 --port=8000` and open `http://127.0.0.1:8000`. Admin: `/admin/login`.

On this machine PHP is at `C:\wamp64\bin\php\php8.3.28\php.exe`. A project-local Composer download is under `work/composer.phar`.

The configured preview uses a separate project-local MySQL 8.4 instance on `127.0.0.1:3307`. Its data is in ignored `work/mysql-data`; application credentials are in `.env`. Run `powershell -ExecutionPolicy Bypass -File start-local.ps1` to restart the local database and website. This does not start or change Wamp services. Preview administrator credentials are in ignored `work/preview-login.json`; use `shop:admin` to choose your own credentials.

## Business flows

- Garage and rental bookings use telephone contact. No online car reservation or payment processing.
- Parts basket is stored in the session. Delivery is Vilnius only; its cost is not included in the parts subtotal.
- Submitting saves a request and immutable item/price snapshots. No stock is deducted until admin confirmation.
- Confirmed/completed status deducts stock once in a transaction with row locks. Cancellation restores stock once.
- Admin manually contacts customers; status changes do not send email or SMS. Requests are visible in the dashboard.
- Inventory supports manual entry, LT/EN/RU text with Lithuanian fallback, eight photos, stock, OEM/part numbers, compatibility notes, category and make filters. Images are stored on the public storage disk.
- All sample records are marked DEMO. Replace them with actual stock before launch; hide sample entries from the dashboard.

## Checks

`php artisan test` runs feature tests using an isolated SQLite in-memory database. `php artisan view:cache` checks Blade compilation. MySQL migrations and a request/stock transaction should also be verified against the local database.

## Before production

Point the web root to `public/`. Set production environment, `APP_DEBUG=false`, HTTPS URL, secure session cookies, and database credentials. Configure backups of MySQL and `storage/app/public`. Configure trusted proxies for the hosting setup. Do not expose `work/`, `.env`, or the project root.

Confirm the exact address spelling, logo, prices, service offering, working hours, rental terms, delivery coverage, translations, and privacy retention/contact details with the owner. Current privacy text is an operational draft; retention details have not been supplied. The logo is the user's third reference image. No hours or unverified service prices are invented.

Future enhancements: structured multi-vehicle compatibility, model/year filters, faster part duplication, customer email notifications, order exports, and multilingual admin. These are not implemented in this first version.
