<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:branches,name',
            'address' => 'required|string|max:255|unique:branches,address',
            'phone' => 'nullable|string|max:20|unique:branches,phone',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe una sucursal con este nombre.',
            'address.unique' => 'Ya existe una sucursal con esta dirección.',
            'phone.unique' => 'Ya existe una sucursal con este teléfono.',
        ];
    }
}
