<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->jsonb('input')->nullable();
            $table->jsonb('output')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_logs');
    }
};
