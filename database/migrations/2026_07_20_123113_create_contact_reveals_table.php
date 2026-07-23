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
        Schema::create('contact_reveals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_type');
            $table->string('revealed_id');
            $table->string('revealed_type');
            $table->date('reveal_date');
            $table->timestamps();

            $table->unique(['user_id', 'user_type', 'revealed_id', 'revealed_type', 'reveal_date'], 'unique_reveals_today');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_reveals');
    }
};
