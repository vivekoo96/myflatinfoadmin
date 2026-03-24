<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Throwable;
use Razorpay\Api\Errors\SignatureVerificationError;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof SignatureVerificationError) {
            // Handle Razorpay signature verification error
            $error = 'Razorpay Error: ' . $exception->getMessage();
            return new JsonResponse(['error' => $error], 400);
        }

        return parent::render($request, $exception);
    }
}
