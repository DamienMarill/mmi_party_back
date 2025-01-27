<?php

namespace App\Rules;

use App\Services\MMIIService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MMIIShapeRule implements ValidationRule
{
    protected MMIIService $mmiiService;

    public function __construct()
    {
        $this->mmiiService = new MMIIService();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail("Le format du MMII n'est pas valide");
            return;
        }

        if (!$this->mmiiService->validateShapeJson($value)) {
            $errors = $this->mmiiService->getValidationErrors($value);
            foreach ($errors as $error) {
                $fail($error);
            }
        }
    }
}
