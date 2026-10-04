<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('deal_id')->nullable()->constrained('deals')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('number', 30);
            $table->date('date');
            $table->date('valid_until');
            // draft|sent|accepted|expired|cancelled
            $table->string('status', 20)->default('draft');
            $table->decimal('subtotal', 14, 4)->default(0);
            $table->decimal('discount_total', 14, 4)->default(0);
            $table->decimal('tax', 14, 4)->default(0);
            $table->decimal('total', 14, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_price', 14, 4);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('subtotal', 14, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('sale_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('number', 30);
            $table->date('date');
            // draft|confirmed|partial|fulfilled|cancelled
            $table->string('status', 20)->default('draft');
            $table->decimal('subtotal', 14, 4)->default(0);
            $table->decimal('discount_total', 14, 4)->default(0);
            $table->decimal('tax', 14, 4)->default(0);
            $table->decimal('total', 14, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });

        Schema::create('sale_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_price', 14, 4);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('subtotal', 14, 4)->default(0);
            $table->decimal('delivered_qty', 12, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('sale_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('sale_order_id')->nullable()->constrained('sale_orders')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('number', 30);
            $table->date('date');
            $table->date('due_date');
            // invoice|return
            $table->string('type', 20)->default('invoice');
            // draft|posted|partially_paid|paid|cancelled
            $table->string('status', 20)->default('draft');
            $table->decimal('subtotal', 14, 4)->default(0);
            $table->decimal('discount_total', 14, 4)->default(0);
            $table->decimal('tax', 14, 4)->default(0);
            $table->decimal('total', 14, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });

        Schema::create('sale_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_price', 14, 4);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('subtotal', 14, 4)->default(0);
            // avg_cost capturado al momento del exit (inmutable, COGS)
            $table->decimal('cost_at_time', 14, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('accounts_receivable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('sale_invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 4);
            $table->decimal('balance', 14, 4);
            $table->date('due_date');
            // open|partially_paid|paid|cancelled
            $table->string('status', 20)->default('open');
            $table->timestamps();
            $table->index(['company_id', 'status']);
            $table->index(['due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_receivable');
        Schema::dropIfExists('sale_invoice_items');
        Schema::dropIfExists('sale_invoices');
        Schema::dropIfExists('sale_order_items');
        Schema::dropIfExists('sale_orders');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};
