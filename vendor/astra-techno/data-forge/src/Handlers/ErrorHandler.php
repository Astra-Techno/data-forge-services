<?php

namespace AstraTech\DataForge\Handlers;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ErrorHandler extends ExceptionHandler
{
    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        $response = [
            'success' => false,
            'status' => 'error',
            'message' => $this->getDefaultMessage($exception),
        ];

        $status = $this->getStatusCode($exception);

        // Handle validation exceptions
        if ($exception instanceof ValidationException) {
            $response['message'] = $exception->getMessage();
            $response['errors'] = $exception->errors();
            $status = $exception->status;
        }

        // Handle authentication exceptions
        if ($exception instanceof AuthenticationException) {
            $response['message'] = 'Authentication failed. Please check your credentials.';
            $status = 401;
        }

        // Handle database exceptions
        if ($exception instanceof \PDOException) {
            $response['message'] = 'A database error occurred. Please try again later.';
            $status = 500;
        }

        // Include debug information if in debug mode
        if (config('app.debug')) {
            $response['exception'] = get_class($exception);
            $response['trace'] = $exception->getTrace();
        }

        return response()->json($response, $status);
    }

    /**
     * Get the default error message for the exception.
     *
     * @param \Throwable $exception
     * @return string
     */
    protected function getDefaultMessage(Throwable $exception)
    {
        $message = $exception->getMessage();

        if (empty($message)) {
            $className = (new \ReflectionClass($exception))->getShortName();
            return "An error of type {$className} occurred.";
        }

        return $message;
    }

    /**
     * Get the status code for the exception.
     *
     * @param \Throwable $exception
     * @return int
     */
    protected function getStatusCode(Throwable $exception)
    {
        if ($exception instanceof HttpException) {
            return $exception->getStatusCode();
        }

        return method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 400;
    }
}