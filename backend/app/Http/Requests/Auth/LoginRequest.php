<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'senha' => ['required', 'string'],
            'remember' => ['nullable', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O email é obrigatório',
            'email.email' => 'Email inválido',
            'senha.required' => 'A senha é obrigatória',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('password') && !$this->has('senha')) {
            $this->merge(['senha' => $this->password]);
        }
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Credenciais inválidas',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
