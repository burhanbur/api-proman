<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->index('owner_id', 'idx_owner_id');
            $table->index('created_by', 'idx_created_by');
            $table->index('updated_by', 'idx_updated_by');
            $table->foreign('owner_id')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('workspaces');
    }
};
