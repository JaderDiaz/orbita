<?php

namespace App\Http\Requests\Clientes;

use App\Concerns\ClienteValidationRules;
use App\Models\Cliente;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClienteRequest extends FormRequest
{
    use ClienteValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $cliente = $this->route('cliente');
        assert($cliente instanceof Cliente);

        return $this->clienteRules($cliente);
    }
}
