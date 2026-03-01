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
        Schema::table('users', function (Blueprint $table) {
            //



              $table->string('phone')->unique()->nullable()->after('email');
            $table->enum('type', ['student', 'instructor', 'admin'])->default('student')->after('password');
            $table->string('avatar')->nullable()->after('type');
            $table->text('bio')->nullable()->after('avatar');
            $table->date('birth_date')->nullable()->after('bio');
            $table->enum('gender', ['male', 'female'])->nullable()->after('birth_date');
            $table->string('country')->nullable()->after('gender');
            $table->string('city')->nullable()->after('country');
            $table->boolean('is_active')->default(true)->after('city');
            $table->string('verification_code')->nullable()->after('email_verified_at');
            $table->timestamp('code_expires_at')->nullable()->after('verification_code');
    $table->softDeletes();
            // Indexes
            $table->index(['email', 'is_active']);
            $table->index('type');
            $table->index('email_verified_at');
        });
    }

   

    /**
     * Reverse the migrations.
     */
 public function down(): void
{
    Schema::table('users', function (Blueprint $table) {

       // Drop indexes
        $table->dropIndex(['email', 'is_active']);
        $table->dropIndex(['type']);
        $table->dropIndex(['email_verified_at']);
        // Drop columns
        $table->dropColumn([
            'phone',
            'type',
            'avatar',
            'bio',
            'birth_date',
            'gender',
            'country',
            'city',
            'is_active',
            'verification_code',
            'code_expires_at',
        
    
     
          ]);
        });
    }
};


