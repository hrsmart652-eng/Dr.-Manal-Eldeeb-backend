<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookResource;
use App\Http\Resources\V1\BookDetailResource;
use App\Http\Resources\V1\ReviewResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Books
 * 
 * APIs for browsing and viewing books
 */
class BookController extends Controller
{
    /**
     * List all books
     * 
     * Get paginated list of published books with filtering and sorting.
     * 
     * @queryParam page integer Page number. Example: 1
     * @queryParam per_page integer Items per page (max 50). Example: 12
     * @queryParam category_id integer Filter by category ID. Example: 1
     * @queryParam author_id integer Filter by author ID. Example: 2
     * @queryParam format string Filter by format (pdf, epub, physical, both). Example: pdf
     * @queryParam search string Search in title and description. Example: القيادة
     * @queryParam sort string Sort by (popular, newest, rating, price_low, price_high). Example: popular
     * @queryParam featured_first boolean Show featured books first. Example: true
     */
    public function index(Request $request): JsonResponse
    {
        $query = Book::query()
            ->with(['author.user', 'category'])
            ->published()
            ->inStock();

        // Apply filters
        $this->applyFilters($query, $request);

        // Apply sorting
        $this->applySorting($query, $request);

        // Featured first
        if ($request->boolean('featured_first')) {
            $query->orderBy('is_featured', 'desc');
        }

        // Pagination
        $perPage = min($request->get('per_page', 12), 50);
        $books = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'books' => BookResource::collection($books),
                'meta' => [
                    'current_page' => $books->currentPage(),
                    'last_page' => $books->lastPage(),
                    'per_page' => $books->perPage(),
                    'total' => $books->total(),
                ],
                'links' => [
                    'first' => $books->url(1),
                    'last' => $books->url($books->lastPage()),
                    'prev' => $books->previousPageUrl(),
                    'next' => $books->nextPageUrl(),
                ],
            ],
        ]);
    }

    /**
     * Get book details
     * 
     * Get full details of a specific book.
     * 
     * @urlParam slug string required Book slug. Example: leadership-book
     */
    public function show(string $slug): JsonResponse
    {
        $book = Book::with([
            'author.user',
            'category',
        ])
        ->where('slug', $slug)
        ->published()
        ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new BookDetailResource($book),
        ]);
    }

    /**
     * Get book reviews
     * 
     * Get paginated reviews for a specific book.
     * 
     * @urlParam slug string required Book slug. Example: leadership-book
     * @queryParam page integer Page number. Example: 1
     * @queryParam per_page integer Reviews per page. Example: 10
     * @queryParam rating integer Filter by rating (1-5). Example: 5
     * @queryParam sort string Sort by (recent, helpful, rating_high, rating_low). Example: recent
     */
    public function reviews(string $slug, Request $request): JsonResponse
    {
        $book = Book::where('slug', $slug)->firstOrFail();
        
        $query = $book->approvedReviews()->with('user');

        // Filter by rating
        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        // Sorting
        $sort = $request->get('sort', 'recent');
        match ($sort) {
            'helpful' => $query->orderBy('helpful_count', 'desc'),
            'rating_high' => $query->orderBy('rating', 'desc'),
            'rating_low' => $query->orderBy('rating', 'asc'),
            default => $query->latest(),
        };

        $reviews = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => ReviewResource::collection($reviews),
                'meta' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'total' => $reviews->total(),
                ],
            ],
        ]);
    }

    /**
     * Get book preview pages
     * 
     * Get sample pages for a book.
     * 
     * @urlParam slug string required Book slug. Example: leadership-book
     */
    public function preview(string $slug): JsonResponse
    {
        $book = Book::where('slug', $slug)->firstOrFail();

        if (empty($book->sample_pages)) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد صفحات عينة متاحة لهذا الكتاب',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'book_id' => $book->id,
                'title' => $book->title_ar,
                'sample_pages' => $book->sample_pages,
            ],
        ]);
    }

    /**
     * Get related books
     * 
     * Get books related to the specified book (same category or author).
     * 
     * @urlParam slug string required Book slug. Example: leadership-book
     * @queryParam limit integer Number of books to return (max 10). Example: 4
     */
    public function related(string $slug, Request $request): JsonResponse
    {
        $book = Book::where('slug', $slug)->firstOrFail();
        
        $limit = min($request->get('limit', 4), 10);
        
        // Get books by same author or category
        $relatedBooks = Book::where(function($query) use ($book) {
                $query->where('author_id', $book->author_id)
                      ->orWhere('category_id', $book->category_id);
            })
            ->where('id', '!=', $book->id)
            ->published()
            ->inStock()
            ->with(['author.user', 'category'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => BookResource::collection($relatedBooks),
        ]);
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, Request $request)
    {
        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        if ($request->has('author_id')) {
            $query->byAuthor($request->author_id);
        }

        if ($request->has('format')) {
            if ($request->format() === 'digital') {
                $query->digital();
            } elseif ($request->format() === 'physical') {
                $query->physical();
            }
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }
    }

    /**
     * Apply sorting to query
     */
    private function applySorting($query, Request $request)
    {
        $sort = $request->get('sort', 'newest');
        
        match ($sort) {
            'popular' => $query->popular(),
            'rating' => $query->topRated(),
            'price_low' => $query->orderBy('digital_price', 'asc'),
            'price_high' => $query->orderBy('digital_price', 'desc'),
            'newest' => $query->recent(),
            default => $query->recent(),
        };
    }
}
