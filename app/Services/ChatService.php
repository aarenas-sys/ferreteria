<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Branch;
use App\Models\Discount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * ChatService - Chatbot sin IA basado en reglas y consultas a BD
 * 
 * El chatbot funciona 100% con lógica de reglas:
 * - Detecta intención por palabras clave
 * - Extrae nombre de sucursal
 * - Consulta BD directamente
 * - NO usa IA ni APIs externas
 */
class ChatService
{
    /**
     * Palabras clave para detectar intenciones
     */
    private array $palabrasClaveStock = ['stock', 'disponible', 'hay', 'tenemos', 'quedan', 'cantidad', 'tiene'];
    private array $palabrasClavePrice = ['precio', 'cuesta', 'vale', 'costo', 'cuanto'];
    private array $palabrasClavePromo = ['promocion', 'descuento', 'rebaja', 'oferta'];
    private array $palabrasClaveHorario = ['horario', 'hora', 'abierto', 'cierra'];
    private array $palabrasClaveUbicacion = ['ubicacion', 'direccion', 'donde', 'localizacion'];
    private array $palabrasClaveContacto = ['contacto', 'telefono', 'whatsapp', 'llamar', 'llamada', 'email', 'correo'];
    private array $palabrasClaveInfoSucursal = ['sucursal', 'sucursales', 'branch', 'branches'];

    /**
     * Palabras a eliminar al extraer nombre del producto
     */
    private array $palabrasAEliminar = [
        'precio', 'hay', 'en', 'la', 'el', 'de', 'sucursal', 'del', 'las', 'los',
        'un', 'una', 'unos', 'unas', 'es', 'son', 'que', 'cuanto', 'cuantos',
        'cuanta', 'cuantas', 'cuesta', 'vale', 'disponible', 'stock', 'tenemos'
    ];

    /**
     * Nombres de sucursales disponibles
     */
    private array $sucursalesConocidas = ['norte', 'sur', 'centro', 'este', 'oeste'];

    /**
     * Procesa el mensaje y devuelve la respuesta (SOLO REGLAS, SIN IA)
     */
    public function procesarMensaje(string $mensaje): string
    {
        // Normalizar entrada
        $mensajeNorm = $this->normalizarTexto($mensaje);

        // Detectar intención basada en palabras clave
        $intencion = $this->detectarIntencion($mensajeNorm);

        // Detectar sucursal mencionada en el mensaje
        $sucursal = $this->detectarSucursal($mensajeNorm);

        // Si no hay sucursal especificada y el usuario está autenticado, usar su sucursal
        if (!$sucursal && Auth::check()) {
            $user = Auth::user();
            if (isset($user->sucursal_id) && $user->sucursal_id) {
                $sucursal = $this->obtenerNombreSucursal($user->sucursal_id);
            }
        }

        // Procesar según la intención
        return match ($intencion) {
            'stock' => $this->consultarStock($mensajeNorm, $sucursal),
            'precio' => $this->consultarPrecio($mensajeNorm, $sucursal),
            'promocion' => $this->consultarPromociones($sucursal),
            'horario' => $this->consultarHorario(),
            'ubicacion' => $this->consultarUbicacion($sucursal),
            'contacto' => $this->consultarContacto($sucursal),
            'info_sucursal' => $this->consultarInfoSucursal($sucursal),
            default => $this->respuestaNoEntendida()
        };
    }

    /**
     * Detecta la intención del mensaje basada en palabras clave
     */
    private function detectarIntencion(string $mensaje): string
    {
        // Detectar promociones PRIMERO (antes de stock) porque "hay" también es palabra clave de stock
        if ($this->coincideAlgunasPalabras($mensaje, $this->palabrasClavePromo)) {
            return 'promocion';
        }

        if ($this->coincideAlgunasPalabras($mensaje, $this->palabrasClaveStock)) {
            return 'stock';
        }

        if ($this->coincideAlgunasPalabras($mensaje, $this->palabrasClavePrice)) {
            return 'precio';
        }

        if ($this->coincideAlgunasPalabras($mensaje, $this->palabrasClaveInfoSucursal)) {
            return 'info_sucursal';
        }

        if ($this->coincideAlgunasPalabras($mensaje, $this->palabrasClaveHorario)) {
            return 'horario';
        }

        if ($this->coincideAlgunasPalabras($mensaje, $this->palabrasClaveUbicacion)) {
            return 'ubicacion';
        }

        if ($this->coincideAlgunasPalabras($mensaje, $this->palabrasClaveContacto)) {
            return 'contacto';
        }

        return 'desconocida';
    }

