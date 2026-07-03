<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('share_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->enum('author_type', ['team', 'guest']);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->text('body');
            $table->float('position_x')->nullable();
            $table->float('position_y')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();

            $table->index(['share_id', 'is_resolved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
