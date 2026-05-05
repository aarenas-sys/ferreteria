<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supervisor\StoreProductoRequest;
use App\Http\Requests\Supervisor\UpdateProductoRequest;
use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Intervention\Image\ImageManagerStatic as Image;

class ProductoController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = auth()->user()->branch_id;

        $query = Producto::with('categoria')
                        ->where('sucursal_id', $branchId);

        // Filtro por búsqueda
        if ($request->has('search') && !empty($request->search)) {
            $query->search($request->search);
        }

        // Filtro por categoría
        if ($request->has('categoria_id') && !empty($request->categoria_id)) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Filtro por estado de stock
        if ($request->has('estado_stock') && !empty($request->estado_stock)) {
            $query->byEstadoStock($request->estado_stock);
        }

        $productos = $query->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $lowStockCount = Producto::where('sucursal_id', $branchId)
            ->lowStock()
            ->count();

        // Obtener categorías para filtro
        $categorias = Categoria::ordenadas()->get();

        return view('supervisor.productos.index', compact('productos', 'lowStockCount', 'categorias'));
    }

    public function create(): View
    {
        $categorias = Categoria::ordenadas()->get();
        return view('supervisor.productos.create', compact('categorias'));
    }

    public function store(StoreProductoRequest $request): RedirectResponse
    {
        $branchId = auth()->user()->branch_id;

        $data = array_merge($request->validated(), [
            'sucursal_id' => $branchId,
        ]);

        $imagenMensaje = '';
        if ($request->hasFile('imagen')) {
            $data['imagen'] = $this->redimensionarYGuardarImagen($request->file('imagen'), 'productos');
            $imagenMensaje = ' La imagen se ha redimensionado a 800x600px automáticamente.';
        }

        Producto::create($data);

        $branchName = auth()->user()->branch?->name ?? 'la sucursal asociada';

        return redirect()->route('supervisor.productos.index')
            ->with('success', "Producto creado correctamente en {$branchName}.{$imagenMensaje}");
    }

    public function show(Producto $producto): View
    {
        $this->authorizeBranch($producto);

        $producto->load('categoria');

        return view('supervisor.productos.show', compact('producto'));
    }

    public function edit(Producto $producto): View
    {
        $this->authorizeBranch($producto);

        $producto->load('categoria');
        $categorias = Categoria::ordenadas()->get();
        return view('supervisor.productos.edit', compact('producto', 'categorias'));
    }

    public function update(UpdateProductoRequest $request, Producto $producto): RedirectResponse
    {
        $this->authorizeBranch($producto);

        $data = $request->validated();

        $imagenMensaje = '';
        if ($request->hasFile('imagen')) {
            // Eliminar imagen anterior si existe
            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }
            $data['imagen'] = $this->redimensionarYGuardarImagen($request->file('imagen'), 'productos');
            $imagenMensaje = ' La imagen se ha redimensionado a 800x600px automáticamente.';
        }

        $producto->update($data);

        return redirect()->route('supervisor.productos.index')
            ->with('success', "Producto actualizado correctamente.{$imagenMensaje}");
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        $this->authorizeBranch($producto);

        if ($producto->stock > 0) {
            return redirect()->route('supervisor.productos.index')
                ->with('error', 'No se puede eliminar un producto con stock mayor a 0.');
        }

        // Eliminar imagen si existe
        if ($producto->imagen) {
            Storage::disk('public')->delete($producto->imagen);
        }

        $producto->delete();

        return redirect()->route('supervisor.productos.index')
            ->with('success', 'Producto eliminado correctamente.');
    }

    protected function authorizeBranch(Producto $producto): void
    {
        if ($producto->sucursal_id !== auth()->user()->branch_id) {
            abort(403);
        }
    }

    /**
     * Redimensiona una imagen a 800x600px y la guarda en storage
     *
     * @param \Illuminate\Http\UploadedFile $imagen
     * @param string $directorio
     * @return string Ruta del archivo guardado
     */
    private function redimensionarYGuardarImagen($imagen, string $directorio): string
    {
        // Crear instancia de Intervention Image
        $img = Image::make($imagen)
            ->resize(800, 600, function ($constraint) {
                $constraint->aspectRatio(); // Mantener proporción
                $constraint->upsize(); // No ampliar si es más pequeña
            })
            ->encode('jpg', 85); // Codificar como JPG con 85% de calidad

        // Generar nombre único
        $filename = time() . '_' . uniqid() . '.jpg';
        $path = $directorio . '/' . $filename;

        // Guardar en storage público
        Storage::disk('public')->put($path, (string) $img);

        return $path;
    }
}
