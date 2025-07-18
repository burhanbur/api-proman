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
        Schema::create('task_relation_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., depends_on, related, subtask_of
            $table->string('name'); // e.g., Depends On, Related To, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_relation_types');
    }
};
