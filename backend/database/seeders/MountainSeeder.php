<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mountain;

class MountainSeeder extends Seeder
{
    public function run(): void
    {
        $mountains = [
            [
                'name' => 'Mount Popa',
                'region' => 'Bagan',
                'difficulty' => 'Moderate',
                'distance' => '4.8 km',
                'duration' => '3–4 hrs',
                'elevation' => '1,518 m',
                'pace' => 'Steady',
                'image' => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1200&q=85',
                'description' => 'A memorable climb with sweeping views across the dry plains and a rewarding summit atmosphere.',
            ],
            [
                'name' => 'Mandalay Hill Trail',
                'region' => 'Mandalay',
                'difficulty' => 'Easy',
                'distance' => '2.6 km',
                'duration' => '1–2 hrs',
                'elevation' => '240 m',
                'pace' => 'Relaxed',
                'image' => 'https://images.unsplash.com/photo-1472396961693-142e6e269027?auto=format&fit=crop&w=1200&q=85',
                'description' => 'A relaxed uphill walk with city views, shaded paths and a beautiful sunset finish.',
            ],
            [
                'name' => 'Nat Ma Taung',
                'region' => 'Mandalay',
                'difficulty' => 'Hard',
                'distance' => '8.2 km',
                'duration' => '5–7 hrs',
                'elevation' => '3,053 m',
                'pace' => 'Fast',
                'image' => 'https://images.unsplash.com/photo-1464278533981-50106e6176b1?auto=format&fit=crop&w=1200&q=85',
                'description' => 'A challenging highland adventure for hikers who enjoy long trails and cooler mountain air.',
            ],
            [
                'name' => 'Popa Forest Walk',
                'region' => 'Bagan',
                'difficulty' => 'Easy',
                'distance' => '3.1 km',
                'duration' => '1.5 hrs',
                'elevation' => '650 m',
                'pace' => 'Relaxed',
                'image' => 'https://images.unsplash.com/photo-1448375240586-882707db888b?auto=format&fit=crop&w=1200&q=85',
                'description' => 'A gentle forest route with peaceful scenery, birdsong and plenty of spots to pause.',
            ],
            [
                'name' => 'Hlawga Nature Loop',
                'region' => 'Yangon',
                'difficulty' => 'Easy',
                'distance' => '3.7 km',
                'duration' => '2 hrs',
                'elevation' => 'Low',
                'pace' => 'Steady',
                'image' => 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=1200&q=85',
                'description' => 'A leafy escape close to the city, ideal for new hikers and weekend meet-ups.',
            ],
            [
                'name' => 'Pyin Oo Lwin Ridge',
                'region' => 'Mandalay',
                'difficulty' => 'Moderate',
                'distance' => '6.4 km',
                'duration' => '3 hrs',
                'elevation' => '1,070 m',
                'pace' => 'Steady',
                'image' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1200&q=85',
                'description' => 'Pine-scented paths and cool weather make this a refreshing day out with a group.',
            ],
        ];

        foreach ($mountains as $mountain) {
            Mountain::updateOrCreate(
                [
                    'name' => $mountain['name'],
                    'region' => $mountain['region'],
                ],
                array_merge($mountain, ['is_active' => true])
            );
        }
    }
}