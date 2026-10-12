<?php

namespace App\Http\Requests\Maestros;

use App\Concerns\CatalogValueValidationRules;
use App\Models\CatalogList;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCatalogValueRequest extends FormRequest
{
    use CatalogValueValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $list = $this->route('list');
        assert($list instanceof CatalogList);

        return $this->catalogValueRules($list);
    }
}
