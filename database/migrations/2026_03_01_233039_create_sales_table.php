<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {

            $table->id();
            $table->foreignId('seller_id')->constrained();
            $table->foreignId('item_id')->constrained();
            $table->integer('pick')->default(0);
            $table->integer('returned')->default(0);
            $table->boolean('red_flag')->default(false);
            $table->date('date');
            $table->integer('custom_price')->default(0);
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
