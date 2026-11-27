<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anonymous global chat ("Maker lounge"). No user accounts are linked;
        // chat_id is a random browser id and ip_hash a salted hash, both only for moderation.
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20);
            $table->string('body', 280);
            $table->uuid('chat_id')->index();
            $table->string('ip_hash', 64)->index();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('chat_mutes', function (Blueprint $table) {
            $table->id();
            $table->uuid('chat_id')->nullable()->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('reason')->nullable();
            $table->timestamp('until')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_mutes');
        Schema::dropIfExists('chat_messages');
    }
};
