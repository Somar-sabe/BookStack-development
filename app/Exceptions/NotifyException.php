<?php

namespace BookStack\Exceptions;

use Exception;
use Illuminate\Contracts\Support\Responsable;

class NotifyException extends Exception implements Responsable
{
    public $message;
    public $redirectLocation;
    protected $status;

    public function __construct(string $message, string $redirectLocation = '/', int $status = 500)
    {
        $this->message = $message;
        $this->redirectLocation = $redirectLocation;
        $this->status = $status;
        parent::__construct();
    }

    /**
     * Get the desired status code for this exception.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Send the response for this type of exception.
     *
     * {@inheritdoc}
     */
    public function toResponse($request)
    {
        $message = $this->getMessage();

        // Front-end JSON handling. API-side handling managed via handler.
        if ($request->wantsJson()) {
            return response()->json(['error' => $message], 403);
        }

        if (!empty($message)) {
            session()->flash('error', $message);
        }

        return redirect($this->redirectLocation);
    }
}
