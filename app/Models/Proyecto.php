<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model
{
    use HasFactory;
    protected $table = "proyectos";
    protected $primaryKey = 'id';
    protected $fillable = [
        'nombre',
        'grupo_usuario',
        'descripcion',
        'archivo_docente',
        'url_docente',
        'archivo_estudiante',
        'url_estudiante',
        'estado',
        'introduccion',
        'tarea',
        'proceso',
        'recurso',
        'evaluacion',
        'creditos',
        'idusuario',
        
    ];

}
