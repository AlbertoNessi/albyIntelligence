<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Request;

class RequestHandlerService
{
    public function getParametersFromRequest(Request $request): array
    {
        return json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
