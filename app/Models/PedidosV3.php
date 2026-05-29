<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidosV3 extends Model
{
    use HasFactory;
    protected $table = 'pedidosv3';
    protected $primaryKey = 'id_pedido';
}
