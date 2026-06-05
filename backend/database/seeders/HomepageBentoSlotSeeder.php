<?php

namespace Database\Seeders;

use App\Models\HomepageBentoSlot;
use Illuminate\Database\Seeder;

class HomepageBentoSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $slots = [
            [
                'slot_key' => 'slot_1',
                'title' => 'Sacred Geometry Series',
                'subtitle' => null,
                'image' => null,
                'icon' => null,
                'badge' => 'Signature',
                'theme' => 'gradient',
                'link_type' => 'custom',
                'custom_url' => '/products',
            ],
            [
                'slot_key' => 'slot_2',
                'title' => 'The Sterling Table',
                'subtitle' => 'Refined utility for the home.',
                'image' => null,
                'icon' => null,
                'badge' => null,
                'theme' => 'light',
                'link_type' => 'custom',
                'custom_url' => '/products',
            ],
            [
                'slot_key' => 'slot_3',
                'title' => 'Bespoke Iron',
                'subtitle' => 'Custom architectural elements hand-forged for lasting legacy.',
                'image' => null,
                'icon' => '⚒',
                'badge' => null,
                'theme' => 'light',
                'link_type' => 'custom',
                'custom_url' => '/products',
            ],
            [
                'slot_key' => 'slot_4',
                'title' => 'Aged Bronze Altar',
                'subtitle' => 'Limited release available for discerning collectors.',
                'image' => null,
                'icon' => null,
                'badge' => 'New Arrival',
                'theme' => 'dark',
                'link_type' => 'custom',
                'custom_url' => '/products',
            ],
        ];

        foreach ($slots as $slot) {
            HomepageBentoSlot::updateOrCreate(
                ['slot_key' => $slot['slot_key']],
                $slot
            );
        }
    }
}
