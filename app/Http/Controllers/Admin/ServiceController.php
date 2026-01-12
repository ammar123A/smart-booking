<?php

namespace App\Http\Controllers\Admin;

use App\Models\Service;
use App\Models\ServicePrice;
use App\Models\Staff;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceController
{
    public function index(Request $request)
    {
        $services = Service::query()
            ->withCount(['prices', 'staff'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Services/Index', [
            'services' => $services,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
        ]);

        Service::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'active' => (bool) ($validated['active'] ?? true),
        ]);

        return redirect()->route('admin.services.index');
    }

    public function edit(Service $service)
    {
        $service->load(['prices' => function ($q) {
            $q->orderBy('name');
        }, 'staff' => function ($q) {
            $q->orderBy('name');
        }]);

        $allStaff = Staff::query()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Services/Edit', [
            'service' => $service,
            'allStaff' => $allStaff,
            'assignedStaffIds' => $service->staff->pluck('id')->values(),
        ]);
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $service->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'active' => (bool) ($validated['active'] ?? false),
        ]);

        return redirect()->route('admin.services.edit', $service);
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return redirect()->route('admin.services.index');
    }

    public function syncStaff(Request $request, Service $service)
    {
        $validated = $request->validate([
            'staff_ids' => ['array'],
            'staff_ids.*' => ['integer', 'exists:staff,id'],
        ]);

        $service->staff()->sync($validated['staff_ids'] ?? []);

        return redirect()->route('admin.services.edit', $service);
    }

    public function storePrice(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_min' => ['required', 'integer', 'min:5', 'max:1440'],
            'amount' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $service->prices()->create([
            'name' => $validated['name'],
            'duration_min' => (int) $validated['duration_min'],
            'amount' => (int) $validated['amount'],
            'currency' => strtoupper($validated['currency']),
            'active' => (bool) ($validated['active'] ?? true),
        ]);

        return redirect()->route('admin.services.edit', $service);
    }

    public function updatePrice(Request $request, ServicePrice $price)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_min' => ['required', 'integer', 'min:5', 'max:1440'],
            'amount' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $price->update([
            'name' => $validated['name'],
            'duration_min' => (int) $validated['duration_min'],
            'amount' => (int) $validated['amount'],
            'currency' => strtoupper($validated['currency']),
            'active' => (bool) ($validated['active'] ?? false),
        ]);

        return redirect()->route('admin.services.edit', $price->service_id);
    }

    public function destroyPrice(ServicePrice $price)
    {
        $serviceId = $price->service_id;
        $price->delete();

        return redirect()->route('admin.services.edit', $serviceId);
    }
}
