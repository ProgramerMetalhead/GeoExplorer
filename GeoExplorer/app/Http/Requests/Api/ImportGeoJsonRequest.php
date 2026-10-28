<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ImportGeoJsonRequest extends FormRequest
{
    /**
     * Determina si el usuario tiene autorización para realizar esta petición.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para la carga del archivo GeoJSON.
     */
    public function rules(): array
    {
        return [
            'layer_id' => [
                'required',
                'integer',
                'exists:layers,id',
            ],
            'file' => [
                'required',
                'file',
                'max:102400', // Límite de 100 MB (en kilobytes)
                function ($attribute, $value, $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    if (! in_array($extension, ['json', 'geojson'])) {
                        $fail('El archivo debe tener una extensión válida (.geojson o .json).');
                    }
                },
            ],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
    public function messages(): array
    {
        return [
            'layer_id.required' => 'Debes indicar la capa a la que pertenecerán las geometrías.',
            'layer_id.integer' => 'El identificador de la capa debe ser numérico.',
            'layer_id.exists' => 'La capa seleccionada no existe en la base de datos.',
            'file.required' => 'Es obligatorio adjuntar un archivo vectorial.',
            'file.file' => 'El dato enviado debe ser un archivo válido.',
            'file.max' => 'El archivo supera el tamaño máximo permitido de 100 MB.',
        ];
    }
}
