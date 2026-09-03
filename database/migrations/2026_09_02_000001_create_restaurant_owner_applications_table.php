<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_owner_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('restaurant_owner_applications', function (Blueprint $table) {
                $table->unsignedBigInteger('pending_user_id')
                    ->nullable()
                    ->virtualAs("CASE WHEN status = 'pending' THEN user_id ELSE NULL END");
                $table->unique('pending_user_id', 'restaurant_owner_applications_one_pending_user_unique');
            });
        } else {
            DB::statement(
                "CREATE UNIQUE INDEX restaurant_owner_applications_one_pending_user_unique
                 ON restaurant_owner_applications (user_id)
                 WHERE status = 'pending'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('restaurant_owner_applications', function (Blueprint $table) {
                $table->dropUnique('restaurant_owner_applications_one_pending_user_unique');
                $table->dropColumn('pending_user_id');
            });
        } else {
            DB::statement('DROP INDEX IF EXISTS restaurant_owner_applications_one_pending_user_unique');
        }

        Schema::dropIfExists('restaurant_owner_applications');
    }
};
