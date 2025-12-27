<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="競馬API",
 *     version="1.0.0",
 *     description="競馬システムのAPIドキュメント",
 *     @OA\Contact(
 *         email="support@example.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="APIサーバー"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
abstract class Controller
{
    //
}
