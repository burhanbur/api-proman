<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->primary(['task_id', 'user_id']);
            $table->index('task_id', 'idx_task_id');
            $table->index('user_id', 'idx_user_id');
            $table->foreign('task_id')->references('id')->on('tasks');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('assigned_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('task_assignees');
    }
};
