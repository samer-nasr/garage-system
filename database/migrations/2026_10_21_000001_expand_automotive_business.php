<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table): void {
            $table->unsignedInteger('mileage')->nullable();
            $table->string('vehicle_condition')->nullable();
            $table->string('engine')->nullable();
            $table->string('availability')->default('available')->index();
            $table->softDeletes();
        });
        Schema::create('vehicle_fitments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('make')->index();
            $table->string('model')->index();
            $table->unsignedSmallInteger('year_from');
            $table->unsignedSmallInteger('year_to');
            $table->index(['make', 'model', 'year_from', 'year_to']);
        });
        Schema::create('customer_inquiries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('reference')->unique();
            $table->string('type')->index();
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('locale', 2);
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->string('condition')->nullable();
            $table->decimal('asking_price', 10, 2)->nullable();
            $table->string('oem_number')->nullable();
            $table->text('message');
            $table->json('photos')->nullable();
            $table->string('status')->default('new')->index();
            $table->boolean('notification_sent')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_inquiries');
        Schema::dropIfExists('vehicle_fitments');
        Schema::table('listings', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropColumn(['mileage', 'vehicle_condition', 'engine', 'availability']);
        });
    }
};
