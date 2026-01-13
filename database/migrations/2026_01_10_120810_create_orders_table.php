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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('department_name')->nullable(); // كود الإدارة
            $table->string('order_no')->nullable();        // رقم الطلب
            $table->date('order_date')->nullable();        // تاريخ الطلب
            $table->date('approval_date')->nullable();     // تاريخ الموافقه
            $table->string('po_no')->nullable();
            $table->date('po_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
