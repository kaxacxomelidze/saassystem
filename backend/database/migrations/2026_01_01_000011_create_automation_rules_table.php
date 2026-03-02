<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('enabled')->default(true);
            $table->string('channel')->nullable();
            $table->string('keyword')->nullable();
            $table->string('set_priority')->nullable();
            $table->string('set_status')->nullable();
            $table->foreignId('assign_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('add_tag')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rules');
    }
};
