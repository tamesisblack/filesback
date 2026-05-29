<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Juegos extends Model
{
    use HasFactory;
    protected $table = "juegos_administrables";
    protected $primaryKey = 'id';
    protected $fillable = [
        'img_portada',
        'titulo',
        'subtitulo',
        'descripcion',
        'tipo_juego',
    ];
	public $timestamps = true;
}
