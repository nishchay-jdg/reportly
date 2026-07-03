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
        Schema::create('project_version_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_version_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->enum('type', ['html', 'css', 'js']);
            $table->longText('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_version_files');
    }
};
