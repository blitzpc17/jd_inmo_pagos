<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorizer_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('modification_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // COBRO, CONTRATO, APARTADO
            $table->string('status')->default('PENDIENTE'); // PENDIENTE, APROBADO, RECHAZADO
            $table->text('justification');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('authorized_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('modification_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modification_request_id')->constrained('modification_requests')->onDelete('cascade');
            $table->bigInteger('record_id'); // polymorphic reference ID (charge ID, contract ID, or reservation ID)
            $table->jsonb('original_data');
            $table->jsonb('new_data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modification_request_items');
        Schema::dropIfExists('modification_requests');
        Schema::dropIfExists('authorizer_users');
    }
};
