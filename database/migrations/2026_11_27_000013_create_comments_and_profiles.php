<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['product_id', 'created_at']);
        });

        // Public maker profiles at /@username
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->unique();
            $table->string('bio', 160)->nullable();
            $table->string('website')->nullable();
            $table->string('x_handle', 30)->nullable();
        });

        // Give existing accounts a username.
        $taken = [];
        foreach (DB::table('users')->orderBy('id')->get(['id', 'name']) as $user) {
            $base = Str::of($user->name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '')->limit(20, '')->toString() ?: 'maker';
            $name = $base;
            $i = 2;
            while (in_array($name, $taken, true) || DB::table('users')->where('username', $name)->exists()) {
                $name = $base.$i++;
            }
            $taken[] = $name;
            DB::table('users')->where('id', $user->id)->update(['username' => $name]);
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['username', 'bio', 'website', 'x_handle']));
        Schema::dropIfExists('comments');
    }
};
