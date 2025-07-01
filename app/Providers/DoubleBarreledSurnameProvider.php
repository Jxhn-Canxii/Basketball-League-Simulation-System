<?php

namespace App\Providers;

use Faker\Provider\en_US\Person as BasePerson;

class DoubleBarreledSurnameProvider extends BasePerson
{
    public function doubleBarreledSurname()
    {
        // Get two different surnames
        $surname1 = $this->lastName();
        do {
            $surname2 = $this->lastName();
        } while ($surname2 === $surname1); // Ensure surnames are different

        return $surname1 . '-' . $surname2;
    }
}