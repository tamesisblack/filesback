<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planificacion extends Model
{
    protected $table = "planificacion";
    protected $primaryKey = 'idplanificacion';
    protected $fillable = [
        'nombreplanificacion',
        'descripcionplanificacion',
        'webplanificacion',
        'asignatura_idasignatura',
        'Estado_idEstado',
        'user_created'
    ];
	public $timestamps = true;
}
