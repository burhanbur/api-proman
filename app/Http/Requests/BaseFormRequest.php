<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use App\Traits\ApiResponse;

class BaseFormRequest extends FormRequest
{
    use ApiResponse;

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
            $response = $this->errorResponse($errors, 422);

            throw new HttpResponseException($response);
        } else {
            Session::flash('notification', [
                'level' => 'error',
                'message' => $errMessage
            ]);
    
            throw new ValidationException($validator);
        }
    }
}
