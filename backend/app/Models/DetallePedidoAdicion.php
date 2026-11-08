<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePedidoAdicion extends Model
{
    protected $table = 'detalle_pedido_adiciones';
    protected $primaryKey = 'id_detalle_adicion';
    protected $guarded = ['id_detalle_adicion'];
}
