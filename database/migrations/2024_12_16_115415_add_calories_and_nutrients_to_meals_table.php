<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meals', function (Blueprint $table) {
            $table->decimal('weight', 8, 3)->nullable()
                ->after('time');
            $table->decimal('calories', 8, 3)->nullable()
                ->after('weight');
            $table->decimal('carbohydrates', 8, 3)->nullable()
                ->after('calories');
            $table->decimal('protein', 8, 3)->nullable()
                ->after('carbohydrates');
            $table->decimal('fat', 8, 3)->nullable()
                ->after('protein');
            $table->decimal('fiber', 8, 3)->nullable()
                ->after('fat');
            $table->decimal('sugar', 8, 3)->nullable()
                ->after('fiber');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meals', function (Blueprint $table) {
            $table->dropColumn([
                'weight',
                'calories',
                'carbohydrates',
                'protein',
                'fat',
                'fiber',
                'sugar',
            ]);
        });
    }
};
