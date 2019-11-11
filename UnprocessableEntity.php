<?php

namespace Vmodel;

use Session;

class UnprocessableEntity implements ReturnInterface
{
    public function __construct(array $data)
    {
        $this->message = $data['message'];
        $this->errors = $data['errors'];
        $this->code = 422;
    }

    public function setErrors()
    {
        Session::put('errors', $this->errors);
    }

}