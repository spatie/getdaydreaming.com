<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('published_appcasts', function (Blueprint $table) {
            $table->id();
            $table->longText('body')->nullable();
            $table->char('sha256', 64)->nullable();
            $table->unsignedBigInteger('latest_build')->default(0);
            $table->timestamps();
        });

        DB::table('published_appcasts')->insert([
            'id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('published_appcasts');
    }
};
