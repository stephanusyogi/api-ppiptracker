<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\UUID;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;
    use UUID;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    public $incrementing = false;
    protected $fillable = [
        'nama',
        'email',
        'password',
        'no_hp',
        'tgl_lahir',
        'tgl_diangkat_pegawai',
        'usia_diangkat_tahun',
        'usia_diangkat_bulan',
        'usia_pensiun',
        'tgl_registrasi',
        'layer_ppmp',
        'layer_ppip',
        'layer_personal',
        'terdapat_investasi_pensiun',
        'jumlah_investasi_keuangan',
        'jumlah_investasi_properti',
        'sewa_properti',
        'kenaikan_properti',
        'kenaikan_sewa',
        'rencana_penambahan_saldo_bulan_ini',
        'penambahan_saldo_tentative_personal_keuangan',
        'penambahan_saldo_tentative_personal_properti',
        'saldo_ppip',
        'inactive',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // 'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
