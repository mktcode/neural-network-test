<?php
/**
 * Created by PhpStorm.
 * User: mkt
 * Date: 03.05.17
 * Time: 12:57
 */

namespace AppBundle\Service;


class Brain
{
    private $n;

    public function __construct($brainFile)
    {
        $this->n = fann_create_from_file($brainFile);
    }

    public function thinkAbout($input)
    {
        return fann_run($this->n, $input);
    }

    public function kill()
    {
        fann_destroy($this->n);
    }
}