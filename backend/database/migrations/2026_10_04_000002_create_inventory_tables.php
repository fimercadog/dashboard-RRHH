<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 100);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('abbreviation', 10);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('location')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 60)->nullable();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            // storable = stock real | service = sin stock | consumable = stock sin alerta obligatoria
            $table->string('type', 20)->default('storable');
            $table->decimal('cost_price', 12, 4)->default(0);
            $table->decimal('sale_price', 12, 4)->default(0);
            $table->decimal('min_stock', 12, 4)->nullable();
            // Ganchos contables — null hasta Fase F (Contabilidad)
            $table->string('inventory_account_code', 20)->nullable();
            $table->string('cogs_account_code', 20)->nullable();
            $table->string('sale_account_code', 20)->nullable();
            $table->string('status', 20)->default('active');
            $table->softDeletes();
            $table->timestamps();
        });

        // Desnormalizado: stock actual por producto × bodega para consultas rápidas.
        // La fuente de verdad es stock_movements; este registro se actualiza por StockService.
        Schema::create('product_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->decimal('quantity', 12, 4)->default(0);
            $table->decimal('avg_cost', 12, 4)->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'warehouse_id']);
        });

        // Registro inmutable de cada movimiento de stock.
        // Sin softDeletes: los errores se corrigen con un movimiento inverso, no borrando.
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            // entry|exit|transfer_out|transfer_in|adjustment|opening
            $table->string('type', 20);
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_cost', 12, 4)->nullable();
            $table->decimal('total_cost', 12, 4)->nullable();
            // UUID compartido entre transfer_out y su transfer_in correspondiente
            $table->string('transfer_id', 36)->nullable()->index();
            // Trazabilidad FK-less hacia Compras/Ventas
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            // Ganchos contables — null hasta Fase F
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'warehouse_id']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('product_stock');
        Schema::dropIfExists('products');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('units');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
