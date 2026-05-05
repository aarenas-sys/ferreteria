<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Productos
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Gestiona el inventario de tu sucursal y detecta stock bajo.</p>
            </div>
            <div class="flex gap-2">
                <button onclick="openCreateCategoriaModal()" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500">
                    Nueva categoría
                </button>
                <button onclick="openGestionarCategoriaModal()" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500">
                    Gestionar categorías
                </button>
                <a href="{{ route('supervisor.productos.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Nuevo producto
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-red-800">
                    {{ session('error') }}
                </div>
            @endif
            @if($lowStockCount > 0)
                <div class="mb-4 rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-yellow-800">
                    <strong>Alerta:</strong> {{ $lowStockCount }} producto(s) con stock igual o inferior al stock mínimo.
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <!-- Formulario de búsqueda y filtros -->
                    <div class="mb-6 space-y-4">
                        <form method="GET" action="{{ route('supervisor.productos.index') }}" class="space-y-4">
                            <!-- Fila 1: Búsqueda -->
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buscar productos</label>
                                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Buscar por nombre, código o descripción..." class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100">
                            </div>

                            <!-- Fila 2: Filtros -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="categoria_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filtrar por categoría</label>
                                    <select name="categoria_id" id="categoria_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100">
                                        <option value="">Todas las categorías</option>
                                        @foreach($categorias as $categoria)
                                            <option value="{{ $categoria->id }}" {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>
                                                {{ $categoria->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="estado_stock" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filtrar por estado de stock</label>
                                    <select name="estado_stock" id="estado_stock" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100">
                                        <option value="">Todos los estados</option>
                                        <option value="normal" {{ request('estado_stock') === 'normal' ? 'selected' : '' }}>Stock Normal (≥20)</option>
                                        <option value="bajo" {{ request('estado_stock') === 'bajo' ? 'selected' : '' }}>Stock Bajo (<20)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="flex gap-2">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    Filtrar
                                </button>
                                @if(request('search') || request('categoria_id') || request('estado_stock'))
                                    <a href="{{ route('supervisor.productos.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                        Limpiar filtros
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Imagen</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($productos as $producto)
                                    <tr class="{{ $producto->estado_stock === 'bajo' ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ $producto->codigo }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ $producto->nombre }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">
                                            @if($producto->categoria)
                                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                                                    {{ $producto->categoria->nombre }}
                                                </span>
                                            @else
                                                <span class="text-gray-400 text-xs">Sin categoría</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">${{ number_format($producto->precio, 2) }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ $producto->stock }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                                            @if($producto->estado_stock === 'bajo')
                                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                                    Stock Bajo
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                                    Normal
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">
                                            @if($producto->imagen)
                                                <div class="relative inline-block">
                                                    <button class="text-blue-600 hover:text-blue-900 tooltip-trigger">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                    </button>
                                                    <div class="tooltip absolute z-10 invisible opacity-0 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg p-2 transition-opacity duration-200 max-w-xs">
                                                        <img src="{{ asset('storage/' . $producto->imagen) }}" alt="Imagen del producto" class="max-w-full h-auto rounded">
                                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $producto->nombre }}</p>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-gray-400">Sin imagen</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <a href="{{ route('supervisor.productos.show', $producto) }}" class="text-indigo-600 hover:text-indigo-900">Ver</a>
                                            <a href="{{ route('supervisor.productos.edit', $producto) }}" class="text-blue-600 hover:text-blue-900">Editar</a>
                                            <form action="{{ route('supervisor.productos.destroy', $producto) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('¿Eliminar este producto?')">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No hay productos registrados para esta sucursal.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div class="mt-6">
                            {{ $productos->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para imagen -->
    <div id="imageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between pb-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100" id="modalTitle">Imagen del producto</h3>
                    <button onclick="closeImageModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="mt-4">
                    <img id="modalImage" src="" alt="Imagen del producto" class="w-full h-auto max-h-96 object-contain">
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tooltip functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggers = document.querySelectorAll('.tooltip-trigger');

            tooltipTriggers.forEach(trigger => {
                const tooltip = trigger.parentElement.querySelector('.tooltip');

                trigger.addEventListener('mouseenter', function(e) {
                    tooltip.classList.remove('invisible');
                    tooltip.classList.add('visible');
                    tooltip.style.opacity = '1';

                    // Position the tooltip
                    const rect = trigger.getBoundingClientRect();
                    const tooltipRect = tooltip.getBoundingClientRect();

                    // Position above the trigger, centered
                    let top = rect.top - tooltipRect.height - 10;
                    let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);

                    // If tooltip would go off-screen, adjust position
                    if (top < 10) {
                        top = rect.bottom + 10; // Show below instead
                    }

                    if (left < 10) {
                        left = 10;
                    } else if (left + tooltipRect.width > window.innerWidth - 10) {
                        left = window.innerWidth - tooltipRect.width - 10;
                    }

                    tooltip.style.top = top + 'px';
                    tooltip.style.left = left + 'px';
                    tooltip.style.position = 'fixed';
                    tooltip.style.zIndex = '9999';
                });

                trigger.addEventListener('mouseleave', function(e) {
                    tooltip.classList.remove('visible');
                    tooltip.classList.add('invisible');
                    tooltip.style.opacity = '0';
                });
            });
        });

        // Keep modal functions for potential future use
        function openImageModal(imageSrc, productName) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('modalTitle').textContent = 'Imagen de ' + productName;
            document.getElementById('imageModal').classList.remove('hidden');
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });

        // Modal para crear categoría
        function openCreateCategoriaModal() {
            document.getElementById('createCategoriaModal').classList.remove('hidden');
        }

        function closeCreateCategoriaModal() {
            document.getElementById('createCategoriaModal').classList.add('hidden');
            document.getElementById('createCategoriaForm').reset();
        }

        // Crear categoría via AJAX - Usando event delegation
        document.addEventListener('submit', function(e) {
            if (e.target.id !== 'createCategoriaForm') return;
            
            e.preventDefault();

            const form = e.target;
            const nombre = document.getElementById('nombreCategoria').value;
            const descripcion = document.getElementById('descripcionCategoria').value;
            const submitBtn = form.querySelector('button[type="submit"]');

            // Validar nombre
            if (!nombre.trim()) {
                showErrorMessage('El nombre de la categoría es obligatorio');
                return;
            }

            // Deshabilitar botón y mostrar estado
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creando...';

            fetch('{{ route("supervisor.categorias.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    nombre: nombre,
                    descripcion: descripcion,
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            const data = JSON.parse(text);
                            // Si hay errores de validación, mostrar el primero
                            if (data.errors) {
                                const firstError = Object.values(data.errors)[0];
                                throw new Error(Array.isArray(firstError) ? firstError[0] : firstError);
                            }
                            throw new Error(data.message || 'Error al crear categoría');
                        } catch (e) {
                            if (e instanceof SyntaxError) {
                                throw new Error('Error al crear: respuesta inválida del servidor');
                            }
                            throw e;
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                // Mostrar éxito
                showSuccessMessage('Categoría creada exitosamente');

                // Recargar categorías en el dropdown
                reloadCategoriaDropdown();

                // Cerrar modal
                closeCreateCategoriaModal();

                // Restaurar botón
                submitBtn.disabled = false;
                submitBtn.textContent = 'Crear categoría';
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage(error.message || 'Error al crear categoría. Intenta de nuevo.');

                // Restaurar botón
                submitBtn.disabled = false;
                submitBtn.textContent = 'Crear categoría';
            });
        });

        // Recargar dropdown de categorías
        function reloadCategoriaDropdown() {
            fetch('{{ route("supervisor.categorias.dropdown") }}')
                .then(response => response.json())
                .then(categorias => {
                    const select = document.getElementById('categoria_id');
                    const currentValue = select.value;

                    // Guardar opciones actuales excepto las categorías
                    const firstOption = select.options[0];

                    // Limpiar y reconstruir
                    select.innerHTML = '';
                    select.appendChild(firstOption);

                    // Agregar categorías actualizadas
                    categorias.forEach(categoria => {
                        const option = document.createElement('option');
                        option.value = categoria.id;
                        option.textContent = categoria.nombre;
                        if (currentValue == categoria.id) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    });
                })
                .catch(error => console.error('Error al recargar categorías:', error));
        }

        // Funciones auxiliares para mensajes
        function showSuccessMessage(message) {
            // Crear elemento de éxito temporal
            const div = document.createElement('div');
            div.className = 'fixed top-4 right-4 bg-green-50 border border-green-200 p-4 text-green-800 rounded-lg z-50 max-w-md';
            div.textContent = message;
            document.body.appendChild(div);

            setTimeout(() => {
                div.remove();
            }, 3000);
        }

        function showErrorMessage(message) {
            // Crear elemento de error temporal
            const div = document.createElement('div');
            div.className = 'fixed top-4 right-4 bg-red-50 border border-red-200 p-4 text-red-800 rounded-lg z-50 max-w-md';
            div.textContent = message;
            document.body.appendChild(div);

            setTimeout(() => {
                div.remove();
            }, 5000);
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('createCategoriaModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCreateCategoriaModal();
            }
        });

        // ============ GESTIONAR CATEGORÍAS ============

        // Abrir modal de gestionar categorías
        function openGestionarCategoriaModal() {
            cargarCategoriasParaGestionar();
            document.getElementById('gestionarCategoriaModal').classList.remove('hidden');
        }

        function closeGestionarCategoriaModal() {
            document.getElementById('gestionarCategoriaModal').classList.add('hidden');
        }

        // Cargar todas las categorías para gestionar
        function cargarCategoriasParaGestionar() {
            fetch('{{ route("supervisor.categorias.all") }}')
                .then(response => response.json())
                .then(categorias => {
                    const tbody = document.getElementById('categoriasTableBody');
                    tbody.innerHTML = '';

                    if (categorias.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="3" class="px-4 py-4 text-center text-gray-500">No hay categorías creadas</td></tr>';
                        return;
                    }

                    categorias.forEach(categoria => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-200">${categoria.nombre}</td>
                            <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-200">${categoria.descripcion || '-'}</td>
                            <td class="px-4 py-4 text-sm font-medium space-x-2">
                                <button onclick="openEditCategoriaModal(${categoria.id}, '${categoria.nombre}', '${categoria.descripcion || ''}')" class="text-blue-600 hover:text-blue-900">Editar</button>
                                <button onclick="eliminarCategoria(${categoria.id})" class="text-red-600 hover:text-red-900">Eliminar</button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorMessage('Error al cargar categorías');
                });
        }

        // Abrir modal de editar categoría
        function openEditCategoriaModal(id, nombre, descripcion) {
            document.getElementById('editCategoriaId').value = id;
            document.getElementById('editNombreCategoria').value = nombre;
            document.getElementById('editDescripcionCategoria').value = descripcion;
            document.getElementById('editCategoriaModal').classList.remove('hidden');
        }

        function closeEditCategoriaModal() {
            document.getElementById('editCategoriaModal').classList.add('hidden');
        }

        // Actualizar categoría via AJAX - FUNCIÓN EXPLÍCITA
        function submitEditCategoriaForm(event) {
            event.preventDefault();

            const form = document.getElementById('editCategoriaForm');
            const id = document.getElementById('editCategoriaId').value;
            const nombre = document.getElementById('editNombreCategoria').value;
            const descripcion = document.getElementById('editDescripcionCategoria').value;
            const submitBtn = form.querySelector('button[type="submit"]');

            if (!nombre.trim()) {
                showErrorMessage('El nombre de la categoría es obligatorio');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Actualizando...';

            fetch(`{{ route('supervisor.categorias.update', ['categoria' => 'ID']) }}`.replace('ID', id), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    nombre: nombre,
                    descripcion: descripcion,
                })
            })
            .then(response => {
                // Si la respuesta no es OK, intentar parsear como JSON
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            const data = JSON.parse(text);
                            // Si hay errores de validación, mostrar el primero
                            if (data.errors) {
                                const firstError = Object.values(data.errors)[0];
                                throw new Error(Array.isArray(firstError) ? firstError[0] : firstError);
                            }
                            throw new Error(data.message || 'Error al actualizar categoría');
                        } catch (e) {
                            if (e instanceof SyntaxError) {
                                throw new Error('Error al actualizar: respuesta inválida del servidor');
                            }
                            throw e;
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                showSuccessMessage('Categoría actualizada exitosamente');
                cargarCategoriasParaGestionar();
                reloadCategoriaDropdown();
                closeEditCategoriaModal();
                submitBtn.disabled = false;
                submitBtn.textContent = 'Actualizar categoría';
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage(error.message || 'Error al actualizar categoría');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Actualizar categoría';
            });
        }

        // Actualizar categoría via AJAX - Event listener (respaldo)
        document.addEventListener('submit', function(e) {
            if (e.target.id !== 'editCategoriaForm') return;
            submitEditCategoriaForm(e);
        });

        // Eliminar categoría
        function eliminarCategoria(id) {
            if (!confirm('¿Estás seguro de que deseas eliminar esta categoría?')) {
                return;
            }

            fetch(`{{ route('supervisor.categorias.destroy', ['categoria' => 'ID']) }}`.replace('ID', id), {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            const data = JSON.parse(text);
                            throw new Error(data.message || 'Error al eliminar categoría');
                        } catch (e) {
                            if (e instanceof SyntaxError) {
                                throw new Error('Error al eliminar: respuesta inválida del servidor');
                            }
                            throw e;
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showSuccessMessage(data.message);
                    cargarCategoriasParaGestionar();
                    reloadCategoriaDropdown();
                } else {
                    showErrorMessage(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage(error.message || 'Error al eliminar categoría');
            });
        }

        // Cerrar modales al hacer clic fuera
        document.getElementById('gestionarCategoriaModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeGestionarCategoriaModal();
            }
        });

        document.getElementById('editCategoriaModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditCategoriaModal();
            }
        });
    </script>

    <style>
    .tooltip {
        pointer-events: none;
        transform: translateY(-5px);
        transition: all 0.2s ease-in-out;
    }

    .tooltip.visible {
        transform: translateY(0);
    }

    .tooltip-trigger {
        position: relative;
        cursor: pointer;
    }

    /* Ensure tooltip appears above other elements */
    .tooltip {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1), 0 4px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
    }

    .dark .tooltip {
        border-color: #374151;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3), 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    </style>

    <!-- Modal para crear categoría -->
    <div id="createCategoriaModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between pb-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Nueva categoría</h3>
                    <button onclick="closeCreateCategoriaModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form id="createCategoriaForm" class="space-y-4 mt-4">
                    <div>
                        <label for="nombreCategoria" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nombre de la categoría <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="nombreCategoria" name="nombre" required
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100"
                            placeholder="Ej: Cables, Herramientas, Electrónico...">
                    </div>

                    <div>
                        <label for="descripcionCategoria" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Descripción
                        </label>
                        <textarea id="descripcionCategoria" name="descripcion"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100"
                            placeholder="Descripción opcional de la categoría..."
                            rows="3"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" onclick="closeCreateCategoriaModal()"
                            class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500">
                            Crear categoría
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para gestionar categorías -->
    <div id="gestionarCategoriaModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between pb-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Gestionar categorías</h3>
                    <button onclick="closeGestionarCategoriaModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="categoriasTableBody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-center text-gray-500">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="closeGestionarCategoriaModal()"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar categoría -->
    <div id="editCategoriaModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between pb-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Editar categoría</h3>
                    <button onclick="closeEditCategoriaModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form id="editCategoriaForm" class="space-y-4 mt-4" onsubmit="submitEditCategoriaForm(event)">
                    <input type="hidden" id="editCategoriaId">

                    <div>
                        <label for="editNombreCategoria" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nombre de la categoría <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="editNombreCategoria" name="nombre" required
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100"
                            placeholder="Ej: Cables, Herramientas, Electrónico...">
                    </div>

                    <div>
                        <label for="editDescripcionCategoria" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Descripción
                        </label>
                        <textarea id="editDescripcionCategoria" name="descripcion"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100"
                            placeholder="Descripción opcional de la categoría..."
                            rows="3"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" onclick="closeEditCategoriaModal()"
                            class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500">
                            Actualizar categoría
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
