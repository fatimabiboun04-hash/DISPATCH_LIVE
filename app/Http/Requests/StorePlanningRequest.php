<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'       => 'required|exists:users,id',
            'date'          => 'required|date',
            'shift'         => 'required|in:J,J+,A,N,HO,R',
            'heure_debut'   => 'required|date_format:H:i',
            'heure_fin'     => 'required|date_format:H:i',
            'pause_minutes' => 'required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required'     => 'Employe obligatoire',
            'date.required'        => 'Date obligatoire',
            'shift.required'       => 'Shift obligatoire',
            'shift.in'             => 'Shift doit être: J, J+, A, N, HO, R',
            'heure_debut.required' => 'Heure début obligatoire',
            'heure_fin.required'   => 'Heure fin obligatoire',
        ];
    }
}
