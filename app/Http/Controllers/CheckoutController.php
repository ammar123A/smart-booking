<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\UserVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CheckoutController
{
    public function __invoke(Request $request)
    {
        $services = Service::query()
            ->where('active', true)
            ->with([
                'prices' => fn ($q) => $q->where('active', true)->orderBy('amount')->orderBy('duration_min')->orderBy('id'),
                'reviews' => fn ($q) => $q->with('customer:id,name')->latest()->limit(3),
            ])
            ->withCount('reviews as total_reviews')
            ->withAvg('reviews as average_rating', 'rating')
            ->orderBy('name')
            ->get()
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'average_rating' => $service->average_rating ? round($service->average_rating, 1) : null,
                'total_reviews' => (int) $service->total_reviews,
                'prices' => $service->prices->map(fn ($price) => [
                    'id' => $price->id,
                    'name' => $price->name,
                    'duration_min' => (int) $price->duration_min,
                    'amount' => (int) $price->amount,
                    'currency' => (string) $price->currency,
                ])->values(),
                'recent_reviews' => $service->reviews->map(fn ($review) => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'customer_name' => $review->customer->name,
                    'created_at' => $review->created_at->toIso8601String(),
                ])->values(),
            ])
            ->values();

        $activeVouchers = $request->user()
            ->activeVouchers()
            ->get()
            ->map(fn (UserVoucher $v) => [
                'id' => $v->id,
                'code' => $v->code,
                'type' => $v->type,
                'value' => $v->value,
                'expires_at' => $v->expires_at?->format('M d, Y'),
            ])
            ->values();

        return Inertia::render('Checkout', [
            'services' => $services,
            'active_vouchers' => $activeVouchers,
        ]);
    }
}
