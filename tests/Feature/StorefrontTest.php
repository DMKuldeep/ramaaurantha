<?php

namespace Tests\Feature;

use App\Mail\LoginOtpMail;
use App\Mail\OrderPlacedMail;
use App\Models\EmailOtp;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_browse_and_add_an_available_product_to_the_bag(): void
    {
        $product = $this->product();

        $this->get(route('shop'))->assertOk()->assertSee($product->name);
        $this->get(route('products.show', $product))->assertOk()->assertSee($product->name);
        $this->post(route('cart.add', $product))
            ->assertRedirect()
            ->assertSessionHas('cart', [$product->id => 1]);
        $this->get(route('cart.index'))->assertOk()->assertSee($product->name);
    }

    public function test_customer_can_sign_in_with_a_single_use_email_code(): void
    {
        Mail::fake();
        config(['mail.default' => 'smtp']);
        $this->post(route('login.otp.send'), [
            'name' => 'Asha Customer',
            'email' => 'asha@example.com',
        ])->assertRedirect()->assertSessionHas('otp_sent');
        Mail::assertSent(LoginOtpMail::class, 1);

        $otp = EmailOtp::query()->where('email', 'asha@example.com')->firstOrFail();
        $mail = Mail::sent(LoginOtpMail::class)->first();
        $this->assertNotNull($mail);
        $this->assertTrue(Hash::check($mail->code, $otp->code_hash));
        $this->assertStringContainsString($mail->code, $mail->render());

        $this->post(route('logout'));
        $this->withSession(['url.intended' => route('account')])->post(route('login.otp.verify'), [
            'email' => 'asha@example.com',
            'code' => $mail->code,
        ])->assertRedirect(route('account'));
        $this->assertAuthenticatedAs(User::query()->where('email', 'asha@example.com')->firstOrFail());
        $this->get(route('account'))->assertOk()->assertSee('Asha Customer');
        $this->assertNotNull($otp->fresh()->used_at);

        $this->post(route('logout'));
        $this->post(route('login.otp.verify'), [
            'email' => 'asha@example.com',
            'code' => $mail->code,
        ])->assertSessionHasErrors('code');
    }

    public function test_local_mail_mode_explains_that_the_otp_was_not_emailed(): void
    {
        Mail::fake();
        config([
            'app.env' => 'local',
            'mail.default' => 'log',
        ]);
        $loggedCode = null;
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) use (&$loggedCode): bool {
                $loggedCode = $context['otp'] ?? null;

                return $message === 'Local customer sign-in OTP (enter this 6-digit code)'
                    && preg_match('/^\d{6}$/', (string) $loggedCode) === 1;
            });

        $this->post(route('login.otp.send'), [
            'email' => 'asha@example.com',
        ])->assertRedirect()->assertSessionHas(
            'otp_sent',
            'Local email mode is on: no email was sent. Check storage/logs/laravel.log for the sign-in code.',
        );

        $otp = EmailOtp::query()->where('email', 'asha@example.com')->firstOrFail();
        $this->assertMatchesRegularExpression('/^\$2y\$/', $otp->code_hash);
        Mail::assertNothingSent();
    }

    public function test_otp_login_reports_when_smtp_is_not_configured(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '',
        ]);

        $this->post(route('login.otp.send'), [
            'email' => 'asha@example.com',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('email_otps', 0);
    }

    public function test_otp_login_reports_smtp_delivery_failures_without_a_server_error(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->with('asha@example.com')
            ->andThrow(new TransportException('Authentication failed'));

        $this->post(route('login.otp.send'), [
            'email' => 'asha@example.com',
        ])->assertRedirect()->assertSessionHasErrors('email');

        $this->assertDatabaseHas('email_otps', ['email' => 'asha@example.com']);
    }

    public function test_customer_can_place_a_cod_order_and_inventory_is_decremented(): void
    {
        Mail::fake();
        $product = $this->product(['stock_quantity' => 3, 'price' => 1299]);
        $user = $this->customer();
        $this->actingAs($user)->withSession(['cart' => [$product->id => 2]]);
        $this->get(route('checkout'))->assertOk()->assertSee('Cash on delivery');

        $this->post(route('checkout.place'), [
            'name' => $user->name,
            'phone' => '+91 98765 43210',
            'address' => '10 Candle Lane',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'save_address' => '1',
        ])->assertRedirect();

        $order = Order::query()->with('items')->firstOrFail();
        $this->assertSame('COD', $order->payment_method);
        $this->assertSame(2 * 1299, $order->subtotal);
        $this->assertSame(0, $order->delivery);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame(1, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('addresses', ['user_id' => $user->id, 'pincode' => '110001']);
        $this->assertSame([], session('cart', []));
        $this->get(route('orders.show', $order))->assertOk()->assertSee($order->order_number);
        $this->assertStringContainsString($order->order_number, (new OrderPlacedMail($order))->render());
        Mail::assertQueued(OrderPlacedMail::class);

        $admin = User::query()->create([
            'name' => 'Store Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('A-long-test-password-2026'),
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), ['status' => 'cancelled'])
            ->assertRedirect();
        $this->assertSame(3, $product->fresh()->stock_quantity);
    }

    public function test_checkout_rejects_insufficient_inventory_without_creating_an_order(): void
    {
        $product = $this->product(['stock_quantity' => 1]);
        $user = $this->customer();
        $this->actingAs($user)->withSession(['cart' => [$product->id => 2]]);

        $this->post(route('checkout.place'), [
            'name' => $user->name,
            'phone' => '+91 98765 43210',
            'address' => '10 Candle Lane',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
        ])->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, $product->fresh()->stock_quantity);
    }

    public function test_admin_can_manage_products_but_customer_cannot_open_admin(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->actingAs($this->customer())
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $admin = User::query()->create([
            'name' => 'Store Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('A-long-test-password-2026'),
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);
        $this->get(route('admin.login'))->assertOk();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.products.edit', $this->product()))->assertOk();
        $this->post(route('admin.products.store'), [
            'code' => 'U-15',
            'name' => 'New Lotus Candle',
            'category' => 'urli',
            'price' => 1499,
            'stock_quantity' => 4,
            'tags' => 'Floral, Gift-ready',
            'image_url' => 'https://example.com/candle.jpg',
        ])->assertRedirect(route('admin.dashboard'));
        $this->assertDatabaseHas('products', ['code' => 'U-15', 'stock_quantity' => 4]);
    }

    private function product(array $overrides = []): Product
    {
        return Product::query()->create($overrides + [
            'id' => 'test-candle',
            'code' => 'T-01',
            'name' => 'Test Candle',
            'category' => 'urli',
            'price' => 899,
            'stock_quantity' => 5,
            'tags' => ['Floral'],
            'image_url' => 'https://example.com/candle.jpg',
        ]);
    }

    private function customer(): User
    {
        return User::query()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'email_verified_at' => now(),
            'role' => 'customer',
        ]);
    }
}
