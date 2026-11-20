<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Mot de passe solide : 8 caractères minimum, majuscule, minuscule, chiffre et symbole (§44). */
class MotDePasseFort implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $v = (string) $value;

        if (mb_strlen($v) < 8) {
            $fail('Le mot de passe doit contenir au moins 8 caractères.');

            return;
        }
        if (! preg_match('/\p{Ll}/u', $v) || ! preg_match('/\p{Lu}/u', $v)) {
            $fail('Le mot de passe doit contenir au moins une majuscule et une minuscule.');

            return;
        }
        if (! preg_match('/\d/', $v)) {
            $fail('Le mot de passe doit contenir au moins un chiffre.');

            return;
        }
        if (! preg_match('/[^\p{L}\d\s]/u', $v)) {
            $fail('Le mot de passe doit contenir au moins un symbole (par exemple ! ? # @ $).');
        }
    }
}