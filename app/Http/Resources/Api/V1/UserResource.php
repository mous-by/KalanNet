<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->idUtilisateur,
            'nom_prenom' => $this->nomPrenom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'fonction' => $this->fonction,
            'genre' => $this->genre,
            'droit' => $this->droit,
            'statut' => $this->statut,
            'photo_url' => rtrim($request->root(), '/') . '/' . $this->photo_path,
            'permissions' => $this->droit === 'SupAdmin' ? [] : $this->permissionCanonicalNames(),
            'theme_preference' => $this->theme_preference,
            'locale_preference' => $this->locale_preference,
            'ecole' => $this->whenLoaded('ecole', fn () => $this->ecole ? [
                'id' => $this->ecole->idEcole,
                'nom' => $this->ecole->nomEcole,
                'type' => $this->ecole->typeEcole,
                'logo' => $this->ecole->logoEcole,
            ] : null),
        ];
    }
}
