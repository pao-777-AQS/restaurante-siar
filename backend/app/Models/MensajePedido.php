<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MensajePedido extends Model
{
    protected $table = 'mensajes_pedido';
    protected $primaryKey = 'id_mensaje';
    protected $guarded = ['id_mensaje'];
    public function pedido(): BelongsTo { return $this->belongsTo(Pedido::class, 'id_pedido', 'id_pedido'); }
}
