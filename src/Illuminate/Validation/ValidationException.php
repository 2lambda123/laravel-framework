<?php

namespace Illuminate\Validation;

use Exception;
use Illuminate\Http\Request;

class ValidationException extends Exception
{
    /**
     * The validator instance.
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    public $validator;

    /**
     * The recommended response to send to the client.
     *
     * @var \Symfony\Component\HttpFoundation\Response|null
     */
    public $response;

    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function __construct($validator, $response = null)
    {
        parent::__construct('The given data failed to pass validation.');

        $this->response = $response;
        $this->validator = $validator;
    }

    /**
     * Get the underlying response instance.
     *
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Render the exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render(Request $request)
    {
        if (! is_null($this->response)) {
            return $this->response;
        }

        $errors = $this->validator->errors()->getMessages();

        if ($request->expectsJson()) {
            return response()->json($errors, 422);
        }

        return redirect()->back()->withInput(
            $request->input()
        )->withErrors($errors);
    }
}
