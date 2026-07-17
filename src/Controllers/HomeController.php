<?php
declare(strict_types=1);

namespace Velo\Controllers;

use Velo\Http\HttpResponse;

class HomeController extends Controller
{
    public function index(): HttpResponse
    {
//        throw new \Exception('An error occurred');
        return $this->returnResopnse('index', ['response' => 3]);
    }

    public function api(): HttpResponse
    {
        return $this->returnResopnse(data: ['response' => 2]);
    }
}