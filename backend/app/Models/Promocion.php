<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Promocion extends Model
{
    protected $table = 'promociones';
    protected $primaryKey = 'id_promocion';
    protected $fillable = ['nombre', 'dia_semana', 'descuento_porcentaje', 'fecha_inicio', 'fecha_fin', 'activa'];
    protected function casts(): array { return ['descuento_porcentaje' => 'decimal:2', 'fecha_inicio' => 'date', 'fecha_fin' => 'date', 'activa' => 'boolean']; }
    public function productos(): BelongsToMany { return $this->belongsToMany(Producto::class, 'promocion_productos', 'id_promocion', 'id_producto'); }
}
