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
        Schema::create('notification_events', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Unique event code e.g., task_assigned');
            $table->string('name')->comment('Display name of the event');
            $table->text('description')->nullable()->comment('Description of when this event triggers');
            $table->string('category')->nullable()->comment('Category: task, project, workspace, comment, etc');
            $table->boolean('is_active')->default(true)->comment('Whether this event is active');
            $table->timestamps();
            
            $table->index('code');
            $table->index(['category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_events');
    }
};
