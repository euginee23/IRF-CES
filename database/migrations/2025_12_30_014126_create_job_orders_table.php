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
        Schema::create('job_orders', function (Blueprint $table) {
            $table->id();
            $table->string('job_order_number')->unique();
            
            // Customer Information
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone');
            $table->text('customer_address')->nullable();
            
            // Device Information
            $table->string('device_brand');
            $table->string('device_model');
            $table->string('serial_number')->nullable();
            
            // Job Details
            $table->text('issue_description');
            $table->json('issues')->nullable(); // Array of services/issues with type and diagnosis
            $table->json('parts_needed')->nullable(); // Array of parts from inventory with part_id and quantity
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->date('expected_completion_date')->nullable();
            
            // Status and Assignment
            $table->string('status')->default('pending'); // pending, assigned, in_progress, completed, delivered, cancelled
            $table->foreignId('received_by')->constrained('users'); // Counter staff who received
            $table->foreignId('assigned_to')->nullable()->constrained('users'); // Technician assigned
            
            // Completion Details
            $table->text('work_performed')->nullable();
            $table->decimal('final_cost', 10, 2)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_orders');
    }
};
