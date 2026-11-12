# SNVP – PHP SSR mini e-commerce with KHQR

## Local setup (XAMPP)
1. Put this folder under `C:\xampp\htdocs`.
2. Start Apache and MySQL. This project uses the `room_booking` database by default.
3. Open `http://localhost:8080/shop%20(3)/shop/` if your Apache uses port 8080, or update the URL for your folder.
4. Local settings are in the ignored `config.local.php`, so they are not published with the source.

On first run, tables `shop_products`, `shop_orders`,
`shop_order_items` are created automatically, along with 22 sample products
and locally stored product illustrations. Missing sample products are added
to existing catalogs without overwriting products you have edited.
Enable PHP's `curl` extension for Bakong payment checks.

## Customer accounts
http://localhost/room_booking/shop/register.php
http://localhost/room_booking/shop/login.php

## Order approval
New customer orders wait for admin approval. Open the order under Admin → Orders and approve it to create its KHQR payment; the customer can then pay from the order page.

## Admin
For local development, set `admin_user`, `admin_email`, and `admin_pass_hash` in your ignored `config.local.php`.
Production must set `SHOP_ADMIN_USER`, `SHOP_ADMIN_EMAIL`, and `SHOP_ADMIN_PASS_HASH` as environment variables. Generate a password hash with `php -r "echo password_hash('your-password', PASSWORD_DEFAULT);"`; never commit production credentials.

## Vercel deployment
Vercel does not officially support PHP. This project uses the community `vercel-php@0.9.0` runtime. Connect the repository to Vercel and set these project environment variables:

- `SHOP_DB_DRIVER=mysql` (or `DB_DRIVER`), plus `SHOP_DB_HOST`/`DB_HOST`, `SHOP_DB_PORT`/`DB_PORT`, `SHOP_DB_NAME`/`DB_NAME`, `SHOP_DB_USER`/`DB_USER`, and `SHOP_DB_PASS`/`DB_PASS` for a persistent managed MySQL database (use Railway's public TCP host and port)
- `SHOP_ADMIN_USER`/`ADMIN_USER`, `SHOP_ADMIN_EMAIL`/`ADMIN_EMAIL`, and `SHOP_ADMIN_PASS_HASH`/`ADMIN_PASS_HASH`
- `BAKONG_ACCOUNT_ID`, `BAKONG_MERCHANT_NAME`, `BAKONG_MERCHANT_CITY`, and optionally `BAKONG_TOKEN`

Do not use the local SQLite database in `data/shop.db` for deployment. Serverless filesystems are not persistent; product image uploads also need an external object-storage service before using that feature in production.
The app also refuses to use local `localhost`/`root` MySQL defaults on Vercel and displays a safe unavailable page if production database settings are missing. Configure `SHOP_DB_HOST`, `SHOP_DB_NAME`, `SHOP_DB_USER`, and `SHOP_DB_PASS`; set `SHOP_DB_PORT` for the provider's public TCP port (default `3306`).

## KHQR
- The QR is generated in `khqr.php` (no libraries needed). Each order gets a QR with its exact amount and expiry.
- With `bakong_token` set, `pay_status.php` calls Bakong `check_transaction_by_md5` every 3 s.
  When paid -> order marked paid, stock reduced, customer redirected to the confirmation page.
- With an empty token the pay page shows a "simulate payment" button (demo mode).
- The QR picture is drawn in the browser using qrcodejs from cdnjs, so the page needs internet.
