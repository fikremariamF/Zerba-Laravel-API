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
        Schema::create('foams', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->float('sold', 8, 2)->default(0);
            $table->float('percentage', 5, 2)->default(0);
            $table->unsignedBigInteger('sprint_id'); // Assuming 'string_id' is an unsigned big integer
            $table->foreign('sprint_id')->references('id')->on('sprints'); // Replace 'strings' with the actual table name
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('foams');
    }
};
