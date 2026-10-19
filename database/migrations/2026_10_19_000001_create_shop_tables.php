<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->boolean('is_admin')->default(false));
        Schema::create('listings', function (Blueprint $t) {
            $t->id();
            $t->string('type')->index();
            $t->json('title');
            $t->json('description');
            $t->string('category')->nullable()->index();
            $t->string('make')->nullable()->index();
            $t->string('model')->nullable()->index();
            $t->string('compatibility')->nullable();
            $t->string('part_number')->nullable()->index();
            $t->string('oem_number')->nullable()->index();
            $t->string('condition')->default('new')->index();
            $t->decimal('price', 10, 2)->nullable();
            $t->unsignedInteger('stock')->default(1);
            $t->unsignedSmallInteger('year')->nullable();
            $t->string('transmission')->nullable();
            $t->string('fuel')->nullable();
            $t->json('images')->nullable();
            $t->boolean('is_active')->default(true)->index();
            $t->boolean('is_demo')->default(false);
            $t->timestamps();
        });
        Schema::create('order_requests', function (Blueprint $t) {
            $t->id();
            $t->uuid('reference')->unique();
            $t->string('name');
            $t->string('phone');
            $t->string('email')->nullable();
            $t->string('city');
            $t->string('address');
            $t->text('notes')->nullable();
            $t->string('locale', 2);
            $t->string('status')->default('new')->index();
            $t->decimal('subtotal', 10, 2);
            $t->decimal('delivery_quote', 10, 2)->nullable();
            $t->boolean('stock_adjusted')->default(false);
            $t->timestamps();
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_request_id')->constrained()->cascadeOnDelete();
            $t->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title');
            $t->string('part_number')->nullable();
            $t->decimal('unit_price', 10, 2);
            $t->unsignedInteger('quantity');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('order_requests');
        Schema::dropIfExists('listings');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_admin'));
    }
};
