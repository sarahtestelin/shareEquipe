<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NoBadWordsValidator extends ConstraintValidator
{
    private array $badWords = []; // Liste de mots interdits à mettre ici

    public function __construct()
    {
        $this->badWords = $this->loadBadWords();
    }

    private function loadBadWords(): array
    {
        $filePath = __DIR__ . '/../../config/badwords.json'; // Liste externe de mots interdits si cette manière d'utilisation est préférée
        if (file_exists($filePath)) {
            return json_decode(file_get_contents($filePath), true) ?? [];
        }
        return [];
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof NoBadWords) {
            return;
        }
    
        if (null === $value || trim($value) === '') {
            return;
        }
    
        $words = preg_split('/[\s\-_,.!?]+/', mb_strtolower($value));
    
        foreach ($words as $word) {
            if (in_array($word, $this->badWords, true)) {
                $this->context->buildViolation($constraint->message)->addViolation();
                return;
            }
    
            foreach ($this->badWords as $badWord) {
                if ($word === $badWord . 's') {
                    $this->context->buildViolation($constraint->message)->addViolation();
                    return;
                }
            }
        }
    }
    
}
