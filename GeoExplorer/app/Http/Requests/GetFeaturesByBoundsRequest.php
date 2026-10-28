<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GetFeaturesByBoundsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza las coordenadas del viewport a los límites geográficos [-180, 180] y [-90, 90]
     * cuando el usuario hace zoom out amplio en el mapa.
     */
    protected function prepareForValidation(): void
    {
        $clamp = static fn (mixed $val, float $min, float $max): ?float => is_numeric($val)
            ? max($min, min($max, (float) $val))
            : null;

        if ($this->has(['min_lng', 'max_lng', 'min_lat', 'max_lat'])) {
            $this->merge([
                'min_lng' => $clamp($this->input('min_lng'), -180.0, 180.0),
                'max_lng' => $clamp($this->input('max_lng'), -180.0, 180.0),
                'min_lat' => $clamp($this->input('min_lat'), -90.0, 90.0),
                'max_lat' => $clamp($this->input('max_lat'), -90.0, 90.0),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'min_lng' => ['required', 'numeric', 'between:-180,180'],
            'min_lat' => ['required', 'numeric', 'between:-90,90'],
            'max_lng' => ['required', 'numeric', 'between:-180,180', 'gte:min_lng'],
            'max_lat' => ['required', 'numeric', 'between:-90,90', 'gte:min_lat'],
            'timestamp' => ['nullable', 'date'],
            'layer_ids' => ['nullable', 'array'],
            'layer_ids.*' => ['integer', 'exists:layers,id'],
        ];
    }
}
