<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\System\HealthCheckService;
use inertia\Inertia;

class HealthCheckController extends Controller
{
    protected $healthCheckService;
    public function __construct() {
        $this->healthCheckService = new HealthCheckService();
    }

    public function index()
    {
        return Inertia::render('System/Index', [
            'status' => session('status'),
        ]);
    }

    public function healthCheck()
    {
        $result = $this->healthCheckService->check();

        $httpStatus = match ($result['status']) {
            'healthy' => 200,
            'warning' => 200,
            default   => 503,
        };

        return response()->json(
            $result,
            $httpStatus
        );
    }
}