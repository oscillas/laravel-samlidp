<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('service_providers', function (Blueprint $table) {
            $table->id();
            $table->string('destination_url')->unique();
            $table->string('logout_url');
            $table->text('certificate')->nullable();
            $table->string('block_encryption_algorithm');
            $table->string('key_transport_encryption');
            $table->boolean('query_parameters');
            $table->boolean('encrypt_assertion');
            $table->string('binding')->nullable();
            $table->timestamps();
        });

        $stmt = 'ALTER TABLE service_providers ADD CONSTRAINT chk_if_cert_is_required CHECK ';
        $stmt .= '(certificate IS NOT NULL OR encrypt_assertion = false)';
        DB::statement($stmt);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_providers');
    }
};
