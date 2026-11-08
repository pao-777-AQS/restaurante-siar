<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    protected $table = 'pedidos';
    protected $primaryKey = 'id_pedido';
    protected $guarded = ['id_pedido'];
    protected function casts(): array { return ['estimado_listo_en' => 'datetime', 'subtotal' => 'decimal:2', 'descuento' => 'decimal:2', 'propina' => 'decimal:2', 'total' => 'decimal:2']; }
    public function detalles(): HasMany { return $this->hasMany(DetallePedido::class, 'id_pedido', 'id_pedido'); }
    public function mensajes(): HasMany { return $this->hasMany(MensajePedido::class, 'id_pedido', 'id_pedido'); }
    public function mesa(): BelongsTo { return $this->belongsTo(Mesa::class, 'id_mesa', 'id_mesa'); }
    public function mesero(): BelongsTo { return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario'); }
}
