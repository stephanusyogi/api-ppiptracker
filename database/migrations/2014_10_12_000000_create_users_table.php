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
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('nip')->unique()->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->integer('usia_pensiun')->nullable();
            $table->string('jenis_pensiun')->nullable();
            $table->string('no_hp')->nullable();
            $table->float('return_cluster1')->nullable();
            $table->float('return_cluster2')->nullable();
            $table->float('return_cluster3')->nullable();
            $table->float('return_cluster4')->nullable();
            $table->float('return_cluster5')->nullable();
            $table->float('return_cluster6')->nullable();
            $table->float('return_cluster7')->nullable();
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
