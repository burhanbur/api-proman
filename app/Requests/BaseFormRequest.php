<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class BaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->all();
        $errMessage = implode(' | ', $errors);
        \Log::warning($errMessage);

        if ($this->wantsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => $errors,
                    'url' => request()->url(),
                    'method' => request()->method(),
                ], 422)
            );
        } else {
            Session::flash('notification', [
                'level' => 'error',
                'message' => $errMessage
            ]);
    
            throw new ValidationException($validator);
        }
    }
}
