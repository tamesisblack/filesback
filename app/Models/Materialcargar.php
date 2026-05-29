<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materialcargar extends Model
{
    protected $table = "material_cargar";
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_libro',
        'estado',
    ];
}
