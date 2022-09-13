<?php

namespace App\Controller\Traits;

trait CommunTrait
{
    public function slugify($string){
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
    }
}