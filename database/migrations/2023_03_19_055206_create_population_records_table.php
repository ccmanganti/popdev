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
        Schema::create('population_records', function (Blueprint $table) {
            $table->id();
            $table->string('household_number')->nullable();
            $table->string('province')->nullable();
            $table->string('city_or_municipality')->nullable();
            $table->string('barangay')->nullable();
            $table->string('address_1')->nullable();
            $table->string('address_2')->nullable();
            $table->string('name_of_respondent')->nullable();
            $table->string('household_head')->nullable();
            $table->string('household_members_total')->nullable();
            $table->json('individual_record')->nullable();
            $table->string('q25')->nullable();
            $table->string('q26')->nullable();
            $table->string('q27')->nullable();
            $table->string('q28')->nullable();
            $table->string('q29')->nullable();
            $table->string('q30')->nullable();
            $table->string('q31')->nullable();
            $table->string('q32')->nullable();
            $table->string('q33')->nullable();
            $table->string('encoder_name')->nullable();
            $table->string('signature')->nullable();
            $table->string('data_privacy_consent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('population_records');
    }
};
