<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => [
                'required',
                'integer',
                'exists:bookings,id',
                Rule::unique('reviews', 'booking_id'),
            ],
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Get the booking with relationships
        $booking = Booking::with(['servicePrice.service', 'staff'])
            ->findOrFail($validated['booking_id']);

        // Verify booking belongs to authenticated user
        if ($booking->customer_id !== $request->user()->id) {
            abort(403, 'Unauthorized to review this booking.');
        }

        // Only allow reviews for confirmed bookings that have ended
        if ($booking->status !== Booking::STATUS_CONFIRMED) {
            return back()->with('error', 'You can only review confirmed bookings.');
        }

        if ($booking->ends_at->isFuture()) {
            return back()->with('error', 'You can only review completed bookings.');
        }

        // Create the review
        $review = Review::create([
            'booking_id' => $booking->id,
            'customer_id' => $request->user()->id,
            'staff_id' => $booking->staff_id,
            'service_id' => $booking->servicePrice->service_id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return back()->with('success', 'Thank you for your review!');
    }

    public function index(Request $request)
    {
        $query = Review::query()
            ->with(['customer:id,name', 'booking:id,starts_at', 'service:id,name', 'staff:id,name'])
            ->latest();

        // Filter by service
        if ($request->has('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        // Filter by staff
        if ($request->has('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        // Filter by rating
        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        $reviews = $query->paginate(20)->through(fn($review) => [
            'id' => $review->id,
            'customer_name' => $review->customer->name,
            'service_name' => $review->service?->name,
            'staff_name' => $review->staff?->name,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'booking_date' => $review->booking->starts_at->toIso8601String(),
            'created_at' => $review->created_at->toIso8601String(),
        ]);

        return inertia('Reviews/Index', [
            'reviews' => $reviews,
            'filters' => $request->only(['service_id', 'staff_id', 'rating']),
        ]);
    }

    public function getServiceStats($serviceId)
    {
        $stats = Review::where('service_id', $serviceId)
            ->select([
                DB::raw('COUNT(*) as total_reviews'),
                DB::raw('AVG(rating) as average_rating'),
                DB::raw('SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star'),
                DB::raw('SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star'),
                DB::raw('SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star'),
                DB::raw('SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star'),
                DB::raw('SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star'),
            ])
            ->first();

        return $stats;
    }

    public function getStaffStats($staffId)
    {
        $stats = Review::where('staff_id', $staffId)
            ->select([
                DB::raw('COUNT(*) as total_reviews'),
                DB::raw('AVG(rating) as average_rating'),
                DB::raw('SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star'),
                DB::raw('SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star'),
                DB::raw('SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star'),
                DB::raw('SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star'),
                DB::raw('SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star'),
            ])
            ->first();

        return $stats;
    }
}
