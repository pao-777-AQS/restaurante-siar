<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetallePedido extends Model
{
    protected $table = 'detalle_pedidos';
    protected $primaryKey = 'id_detalle_pedido';
    protected $guarded = ['id_detalle_pedido'];
    public function adiciones(): HasMany { return $this->hasMany(DetallePedidoAdicion::class, 'id_detalle_pedido', 'id_detalle_pedido'); }
}
