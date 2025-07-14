<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('workspace_users', function (Blueprint $table) {
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('workspace_role_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->primary(['workspace_id', 'user_id']);
            $table->index('workspace_id', 'idx_workspace_id');
            $table->index('user_id', 'idx_user_id');
            $table->index('workspace_role_id', 'idx_workspace_role_id');
            $table->foreign('workspace_id')->references('id')->on('workspaces');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('workspace_role_id')->references('id')->on('workspace_roles');
        });
    }

    public function down()
    {
        Schema::dropIfExists('workspace_users');
    }
};
