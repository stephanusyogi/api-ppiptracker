<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('nip')->default('');
            $table->date('tgl_lahir')->default(date("Y-m-d"));
            $table->integer('usia_pensiun')->default(0);
            $table->string('jenis_pensiun')->default('');
            $table->string('no_hp')->default('');
            $table->string('saldo_ppip')->default('');
            $table->float('return_cluster1')->default(0);
            $table->float('return_cluster2')->default(0);
            $table->float('return_cluster3')->default(0);
            $table->float('return_cluster4')->default(0);
            $table->float('return_cluster5')->default(0);
            $table->float('return_cluster6')->default(0);
            $table->float('return_cluster7')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
