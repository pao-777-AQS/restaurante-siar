<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdicionProducto extends Model
{
    protected $table = 'adiciones_productos';
    protected $primaryKey = 'id_adicion';
    protected $fillable = ['id_producto', 'nombre', 'precio', 'activa'];
    protected function casts(): array { return ['precio' => 'decimal:2', 'activa' => 'boolean']; }
    public function producto(): BelongsTo { return $this->belongsTo(Producto::class, 'id_producto', 'id_producto'); }
}
