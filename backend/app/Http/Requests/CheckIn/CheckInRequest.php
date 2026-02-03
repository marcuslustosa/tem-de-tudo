<?php

namespace App\Http\Requests\CheckIn;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'metodo' => ['nullable', 'in:qrcode,manual,gps']
        ];
    }

    public function messages(): array
    {
        return [
            'empresa_id.required' => 'A empresa é obrigatória',
            'empresa_id.exists' => 'Empresa não encontrada',
            'latitude.between' => 'Latitude inválida',
            'longitude.between' => 'Longitude inválida',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Dados inválidos para check-in',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
