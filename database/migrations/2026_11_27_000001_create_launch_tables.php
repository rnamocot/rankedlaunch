<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('url');
            $table->string('tagline', 120);
            $table->text('description');
            $table->string('logo')->nullable();
            $table->json('screenshots')->nullable();
            $table->string('pricing_type')->default('freemium'); // free, freemium, paid
            $table->string('plan')->default('free');             // free, pro, featured
            $table->string('status')->default('draft');          // draft, awaiting_payment, pending, approved, rejected
            $table->date('launch_date')->nullable()->index();
            $table->date('requested_launch_date')->nullable();
            $table->string('requested_plan')->nullable();
            $table->boolean('is_dofollow')->default(false);
            $table->timestamp('featured_until')->nullable();
            $table->unsignedInteger('votes_count')->default(0);
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'launch_date']);
        });

        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider');
            $table->string('provider_order_id')->nullable()->unique();
            $table->string('plan');
            $table->unsignedInteger('amount'); // cents
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('pending');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('votes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
