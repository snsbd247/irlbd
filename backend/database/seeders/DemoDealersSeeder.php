<?php

namespace Database\Seeders;

use App\Models\Dealer;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Fills the branches page with the company's own branches: the Dhaka head office
 * and the Gazipur factory (IR Limited has no other branches). Runs only when no
 * dealers exist, then publishes the Dealers page.
 */
class DemoDealersSeeder extends Seeder
{
    public function run(): void
    {
        if (Dealer::exists()) {
            return;
        }

        $phone = Setting::get('contact_phone') ?: null;
        $email = Setting::get('contact_email') ?: null;

        $rows = [
            ['IR Limited Head Office', 'head_office', 'Dhaka', 'House#40, Road#27, 4th Floor, Dhaka Bank Building, Dhanmondi, Dhaka'],
            ['IR Limited Factory', 'factory', 'Gazipur', 'Plot # A 30-31, A 22-23, BSCIC Industrial Estate, Konabari, Gazipur - 1346'],
        ];

        foreach ($rows as $i => [$name, $type, $district, $address]) {
            Dealer::create([
                'name' => $name, 'type' => $type, 'district' => $district, 'address' => $address,
                'phone' => $phone, 'email' => $email, 'sort_order' => $i + 1, 'is_active' => true,
            ]);
        }

        Page::whereIn('slug', ['branches', 'dealers'])->where('status', 'draft')->update(['status' => 'published', 'published_at' => now()]);
    }
}
