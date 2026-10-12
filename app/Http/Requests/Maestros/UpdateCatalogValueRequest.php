<?php

namespace App\Http\Requests\Maestros;

use App\Concerns\CatalogValueValidationRules;
use App\Models\CatalogValue;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCatalogValueRequest extends FormRequest
{
    use CatalogValueValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $value = $this->route('value');
        assert($value instanceof CatalogValue);

        return $this->catalogValueRules($value->list, $value);
    }
}
