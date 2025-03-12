<?php

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class NoBadWords extends Constraint
{
    public string $message = "Le texte contient un mot interdit.";

    public function __construct(
        array $options = [],
        string $message = null
    ) {
        parent::__construct($options);
        if ($message !== null) {
            $this->message = $message;
        }
    }
}