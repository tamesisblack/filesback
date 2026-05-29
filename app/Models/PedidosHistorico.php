<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidosHistorico extends Model
{
    use HasFactory;
    protected $table = "pedidos_historico";
    protected $primaryKey = "id";
}
