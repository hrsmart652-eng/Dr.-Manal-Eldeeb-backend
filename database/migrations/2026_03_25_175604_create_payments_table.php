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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
                 $table->string('payment_number')->unique(); // PY-XXXXXXXXXX
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Payable (polymorphic - can be enrollment, book purchase, booking)
            $table->morphs('payable');
            
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('fee', 10, 2)->default(0); // Gateway fees
            $table->decimal('net_amount', 10, 2); // Amount after fees
            
            $table->enum('payment_method', ['paypal', 'stripe', 'credit_card', 'bank_transfer', 'cash'])->default('paypal');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending');
            
            // Gateway details
            $table->string('gateway')->nullable(); // paypal, stripe
            $table->string('gateway_payment_id')->nullable(); // ID from payment gateway
            $table->string('gateway_order_id')->nullable();
            $table->text('gateway_response')->nullable(); // Full gateway response
            
            // Card details (if credit card)
            $table->string('card_last4')->nullable();
            $table->string('card_brand')->nullable(); // visa, mastercard, etc
            
            // Payer details
            $table->string('payer_email')->nullable();
            $table->string('payer_name')->nullable();
            
            // Refund details
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->text('refund_reason')->nullable();
            $table->timestamp('refunded_at')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('payment_number');
           
            $table->index('status');
            $table->index('payment_method');
            $table->index('gateway_payment_id');
            // $table->index(['payable_type', 'payable_id']);
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
