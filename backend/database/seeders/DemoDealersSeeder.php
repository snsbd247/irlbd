<?php

namespace Database\Seeders;

use App\Models\Dealer;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Fills the dealer finder so the page is complete before real dealers are known:
 * the company's own head office and factory (real) plus three clearly marked demo
 * entries that use the COMPANY's phone/email (never invented numbers). Runs only
 * when no dealers exist, then publishes the Dealers page. Delete the "(Demo)" rows
 * once real dealers are added.
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
            ['IR Limited Head Office (Direct Sales)', 'distributor', 'Dhaka', 'House#40, Road#27, 4th Floor, Dhaka Bank Building, Dhanmondi, Dhaka'],
            ['IR Limited Factory Sales Office', 'distributor', 'Gazipur', 'Plot # A 30-31, A 22-23, BSCIC Industrial Estate, Konabari, Gazipur - 1346'],
            ['Sample Dealer – Chattogram (Demo)', 'dealer', 'Chattogram', 'Demo listing. Call our head office for the nearest dealer in Chattogram.'],
            ['Sample Dealer – Sylhet (Demo)', 'dealer', 'Sylhet', 'Demo listing. Call our head office for the nearest dealer in Sylhet.'],
            ['Sample Agent – Khulna (Demo)', 'agent', 'Khulna', 'Demo listing. Call our head office for the nearest agent in Khulna.'],
        ];

        foreach ($rows as $i => [$name, $type, $district, $address]) {
            Dealer::create([
                'name' => $name, 'type' => $type, 'district' => $district, 'address' => $address,
                'phone' => $phone, 'email' => $email, 'sort_order' => $i + 1, 'is_active' => true,
            ]);
        }

        Page::where('slug', 'dealers')->where('status', 'draft')->update(['status' => 'published', 'published_at' => now()]);
    }
}
