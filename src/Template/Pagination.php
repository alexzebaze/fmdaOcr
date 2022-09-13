<?php


namespace App\Template;


use Pagerfanta\View\DefaultView;

class Pagination extends DefaultView
{
    protected function createDefaultTemplate ()
    {
        return new MyTemplate();
    }

    protected function getDefaultProximity ()
    {
        return 3;
    }

    /**
     * {@inheritdoc}
     */
    public function getName ()
    {
        return 'my_template';
    }
}