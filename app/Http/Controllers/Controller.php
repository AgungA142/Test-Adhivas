<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Laravel Book Management API",
 *     version="1.0.0",
 *     description="Comprehensive API documentation untuk sistem manajemen buku dengan Laravel dan Sanctum authentication. Menyediakan fitur CRUD untuk books, user management, dan sistem peminjaman buku dengan validasi stock.",
 *     @OA\Contact(
 *         email="agung.alfatah43@gmail.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Local Development Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter token in format (Bearer <token>)"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="API endpoints untuk authentication"
 * )
 *
 * @OA\Tag(
 *     name="Users",
 *     description="API endpoints untuk user management"
 * )
 *
 * @OA\Tag(
 *     name="Books",
 *     description="API endpoints untuk book management"
 * )
 *
 * @OA\Tag(
 *     name="Book Loans",
 *     description="API endpoints untuk book loan management"
 * )
 *
 *
 * @OA\Response(
 *     response="NotFound",
 *     description="Resource not found",
 *     @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
 * )
 *
 * @OA\Response(
 *     response="ValidationError",
 *     description="Validation error",
 *     @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
 * )
 *
 * @OA\Response(
 *     response="Unauthenticated",
 *     description="Unauthenticated",
 *     @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")
 * )
 */
abstract class Controller
{
    //
}
