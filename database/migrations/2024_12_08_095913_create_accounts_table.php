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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id(); // Creates an auto-incrementing primary key
            $table->string('code', 5)->unique(); // Code field, unique with a maximum length of 5
            $table->string('name', 90)->unique(); // Name field, unique with a maximum length of 90
            $table->string('status');
            $table->string('type', 90); // Type field, string with a maximum length of 90
            $table->timestamps(); // Automatically adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
