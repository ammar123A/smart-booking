<?php

namespace Tests\Feature\Admin;

use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffScheduleBulkTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_schedule_rejects_overlaps_and_keeps_existing(): void
    {
        Role::findOrCreate('admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $staff = Staff::create([
            'name' => 'S1',
            'timezone' => 'UTC',
            'active' => true,
        ]);

        StaffSchedule::create([
            'staff_id' => $staff->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'timezone' => 'UTC',
            'active' => true,
        ]);

        $this->assertSame(1, $staff->schedules()->count());

        $response = $this->actingAs($admin)
            ->from(route('admin.staff.edit', $staff))
            ->put(route('admin.staff.schedules.bulk', $staff), [
                'schedules' => [
                    [
                        'day_of_week' => 1,
                        'start_time' => '09:00',
                        'end_time' => '10:00',
                        'timezone' => 'UTC',
                        'active' => true,
                    ],
                    [
                        'day_of_week' => 1,
                        'start_time' => '09:30',
                        'end_time' => '11:00',
                        'timezone' => 'UTC',
                        'active' => true,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.staff.edit', $staff));
        $response->assertSessionHasErrors(['schedules']);

        $this->assertSame(1, $staff->schedules()->count());
    }

    public function test_bulk_schedule_accepts_non_overlapping_ranges(): void
    {
        Role::findOrCreate('admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $staff = Staff::create([
            'name' => 'S1',
            'timezone' => 'UTC',
            'active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.staff.schedules.bulk', $staff), [
                'schedules' => [
                    [
                        'day_of_week' => 1,
                        'start_time' => '09:00',
                        'end_time' => '10:00',
                        'timezone' => 'UTC',
                        'active' => true,
                    ],
                    [
                        'day_of_week' => 1,
                        'start_time' => '10:00',
                        'end_time' => '11:00',
                        'timezone' => 'UTC',
                        'active' => true,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.staff.edit', $staff));
        $response->assertSessionHasNoErrors();

        $this->assertSame(2, $staff->schedules()->count());
    }
}
