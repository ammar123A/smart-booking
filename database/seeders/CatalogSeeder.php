<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServicePrice;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $tz = (string) config('app.timezone', 'UTC');

        // Service 1: Consultation
        $consultation = Service::query()->firstOrCreate(
            ['name' => 'Consultation'],
            ['description' => 'Professional consultation service', 'active' => true]
        );

        ServicePrice::query()->firstOrCreate(
            ['service_id' => $consultation->id, 'name' => '30 minutes'],
            ['duration_min' => 30, 'amount' => 5000, 'currency' => 'MYR', 'active' => true]
        );

        ServicePrice::query()->firstOrCreate(
            ['service_id' => $consultation->id, 'name' => '60 minutes'],
            ['duration_min' => 60, 'amount' => 9000, 'currency' => 'MYR', 'active' => true]
        );

        // Service 2: Haircut
        $haircut = Service::query()->firstOrCreate(
            ['name' => 'Haircut'],
            ['description' => 'Professional haircut and styling service', 'active' => true]
        );

        ServicePrice::query()->firstOrCreate(
            ['service_id' => $haircut->id, 'name' => 'Standard'],
            ['duration_min' => 45, 'amount' => 3500, 'currency' => 'MYR', 'active' => true]
        );

        ServicePrice::query()->firstOrCreate(
            ['service_id' => $haircut->id, 'name' => 'Premium'],
            ['duration_min' => 60, 'amount' => 6000, 'currency' => 'MYR', 'active' => true]
        );

        // Service 3: Massage Therapy
        $massage = Service::query()->firstOrCreate(
            ['name' => 'Massage Therapy'],
            ['description' => 'Relaxing therapeutic massage', 'active' => true]
        );

        ServicePrice::query()->firstOrCreate(
            ['service_id' => $massage->id, 'name' => '60 minutes'],
            ['duration_min' => 60, 'amount' => 12000, 'currency' => 'MYR', 'active' => true]
        );

        ServicePrice::query()->firstOrCreate(
            ['service_id' => $massage->id, 'name' => '90 minutes'],
            ['duration_min' => 90, 'amount' => 17000, 'currency' => 'MYR', 'active' => true]
        );

        // Service 4: Personal Training
        $training = Service::query()->firstOrCreate(
            ['name' => 'Personal Training'],
            ['description' => 'One-on-one fitness training session', 'active' => true]
        );

        ServicePrice::query()->firstOrCreate(
            ['service_id' => $training->id, 'name' => 'Single Session'],
            ['duration_min' => 60, 'amount' => 8000, 'currency' => 'MYR', 'active' => true]
        );

        // Service 5: Dental Checkup
        $dental = Service::query()->firstOrCreate(
            ['name' => 'Dental Checkup'],
            ['description' => 'Comprehensive dental examination', 'active' => true]
        );

        ServicePrice::query()->firstOrCreate(
            ['service_id' => $dental->id, 'name' => 'Standard Checkup'],
            ['duration_min' => 30, 'amount' => 15000, 'currency' => 'MYR', 'active' => true]
        );

        // Create Staff Members
        $alice = Staff::query()->firstOrCreate(
            ['name' => 'Alice'],
            ['email' => 'alice@example.com', 'timezone' => $tz, 'active' => true]
        );

        $bob = Staff::query()->firstOrCreate(
            ['name' => 'Bob'],
            ['email' => 'bob@example.com', 'timezone' => $tz, 'active' => true]
        );

        $charlie = Staff::query()->firstOrCreate(
            ['name' => 'Charlie'],
            ['email' => 'charlie@example.com', 'timezone' => $tz, 'active' => true]
        );

        $diana = Staff::query()->firstOrCreate(
            ['name' => 'Diana'],
            ['email' => 'diana@example.com', 'timezone' => $tz, 'active' => true]
        );

        $ethan = Staff::query()->firstOrCreate(
            ['name' => 'Ethan'],
            ['email' => 'ethan@example.com', 'timezone' => $tz, 'active' => true]
        );

        $fiona = Staff::query()->firstOrCreate(
            ['name' => 'Fiona'],
            ['email' => 'fiona@example.com', 'timezone' => $tz, 'active' => true]
        );

        // Assign staff to services
        $consultation->staff()->syncWithoutDetaching([$alice->id, $bob->id, $charlie->id]);
        $haircut->staff()->syncWithoutDetaching([$diana->id, $fiona->id]);
        $massage->staff()->syncWithoutDetaching([$ethan->id, $fiona->id]);
        $training->staff()->syncWithoutDetaching([$charlie->id, $ethan->id]);
        $dental->staff()->syncWithoutDetaching([$alice->id, $bob->id]);

        // Create schedules for all staff
        $allStaff = [$alice, $bob, $charlie, $diana, $ethan, $fiona];

        foreach ($allStaff as $index => $staff) {
            if ($index < 3) {
                // Alice, Bob, Charlie: Mon-Fri 09:00-17:00
                foreach ([1, 2, 3, 4, 5] as $dow) {
                    StaffSchedule::query()->firstOrCreate(
                        [
                            'staff_id' => $staff->id,
                            'day_of_week' => $dow,
                            'start_time' => '09:00:00',
                            'end_time' => '17:00:00',
                        ],
                        [
                            'timezone' => $staff->timezone,
                            'active' => true,
                        ]
                    );
                }
            } else {
                // Diana, Ethan, Fiona: Mon-Fri 10:00-18:00
                foreach ([1, 2, 3, 4, 5] as $dow) {
                    StaffSchedule::query()->firstOrCreate(
                        [
                            'staff_id' => $staff->id,
                            'day_of_week' => $dow,
                            'start_time' => '10:00:00',
                            'end_time' => '18:00:00',
                        ],
                        [
                            'timezone' => $staff->timezone,
                            'active' => true,
                        ]
                    );
                }
                
                // Add Saturday schedule for Diana, Ethan, Fiona
                StaffSchedule::query()->firstOrCreate(
                    [
                        'staff_id' => $staff->id,
                        'day_of_week' => 6,
                        'start_time' => '10:00:00',
                        'end_time' => '14:00:00',
                    ],
                    [
                        'timezone' => $staff->timezone,
                        'active' => true,
                    ]
                );
            }
        }
    }
}
