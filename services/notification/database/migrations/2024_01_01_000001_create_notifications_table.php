<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('subscriber_id');
            $table->string('channel', 16);
            $table->text('message');
            $table->string('priority', 16);
            $table->string('status', 16)->default('queued');
            $table->string('idempotency_key')->nullable();
            $table->string('provider_ref')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['subscriber_id', 'created_at']);
            $table->index('status');
            $table->unique(['idempotency_key', 'subscriber_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
