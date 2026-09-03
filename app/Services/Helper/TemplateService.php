<?php

namespace App\Services\Helper;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Helper\HelperService;

class TemplateService
{
   protected $helper;

    public function __construct()
    {
        $this->helper = new HelperService();
    }
   
}
