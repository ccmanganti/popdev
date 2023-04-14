<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('created_by')->nullable();
            $table->string('contact')->nullable();
            
            // If LGU
            $table->string('province')->nullable();
            $table->string('city_or_municipality')->nullable();
            $table->string('brgy_count')->nullable();
            $table->string('lot_area')->nullable();
            
            // If Barangay
            $table->string('barangay')->nullable();
            $table->string('purok_count')->nullable();
            // includes lot area
            
            // If Enumerator
            $table->string('household_quota')->nullable();
            // includes barangay
            // includes province
                        
            
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
