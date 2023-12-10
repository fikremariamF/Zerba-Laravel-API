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
        Schema::create('my_costs', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->double('spent', 8, 2)->default(0);
            $table->unsignedBigInteger('sprint_id'); // Assuming sprint_id is an unsigned big integer
            $table->foreign('sprint_id')->references('id')->on('sprints');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('my_costs');
    }
};
