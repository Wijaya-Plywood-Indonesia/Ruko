<?php

namespace App\Models;

use App\Models\Role;
use Illuminate\Database\Eloquent\Model;

class ListAkun extends Model
{
    protected $table = 'list_akun';
    protected $guarded = ['id'];
    protected $fillable = [
        'id_pegawai',
        'id_akun',
        'id_toko',
    ];


    public function akun()
    {
        return $this->belongsTo(User::class, 'id_akun');
    }

    public function toko()
    {
        return $this->belongsTo(IdentitasToko::class, 'id_toko');
    }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai');
    }
    public function role()
    {
        return $this->belongsTo(Role::class, 'id_roles');
    }

}
