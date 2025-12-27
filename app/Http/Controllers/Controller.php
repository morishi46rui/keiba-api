<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: '競馬API',
    description: '競馬システムのAPIドキュメント',
    contact: new OA\Contact(email: 'support@example.com')
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'APIサーバー'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
abstract class Controller
{
    //
}
