# Rama Aurantha · Laravel storefront

The storefront is now a Laravel 12 application for PHP 8.2+. It keeps the existing MySQL `products` table and catalogue, and adds Laravel customer accounts, saved addresses, COD orders, order items, OTPs, and admin access. Existing `payment_orders` and `admin_users` tables are left untouched; new orders and admins use Laravel's `orders` and `users` tables. Legacy orders and co-admin credentials are not imported into the new admin panel.

## Run locally with Herd

1. Open this repository as a Herd site. From the project folder, you can run `herd link rama`; Herd should serve Laravel's `public/` directory at `http://rama.test`.
2. The local `.env` is private and ignored by Git. If it does not exist, create it and generate an application key:

   ```sh
   cp .env.example .env
   php artisan key:generate
   ```

   Set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` to the same MySQL database used by the existing store. The example database name is `rama`. The old private `config.php` is no longer used. Do not commit `.env` or paste credentials into chat.
3. Back up the database, then run Laravel's additive migrations and create the public storage links:

   ```sh
   php artisan migrate
   php artisan storage:link
   php artisan db:seed
   ```

   The migration reuses existing products and adds `stock_quantity` only if it is missing. It does not drop the catalogue or old payment tables. The seed command adds the original sample catalogue only when the products table is empty. Check product inventory in admin before accepting orders.
4. Configure SMTP in `.env` (`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`) to send sign-in codes and customer emails. Set `STORE_ADMIN_EMAIL` to receive new-order notices. For local-only OTP testing, `MAIL_MAILER=log` writes email content to `storage/logs/laravel.log` instead of sending it.
5. Create the first owner account. The command prompts for a hidden password of at least 12 characters:

   ```sh
   php artisan store:admin owner@example.com --name="Store Owner"
   ```

Visit `/` for the store, `/login` for customer email-code sign-in, and `/admin/login` for the admin panel. The owner can add more administrators from the dashboard. Customers can keep a bag, save addresses, place COD orders, and review their order history.

Order confirmation emails are queued. Run this in another terminal while testing:

```sh
php artisan queue:work
```

Online payments are intentionally not enabled. Razorpay can be integrated later without changing the product catalogue.
