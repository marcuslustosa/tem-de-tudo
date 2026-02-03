<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email', 'max:255'],
            'senha' => ['required', 'string', 'min:6', 'max:255'],
            'telefone' => ['nullable', 'string', 'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/'],
            'cpf' => ['nullable', 'string', 'regex:/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', 'unique:users,cpf'],
            'data_nascimento' => ['nullable', 'date', 'before:today'],
            'perfil' => ['nullable', 'in:usuario_comum,empresa,admin']
        ];
    }

    /**
     * Mensagens de erro personalizadas
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório',
            'nome.min' => 'O nome deve ter no mínimo 3 caracteres',
            'email.required' => 'O email é obrigatório',
            'email.email' => 'Email inválido',
            'email.unique' => 'Este email já está cadastrado',
            'senha.required' => 'A senha é obrigatória',
            'senha.min' => 'A senha deve ter no mínimo 6 caracteres',
            'telefone.regex' => 'Telefone deve estar no formato (XX) XXXXX-XXXX',
            'cpf.regex' => 'CPF deve estar no formato XXX.XXX.XXX-XX',
            'cpf.unique' => 'Este CPF já está cadastrado',
            'data_nascimento.date' => 'Data de nascimento inválida',
            'data_nascimento.before' => 'Data de nascimento deve ser anterior a hoje',
        ];
    }

    /**
     * Prepara dados antes da validação
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('name') && !$this->has('nome')) {
            $this->merge(['nome' => $this->name]);
        }
        if ($this->has('password') && !$this->has('senha')) {
            $this->merge(['senha' => $this->password]);
        }
    }

    /**
     * Tratamento de falha de validação
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
