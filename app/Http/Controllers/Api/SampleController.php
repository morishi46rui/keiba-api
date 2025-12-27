<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SampleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/sample",
     *     tags={"サンプル"},
     *     summary="サンプルデータの取得",
     *     description="サンプルエンドポイントの説明",
     *     @OA\Response(
     *         response=200,
     *         description="成功時のレスポンス",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Hello, World!"),
     *             @OA\Property(property="timestamp", type="string", format="date-time", example="2025-12-28T01:00:00Z")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Hello, World!',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/sample",
     *     tags={"サンプル"},
     *     summary="サンプルデータの作成",
     *     description="サンプルデータを作成します",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="テストデータ"),
     *             @OA\Property(property="description", type="string", example="説明文")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="作成成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="バリデーションエラー"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        return response()->json([
            'message' => 'Created successfully',
            'data' => $validated,
        ], 201);
    }
}
