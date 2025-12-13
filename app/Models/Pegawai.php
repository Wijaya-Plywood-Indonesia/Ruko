<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    //
    use HasFactory;

    protected $table = 'pegawais';

    protected $fillable = [
        'nik',
        'nama_lengkap',
        'nama_panggilan',
        'jenis_kelamin',
        'tanggal_lahir',
        'tanggal_masuk',
        'telepon',
        'email',
        'alamat',
        'foto_pegawai',
        'foto_ktp',
        'status',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_masuk' => 'date',
    ];
}
