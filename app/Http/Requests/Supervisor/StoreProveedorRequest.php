<?php

namespace App\Http\Requests\Supervisor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'nit' => ['required', 'string', 'max:20', Rule::unique('proveedores', 'nit')],
            'telefono' => ['required', 'string', 'max:20', Rule::unique('proveedores', 'telefono')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('proveedores', 'email')],
            'direccion' => ['nullable', 'string', 'max:500'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del proveedor es obligatorio.',
            'nit.required' => 'El NIT es obligatorio.',
            'nit.unique' => 'Ya existe un proveedor con este NIT.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.unique' => 'Ya existe un proveedor con este teléfono.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe tener un formato válido.',
            'email.unique' => 'Ya existe un proveedor con este email.',
            'direccion.max' => 'La dirección no puede exceder los 500 caracteres.',
        ];
    }
}
