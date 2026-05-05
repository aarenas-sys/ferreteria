<?php

namespace App\Http\Requests\Supervisor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branchId = auth()->user()->branch_id;

        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('productos', 'nombre')->where('sucursal_id', $branchId),
            ],
            'codigo' => [
                'required',
                'string',
                'max:255',
                Rule::unique('productos', 'codigo')->where('sucursal_id', $branchId),
            ],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
            'imagen' => ['nullable', 'file', 'mimes:jpeg,jpg,png,svg', 'max:10240'], // 10MB max
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'nombre.unique' => 'Este nombre ya existe. Por favor elige otro nombre.',
            'codigo.required' => 'El código del producto es obligatorio.',
            'codigo.unique' => 'Este código ya existe. Por favor elige otro código.',
            'precio.required' => 'El precio es obligatorio.',
            'precio.numeric' => 'El precio debe ser un valor numérico.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock_minimo.required' => 'El stock mínimo es obligatorio.',
            'stock_minimo.integer' => 'El stock mínimo debe ser un número entero.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe ser de tipo JPG, JPEG, PNG o SVG.',
            'imagen.max' => 'La imagen no debe superar los 10MB.',
        ];
    }
}
