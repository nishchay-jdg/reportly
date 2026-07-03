<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'changes_requested'])->default('pending')->after('allow_guest_comments');
            $table->string('approved_by_name')->nullable()->after('approval_status');
            $table->text('approval_note')->nullable()->after('approved_by_name');
            $table->timestamp('approved_at')->nullable()->after('approval_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'approved_by_name', 'approval_note', 'approved_at']);
        });
    }
};
