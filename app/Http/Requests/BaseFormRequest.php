<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Autorise la modification si l'utilisateur a la permission _edit
     * OU s'il est cartographe de l'objet passé en route model binding.
     */
    protected function authorizeEdit(): bool
    {
        foreach ($this->route()->parameters() as $param) {
            if ($param instanceof \Illuminate\Database\Eloquent\Model) {
                return Gate::allows('edit-object', $param);
            }
        }

        return false;
    }

    /**
     * Champs contenant du HTML riche (CKEditor)
     * À surcharger dans les FormRequest enfants
     */
    protected array $htmlFields = [];

    protected function prepareForValidation(): void
    {
        $sanitized = $this->sanitizeArray($this->all(), $this->htmlFields);

        $this->merge($sanitized);
    }

    protected function sanitizeArray(array $data, array $htmlFields): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Dans le cas d'un tableau d'items (ex: items.*.champ), la clé pertinente
                // pour savoir si c'est un champ HTML n'est pas l'index numérique
                $sanitized[$key] = $this->sanitizeArray($value, $htmlFields);
            } elseif (is_string($value)) {
                if (in_array($key, $htmlFields, true)) {
                    // Champ HTML riche : sanitiser en conservant les balises sûres
                    $sanitized[$key] = clean($value); // helper de mews/purifier
                } else {
                    // Champ texte : supprimer toutes les balises
                    $sanitized[$key] = strip_tags($value);
                }
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
