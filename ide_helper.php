<?php

namespace Illuminate\Routing {

    use Illuminate\Routing\Matching\ValidatorInterface;

    class Route
    {
        /**
         * Append route validator.
         *
         * @param ValidatorInterface $validator
         */
        public static function appendValidator(ValidatorInterface $validator): void
        {
        }
    }
}