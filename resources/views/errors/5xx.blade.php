@include('errors.minimal', ['code' => $exception->getStatusCode(), 'title' => 'Something went wrong.', 'message' => 'The server couldn’t finish that request. Please try again in a minute.'])
