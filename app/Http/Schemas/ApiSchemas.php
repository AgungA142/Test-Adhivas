<?php

namespace App\Http\Schemas;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model",
 *     @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="email_verified_at", type="string", nullable=true, example="2023-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="created_at", type="string", example="2023-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", example="2023-01-01T00:00:00.000000Z")
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     title="Success Response",
 *     description="Standard success response format",
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(property="message", type="string", example="Operation successful"),
 *     @OA\Property(property="data", type="object", description="Response data")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     title="Error Response",
 *     description="Standard error response format",
 *     @OA\Property(property="status", type="string", example="error"),
 *     @OA\Property(property="message", type="string", example="Error message"),
 *     @OA\Property(property="errors", type="object", nullable=true, description="Validation errors")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     type="object",
 *     title="Validation Error Response",
 *     description="Validation error response format",
 *     @OA\Property(property="status", type="string", example="error"),
 *     @OA\Property(property="message", type="string", example="Validation failed"),
 *     @OA\Property(property="errors", type="object",
 *         @OA\Property(property="email", type="array",
 *             @OA\Items(type="string", example="The email field is required.")
 *         ),
 *         @OA\Property(property="password", type="array",
 *             @OA\Items(type="string", example="The password field is required.")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UnauthenticatedResponse",
 *     type="object",
 *     title="Unauthenticated Response",
 *     description="Unauthenticated error response",
 *     @OA\Property(property="message", type="string", example="Unauthenticated.")
 * )
 *
 * @OA\Schema(
 *     schema="LoginRequest",
 *     type="object",
 *     title="Login Request",
 *     description="Login request payload",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email", example="test@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="password")
 * )
 *
 * @OA\Schema(
 *     schema="RegisterRequest",
 *     type="object",
 *     title="Register Request",
 *     description="Registration request payload",
 *     required={"name", "email", "password", "password_confirmation"},
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="password123"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
 * )
 *
 * @OA\Schema(
 *     schema="CreateUserRequest",
 *     type="object",
 *     title="Create User Request",
 *     description="Create user request payload",
 *     required={"name", "email", "password"},
 *     @OA\Property(property="name", type="string", example="New User"),
 *     @OA\Property(property="email", type="string", format="email", example="newuser@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="password123")
 * )
 *
 * @OA\Schema(
 *     schema="UpdateUserRequest",
 *     type="object",
 *     title="Update User Request",
 *     description="Update user request payload",
 *     @OA\Property(property="name", type="string", example="Updated User"),
 *     @OA\Property(property="email", type="string", format="email", example="updated@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="newpassword123")
 * )
 *
 * @OA\Schema(
 *     schema="AuthResponse",
 *     type="object",
 *     title="Authentication Response",
 *     description="Authentication success response",
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(property="message", type="string", example="Login successful"),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="user", ref="#/components/schemas/User"),
 *         @OA\Property(property="token", type="string", example="1|abc123..."),
 *         @OA\Property(property="token_type", type="string", example="Bearer")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Book",
 *     type="object",
 *     title="Book",
 *     description="Book model",
 *     @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="title", type="string", example="Laravel Guide"),
 *     @OA\Property(property="author", type="string", example="John Doe"),
 *     @OA\Property(property="published_year", type="integer", example=2023),
 *     @OA\Property(property="isbn", type="string", example="978-0123456789"),
 *     @OA\Property(property="stock", type="integer", example=5),
 *     @OA\Property(property="available_stock", type="integer", example=3),
 *     @OA\Property(property="created_at", type="string", example="2023-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", example="2023-01-01T00:00:00.000000Z")
 * )
 *
 * @OA\Schema(
 *     schema="BookLoan",
 *     type="object",
 *     title="Book Loan",
 *     description="Book loan model",
 *     @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="user_id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="book_id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="loan_date", type="string", format="date", example="2023-01-01"),
 *     @OA\Property(property="due_date", type="string", format="date", example="2023-01-15"),
 *     @OA\Property(property="return_date", type="string", format="date", nullable=true, example="2023-01-10"),
 *     @OA\Property(property="status", type="string", enum={"borrowed", "returned"}, example="borrowed"),
 *     @OA\Property(property="is_overdue", type="boolean", example=false),
 *     @OA\Property(property="days_until_due", type="integer", example=5),
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="book", ref="#/components/schemas/Book"),
 *     @OA\Property(property="created_at", type="string", example="2023-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", example="2023-01-01T00:00:00.000000Z")
 * )
 *
 * @OA\Schema(
 *     schema="CreateBookRequest",
 *     type="object",
 *     title="Create Book Request",
 *     description="Create book request payload",
 *     required={"title", "author", "published_year", "isbn", "stock"},
 *     @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="title", type="string", example="Laravel Guide"),
 *     @OA\Property(property="author", type="string", example="John Doe"),
 *     @OA\Property(property="published_year", type="integer", example=2023),
 *     @OA\Property(property="isbn", type="string", example="978-0123456789"),
 *     @OA\Property(property="stock", type="integer", example=5)
 * )
 *
 * @OA\Schema(
 *     schema="UpdateBookRequest",
 *     type="object",
 *     title="Update Book Request",
 *     description="Update book request payload",
 *     @OA\Property(property="title", type="string", example="Updated Laravel Guide"),
 *     @OA\Property(property="author", type="string", example="Jane Doe"),
 *     @OA\Property(property="published_year", type="integer", example=2024),
 *     @OA\Property(property="isbn", type="string", example="978-0987654321"),
 *     @OA\Property(property="stock", type="integer", example=10)
 * )
 *
 * @OA\Schema(
 *     schema="NotFoundResponse",
 *     type="object",
 *     title="Not Found Response",
 *     description="Resource not found response",
 *     @OA\Property(property="status", type="string", example="error"),
 *     @OA\Property(property="message", type="string", example="Resource not found")
 * )
 *
 * @OA\Schema(
 *     schema="BorrowBookRequest",
 *     type="object",
 *     title="Borrow Book Request",
 *     description="Request payload untuk meminjam buku",
 *     required={"book_id"},
 *     @OA\Property(property="book_id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="loan_days", type="integer", example=14, description="Number of days to borrow (default: 14)")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     type="object",
 *     title="Pagination Metadata",
 *     description="Pagination information",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="from", type="integer", example=1),
 *     @OA\Property(property="last_page", type="integer", example=2),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="to", type="integer", example=15),
 *     @OA\Property(property="total", type="integer", example=30),
 *     @OA\Property(property="first_page_url", type="string", example="http://localhost:8000/api/books?page=1"),
 *     @OA\Property(property="last_page_url", type="string", example="http://localhost:8000/api/books?page=2"),
 *     @OA\Property(property="next_page_url", type="string", nullable=true, example="http://localhost:8000/api/books?page=2"),
 *     @OA\Property(property="prev_page_url", type="string", nullable=true, example=null),
 *     @OA\Property(property="path", type="string", example="http://localhost:8000/api/books")
 * )
 *
 * @OA\Schema(
 *     schema="BooksPaginatedResponse",
 *     type="object",
 *     title="Books Paginated Response",
 *     description="Paginated books response",
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(property="message", type="string", example="Books retrieved successfully"),
 *     @OA\Property(property="data", type="object",
 *         allOf={
 *             @OA\Schema(ref="#/components/schemas/PaginationMeta"),
 *             @OA\Schema(
 *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Book"))
 *             )
 *         }
 *     )
 * )
 */
class ApiSchemas
{
    // This class is only used for OpenAPI schema definitions
}
