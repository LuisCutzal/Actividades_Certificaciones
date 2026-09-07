<?php

namespace Application\Utils;

use Laminas\Paginator\Paginator;
use Laminas\Paginator\Adapter\ArrayAdapter;

class PaginatorHelper
{
    public static function paginate($data, $page = 1, $perPage = 15)
    {
        $datosArray = is_array($data)
            ? $data
            : iterator_to_array($data);

        $paginator = new Paginator(new ArrayAdapter($datosArray));
        $paginator->setCurrentPageNumber((int)$page);
        $paginator->setItemCountPerPage($perPage);

        return $paginator;
    }
}
