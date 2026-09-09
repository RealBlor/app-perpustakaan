<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
    *@return array<string, ValidationRule|array<mixed>|string>
    */
    public function rules(): array
    {
        return [
            'Nama' => 'required|string|max:200',
            'NIM' => 'required|string|max:100',
            'Email' => 'required|string|max:200',
            'Nomor_telepon' => 'required|string|max:100',
            'Alamat' => 'required|string|max:20',
            'Status' => 'required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'Nama.required' => 'Nama wajib diisi.',
            'Nama.max' => 'Nama maksimal 200 karakter.',
            'NIM.required' => 'NIM wajib diisi.',
            'Email.required' => 'Email wajib diisi.',
            'Nomor_telepon.required' => 'Nomor telepon wajib diisi.',
            'Alamat.required' => 'Alamat wajib diisi.',
            'Status.required' => 'Status diisi aktif atau tidak aktif.',
        ];
    }
}