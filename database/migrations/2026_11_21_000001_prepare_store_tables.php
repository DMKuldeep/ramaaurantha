<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table): void {
                $table->string('id', 100)->primary();
                $table->string('code', 24)->unique();
                $table->string('name', 90);
                $table->enum('category', ['urli', 'soy']);
                $table->unsignedInteger('price');
                $table->unsignedInteger('stock_quantity')->default(0);
                $table->json('tags');
                $table->text('image_url');
                $table->timestamps();
                $table->index('category');
            });
        } elseif (! Schema::hasColumn('products', 'stock_quantity')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->unsignedInteger('stock_quantity')->default(0);
            });
        }

        Schema::create('email_otps', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('phone', 24);
            $table->string('address', 500);
            $table->string('landmark', 160)->nullable();
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('pincode', 6);
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('customer_name', 120);
            $table->string('email');
            $table->string('phone', 24);
            $table->string('address', 500);
            $table->string('landmark', 160)->nullable();
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('pincode', 6);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('delivery');
            $table->unsignedBigInteger('total');
            $table->string('payment_method', 20)->default('COD');
            $table->string('status', 30)->default('placed');
            $table->timestamp('inventory_released_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('product_id', 100)->nullable()->index();
            $table->string('product_code', 24);
            $table->string('product_name', 90);
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('email_otps');
    }
};
