<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckoutController
{
    public function __invoke(Request $request)
    {
        $services = Service::query()
            ->where('active', true)
            ->with(['prices' => fn ($q) => $q->where('active', true)->orderBy('amount')->orderBy('duration_min')->orderBy('id')])
            ->orderBy('name')
            ->get()
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'prices' => $service->prices->map(fn ($price) => [
                    'id' => $price->id,
                    'name' => $price->name,
                    'duration_min' => (int) $price->duration_min,
                    'amount' => (int) $price->amount,
                    'currency' => (string) $price->currency,
                ])->values(),
            ])
            ->values();

        return Inertia::render('Checkout', [
            'services' => $services,
        ]);
    }
}