    /**
     * Verifica si alguna palabra clave está en el mensaje
     */
    private function coincideAlgunasPalabras(string $mensaje, array $palabras): bool
    {
        foreach ($palabras as $palabra) {
            if (str_contains($mensaje, $palabra)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detecta sucursal mencionada en el mensaje
     */
    private function detectarSucursal(string $mensaje): ?string
    {
        foreach ($this->sucursalesConocidas as $sucursal) {
            if (str_contains($mensaje, $sucursal)) {
                return $sucursal;
            }
        }
        return null;
    }

    /**
     * Obtiene el nombre de sucursal por ID
     */
    private function obtenerNombreSucursal(?int $id): ?string
    {
        if (!$id) {
            return null;
        }

        $branch = Branch::find($id);
        return $branch ? strtolower($branch->name) : null;
    }

    /**
     * Extrae el nombre del producto del mensaje
     */
    private function extraerProducto(string $mensaje): ?string
    {
        // Remover palabras clave y palabras comunes
        $palabrasARemover = array_merge(
            $this->palabrasClaveStock,
            $this->palabrasClavePrice,
            $this->palabrasClavePromo,
            $this->palabrasClaveInfoSucursal,
            $this->palabrasAEliminar,
            $this->sucursalesConocidas
        );

        $texto = $mensaje;
        foreach ($palabrasARemover as $palabra) {
            $texto = preg_replace('/\b' . $palabra . '\b/', '', $texto);
        }

        $texto = trim(preg_replace('/\s+/', ' ', $texto));

        return !empty($texto) ? $texto : null;
    }

    /**
     * Normaliza el texto para procesamiento
     */
    private function normalizarTexto(string $texto): string
    {
        // Convertir a minúsculas
        $texto = mb_strtolower($texto, 'UTF-8');

        // Remover acentos
        $texto = $this->removerAcentos($texto);

        // Remover puntuación (mantener espacios)
        $texto = preg_replace('/[^\w\s]/', ' ', $texto);

        // Remover espacios múltiples
        $texto = preg_replace('/\s+/', ' ', $texto);

        return trim($texto);
    }

    /**
     * Remueve acentos de un texto
     */
    private function removerAcentos(string $texto): string
    {
        $acentos = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
        ];
        return str_replace(array_keys($acentos), array_values($acentos), $texto);
    }

    /**
     * Consulta stock de un producto por sucursal
     * MEJORADO: Autocorrección, sugerencias, memoria de conversación, alerta de stock bajo
     */
    private function consultarStock(string $mensaje, ?string $sucursal): string
    {
        $producto = $this->extraerProducto($mensaje);

        if (!$producto) {
            return "¿Cuál es el nombre del producto que buscas? Ejemplo: \"¿Hay cemento?\"";
        }

        // Construir query base
        $query = Producto::whereRaw('LOWER(nombre) LIKE ?', ["%{$producto}%"]);

        // Filtrar por sucursal si se especificó
        $branchId = null;
        if ($sucursal) {
            $branch = Branch::whereRaw('LOWER(name) LIKE ?', ["%{$sucursal}%"])->first();

            if (!$branch) {
                return "❌ No encontré la sucursal \"{$sucursal}\".";
            }

            $query->where('sucursal_id', $branch->id);
            $branchId = $branch->id;
        }

        $productoModelo = $query->first();

        // Si NO encontró el producto, intentar autocorrección y sugerencias
        if (!$productoModelo) {
            $similar = $this->sugerirProductoSimilar($producto);
            if ($similar) {
                return "🤔 No encontré exactamente **{$producto}**.\n\n¿Quisiste decir **{$similar['nombre']}**?\n\n_(Escribe \"sí\" o intenta con otro nombre)_";
            }

            // Si no hay similar, sugerir productos relacionados
            $relacionados = $this->obtenerProductosRelacionados($producto, $branchId);
            if (!$relacionados->isEmpty()) {
                $respuesta = "❌ No tenemos **{$producto}**, pero puedes ver:\n\n";
                foreach ($relacionados as $prod) {
                    $respuesta .= "• **{$prod->nombre}** - \${$prod->precio}\n";
                }
                return $respuesta;
            }

            return "❌ No encontré el producto \"{$producto}\".";
        }

        // Guardar en sesión para memoria de conversación
        Session::put('chat_producto', $productoModelo->nombre);
        Session::put('chat_sucursal', $productoModelo->sucursal_id);

        $nombreSucursal = $productoModelo->sucursal ? $productoModelo->sucursal->name : 'desconocida';
        $stock = $productoModelo->stock;

        if ($stock > 0) {
            // Agregar alerta de stock bajo si es necesario
            $alerta = $stock <= 20 ? " ⚠️ *Stock bajo, se recomienda comprar pronto*" : "";
            return "✅ Tenemos **{$stock} unidades** de **{$productoModelo->nombre}** en la  **{$nombreSucursal}**{$alerta}.";
        } else {
            // Si está agotado, sugerir alternativas
            $alternativas = $this->obtenerProductosRelacionados($productoModelo->nombre, $branchId, 3);
            $respuesta = "❌ Lo sentimos, **{$productoModelo->nombre}** está agotado en la **{$nombreSucursal}**.";
            
            if (!$alternativas->isEmpty()) {
                $respuesta .= "\n\n💡 Alternativas disponibles:\n";
                foreach ($alternativas as $alt) {
                    $respuesta .= "• **{$alt->nombre}** - {$alt->stock} unidades disponibles\n";
                }
            }
            
            return $respuesta;
        }
    }

    /**
     * Consulta precio de un producto por sucursal
     * MEJORADO: Autocorrección, sugerencias, memoria de conversación
     */
    private function consultarPrecio(string $mensaje, ?string $sucursal): string
    {
        $producto = $this->extraerProducto($mensaje);

        if (!$producto) {
            return "¿Cuál es el nombre del producto? Ejemplo: \"¿Cuál es el precio del cemento?\"";
        }

        // Construir query base
        $query = Producto::whereRaw('LOWER(nombre) LIKE ?', ["%{$producto}%"]);

        // Filtrar por sucursal si se especificó
        $branchId = null;
        if ($sucursal) {
            $branch = Branch::whereRaw('LOWER(name) LIKE ?', ["%{$sucursal}%"])->first();

            if (!$branch) {
                return "❌ No encontré la sucursal \"{$sucursal}\".";
            }

            $query->where('sucursal_id', $branch->id);
            $branchId = $branch->id;
        }

        $productoModelo = $query->first();

        // Si NO encontró el producto, intentar autocorrección y sugerencias
        if (!$productoModelo) {
            $similar = $this->sugerirProductoSimilar($producto);
            if ($similar) {
                return "🤔 No encontré exactamente **{$producto}**.\n\n¿Quisiste decir **{$similar['nombre']}**?\n\n_(Escribe \"sí\" o intenta con otro nombre)_";
            }

            // Si no hay similar, sugerir productos relacionados
            $relacionados = $this->obtenerProductosRelacionados($producto, $branchId);
            if (!$relacionados->isEmpty()) {
                $respuesta = "❌ No tenemos **{$producto}**, pero puedes ver:\n\n";
                foreach ($relacionados as $prod) {
                    $respuesta .= "• **{$prod->nombre}** - \${$prod->precio}\n";
                }
                return $respuesta;
            }

            return "❌ No encontré el producto \"{$producto}\".";
        }

        // Guardar en sesión para memoria de conversación
        Session::put('chat_producto', $productoModelo->nombre);
        Session::put('chat_sucursal', $productoModelo->sucursal_id);

        $nombreSucursal = $productoModelo->sucursal ? $productoModelo->sucursal->name : 'desconocida';

        return "💰 El precio de **{$productoModelo->nombre}** es **\${$productoModelo->precio}** en la sucursal **{$nombreSucursal}**.";
    }

    /**
     * Consulta promociones activas por sucursal
     */
    private function consultarPromociones(?string $sucursal): string
    {
        // Usar el scope active() del modelo que ya filtra fechas
        $query = Discount::active();

        // Filtrar por sucursal si se especificó
        if ($sucursal) {
            $branch = Branch::whereRaw('LOWER(name) LIKE ?', ["%{$sucursal}%"])->first();

            if (!$branch) {
                return "❌ No encontré la sucursal \"{$sucursal}\".";
            }

            // Filtrar por sucursal usando la relación
            $query->whereHas('branches', function ($q) use ($branch) {
                $q->where('sucursal_id', $branch->id);
            });
        }

        $promociones = $query->get();

        if ($promociones->isEmpty()) {
            return "📌 No hay promociones activas en este momento.";
        }

        $respuesta = "🎉 **Promociones Activas:**\n\n";
        foreach ($promociones as $promo) {
            $valor = $promo->type === 'percentage'
                ? "{$promo->value}%"
                : "\${$promo->value}";

            $respuesta .= "• **{$promo->name}**: {$valor}\n";

            if ($promo->fecha_fin) {
                $respuesta .= "  *Válido hasta: " . $promo->fecha_fin->format('d/m/Y') . "*\n";
            }
        }

        return $respuesta;
    }

    /**
     * Consulta horario general (fijo para todas las sucursales)
     */
    private function consultarHorario(): string
    {
        return "🕐 **Horario:**\n\nLunes a Viernes: **8:00 AM - 5:00 PM**\nSábado y Domingo: Cerrado";
    }

    /**
     * Consulta ubicación de una sucursal
     */
    private function consultarUbicacion(?string $sucursal): string
    {
        if (!$sucursal) {
            return "¿De cuál sucursal quieres conocer la ubicación? (norte, sur, centro, este, oeste)";
        }

        $branch = Branch::whereRaw('LOWER(name) LIKE ?', ["%{$sucursal}%"])->first();

        if (!$branch) {
            return "❌ No encontré la sucursal \"{$sucursal}\".";
        }

        return "📍 **{$branch->name}**\n\nDirección: {$branch->address}";
    }

    /**
     * Consulta contacto de una sucursal
     */
    private function consultarContacto(?string $sucursal): string
    {
        if (!$sucursal) {
            return "¿De cuál sucursal quieres el contacto? (norte, sur, centro, este, oeste)";
        }

        $branch = Branch::whereRaw('LOWER(name) LIKE ?', ["%{$sucursal}%"])->first();

        if (!$branch) {
            return "❌ No encontré la sucursal \"{$sucursal}\".";
        }

        $respuesta = "📞 **Contacto - {$branch->name}**\n\n";
        $respuesta .= "☎️ Teléfono: {$branch->phone}\n";
        $respuesta .= "📍 Dirección: {$branch->address}";

        return $respuesta;
    }

    /**
     * Consulta información completa de una sucursal
     */
    private function consultarInfoSucursal(?string $sucursal): string
    {
        if (!$sucursal) {
            return "¿De cuál sucursal quieres información? (norte, sur, centro, este, oeste)";
        }

        $branch = Branch::whereRaw('LOWER(name) LIKE ?', ["%{$sucursal}%"])->first();

        if (!$branch) {
            return "❌ No encontré la sucursal \"{$sucursal}\".";
        }

        $respuesta = "🏢 **{$branch->name}**\n\n";
        $respuesta .= "📍 **Dirección:** {$branch->address}\n";
        $respuesta .= "📞 **Teléfono:** {$branch->phone}\n";
        $respuesta .= "🕐 **Horario:** Lunes a Viernes 8:00 AM - 5:00 PM\n";
        $respuesta .= "📅 **Atendemos:** Lunes a Sábado";

        return $respuesta;
    }

    /**
     * Respuesta cuando no entiende el mensaje
     */
    private function respuestaNoEntendida(): string
    {
        return "❓ No entendí tu pregunta. Puedo ayudarte con:\n\n"
            . "📦 **Stock:** \"¿Hay cemento?\" o \"¿Hay martillos en sucursal centro?\"\n"
            . "💰 **Precios:** \"Precio del cemento\" o \"¿Cuánto cuesta en sucursal norte?\"\n"
            . "🎉 **Promociones:** \"¿Hay descuentos?\" o \"Ofertas en sucursal centro\"\n"
            . "🏢 **Sucursal:** \"Información de sucursal centro\"\n"
            . "🕐 **Horario:** \"¿Cuál es tu horario?\"\n"
            . "📞 **Contacto:** \"Teléfono de sucursal norte\"";
    }

    /**
     * MEJORA 1: Busca producto similar usando similar_text() y levenshtein()
     * 
     * Usa dos algoritmos para encontrar el producto más similar:
     * - similar_text(): Compara la similitud porcentual
     * - levenshtein(): Cuenta la diferencia de caracteres
     * 
     * @param string $textoIngresado El texto del usuario
     * @return array|null ['nombre' => 'Producto', 'similitud' => 85] o null
     */
    private function sugerirProductoSimilar(string $textoIngresado): ?array
    {
        // Obtener todos los productos únicos por nombre
        $productosUnicos = Producto::select('nombre')
            ->distinct()
            ->get()
            ->pluck('nombre')
            ->toArray();

        if (empty($productosUnicos)) {
            return null;
        }

        $mejorCoincidencia = null;
        $mejorPuntaje = 0;
        $umbralMinimo = 60; // Porcentaje mínimo de similitud

        foreach ($productosUnicos as $nombreProducto) {
            $nombreNorm = $this->normalizarTexto($nombreProducto);
            
            // Usar similar_text para porcentaje de similitud
            $similitud = 0;
            similar_text($textoIngresado, $nombreNorm, $similitud);
            
            // Si similar_text da bajo resultado, usar levenshtein como alternativa
            if ($similitud < $umbralMinimo) {
                $distancia = levenshtein($textoIngresado, $nombreNorm);
                // Convertir distancia a porcentaje (menos distancia = más similitud)
                $maxLen = max(strlen($textoIngresado), strlen($nombreNorm));
                if ($maxLen > 0) {
                    $similitud = (1 - ($distancia / $maxLen)) * 100;
                }
            }

            // Guardar el mejor resultado
            if ($similitud > $mejorPuntaje && $similitud >= $umbralMinimo) {
                $mejorPuntaje = $similitud;
                $mejorCoincidencia = [
                    'nombre' => $nombreProducto,
                    'similitud' => (int)$similitud
                ];
            }
        }

        return $mejorCoincidencia;
    }

    /**
     * MEJORA 2: Obtiene productos relacionados por categoría o nombre similar
     * 
     * Cuando un producto no existe o está agotado, sugiere alternativas:
     * - Primero por categoría (si existe categoria_id)
     * - Luego por nombre similar
     * - Limitado a 3-5 productos
     * 
     * @param string $nombreProducto Nombre del producto buscado
     * @param int|null $branchId ID de sucursal (opcional)
     * @param int $limite Máximo de sugerencias (default 3)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function obtenerProductosRelacionados(
        string $nombreProducto,
        ?int $branchId = null,
        int $limite = 3
    ) {
        // Normalizar el nombre para búsqueda
        $nombreNorm = $this->normalizarTexto($nombreProducto);

        $query = Producto::query()
            ->where('stock', '>', 0) // Solo productos con stock
            ->limit($limite);

        // Si se especificó sucursal, filtrar por ella
        if ($branchId) {
            $query->where('sucursal_id', $branchId);
        }

        // Intentar encontrar por similitud de nombre
        $query->whereRaw('LOWER(nombre) LIKE ?', ["%{$nombreNorm}%"]);

        $productos = $query->get();

        // Si no hay coincidencias de nombre, buscar por similitud fuzzy
        if ($productos->isEmpty()) {
            $todosLosProductos = Producto::where('stock', '>', 0)
                ->when($branchId, fn($q) => $q->where('sucursal_id', $branchId))
                ->get();

            // Calcular similitud con cada producto
            $conPuntaje = $todosLosProductos->map(function ($prod) use ($nombreNorm) {
                $similitud = 0;
                similar_text($nombreNorm, $this->normalizarTexto($prod->nombre), $similitud);
                return [
                    'producto' => $prod,
                    'similitud' => $similitud
                ];
            })
            ->filter(fn($item) => $item['similitud'] > 40) // Filtrar por mínimo
            ->sortByDesc('similitud')
            ->take($limite)
            ->pluck('producto');

            return $conPuntaje;
        }

        return $productos;
    }

    /**
     * MEJORA 3: Maneja memoria de conversación usando sesión de Laravel
     * 
     * Guarda el contexto de la conversación para entender:
     * - "¿Hay stock?" → Usa el último producto consultado
     * - "¿Y el precio?" → Usa producto y sucursal guardadas
     * - etc.
     * 
     * @param string $mensaje Mensaje del usuario
     * @param string $intencion Intención detectada
     * @return string Mensaje con contexto aplicado o original
     */
    private function manejarMemoriaConversacion(string $mensaje, string $intencion): string
    {
        // Palabras que indican "usar contexto"
        $palabraContexto = ['y', 'el', 'la', 'ese', 'eso', 'ese mismo'];
        $tieneContexto = false;

        foreach ($palabraContexto as $palabra) {
            if (str_contains($mensaje, $palabra)) {
                $tieneContexto = true;
                break;
            }
        }

        if (!$tieneContexto) {
            return $mensaje;
        }

        // Si hay producto guardado en sesión, incluirlo
        $productoBuscado = Session::get('chat_producto');
        if ($productoBuscado && !str_contains($mensaje, $productoBuscado)) {
            $mensaje .= " {$productoBuscado}";
        }

        return $mensaje;
    }


}
