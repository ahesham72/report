<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // إضافة الأعمدة الجديدة بعد الأعمدة القديمة مباشرة
            $table->string('po_no')->after('order_no')->nullable();
            $table->date('po_date')->after('order_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['po_no', 'po_date']);
        });
    }
};
