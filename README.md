# Rice Mo’To

Responsive PHP/MySQL food ordering and store operations demo. The customer storefront, checkout, kitchen board, rider view, inventory, owner analytics, catalog tools, and chatbot share one PDO-backed API.

## Run on XAMPP

1. Copy the project folder into `C:\xampp\htdocs\boombites`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel (PHP 8.1+). Enable PHP's `pdo_mysql` and `mbstring` extensions if they are not enabled.
3. Open phpMyAdmin (`http://localhost/phpmyadmin`), select **Import**, and import `schema.sql`. **This development schema drops and recreates its application tables; do not import it over data you need to keep.**
4. The default local connection is MySQL host `127.0.0.1`, database `rice_moto_db`, user `root`, blank password. For other settings, define `RICE_MOTO_DB_HOST`, `RICE_MOTO_DB_NAME`, `RICE_MOTO_DB_USER`, and `RICE_MOTO_DB_PASS` in the PHP process environment.
5. Visit `http://localhost/boombites/`.

The UI loads Bootstrap 5, Tailwind CSS, Leaflet, map tiles, and menu photos from public CDNs, so internet access is required for those assets and address lookup. Delivery distance is computed server-side with the Haversine formula from the map pin; no routing service is needed to price a delivery. Manual delivery orders use the staff-entered estimated distance. The online payment choice only marks a sandbox payment; it does not charge a card or wallet.

## Demo accounts

All four seeded accounts use the password `password`:

| Role | Email |
| --- | --- |
| Customer | `customer@ricemoto.test` |
| Owner | `owner@ricemoto.test` |
| Kitchen staff | `staff@ricemoto.test` |
| Delivery rider | `rider@ricemoto.test` |

Seeded order IDs for tracking: `RM-DEMO-1001` and `RM-DEMO-1002`.

Change demo passwords and configure HTTPS and secure database credentials before any non-local deployment. Customer registration can only create customer accounts; owners can manage staff and rider accounts from the owner dashboard.

## Main files

- `schema.sql` — normalized MySQL schema, sample users/menu/recipes/orders/activity.
- `config.php` — PDO/session setup and shared authentication, CSRF, and activity helpers.
- `api.php` — JSON endpoints for auth, menu, ordering, status, inventory, analytics, chatbot, and owner management.
- `index.php` — responsive public storefront and role dashboards.
- `assets/app.js` / `assets/app.css` — cart, checkout/map, dashboard interactions, and responsive presentation.
