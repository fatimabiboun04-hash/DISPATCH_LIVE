<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'          => 'sometimes|date',
            'shift'         => 'sometimes|in:J,J+,A,N,HO,R',
            'heure_debut'   => 'sometimes|date_format:H:i',
            'heure_fin'     => 'sometimes|date_format:H:i',
            'pause_minutes' => 'sometimes|integer|min:0',
        ];
    }
}
