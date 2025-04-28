<?php

namespace ForminatorPDFAddon;

if (!\function_exists('ForminatorPDFAddon\\dd')) {
    function dd(...$args)
    {
        if (\function_exists('ForminatorPDFAddon\\dump')) {
            dump(...$args);
        } else {
            \var_dump(...$args);
        }
        die;
    }
}