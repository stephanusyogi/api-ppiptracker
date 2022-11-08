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
            $table->string('nip')->unique();
            $table->date('tgl_lahir');
            $table->integer('usia_pensiun');
            $table->string('jenis_pensiun');
            $table->string('no_hp');
            $table->float('return_cluster1');
            $table->float('return_cluster2');
            $table->float('return_cluster3');
            $table->float('return_cluster4');
            $table->float('return_cluster5');
            $table->float('return_cluster6');
            $table->float('return_cluster7');
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
