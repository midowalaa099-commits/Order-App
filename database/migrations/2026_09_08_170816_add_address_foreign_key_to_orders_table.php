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
        $hasAddressForeignKey = collect(Schema::getForeignKeys('orders'))
            ->contains(fn (array $foreignKey): bool => in_array('address_id', $foreignKey['columns'], true));

        if ($hasAddressForeignKey) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('address_id')->references('id')->on('addresses')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasAddressForeignKey = collect(Schema::getForeignKeys('orders'))
            ->contains(fn (array $foreignKey): bool => in_array('address_id', $foreignKey['columns'], true));

        if (! $hasAddressForeignKey) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['address_id']);
        });
    }
};
