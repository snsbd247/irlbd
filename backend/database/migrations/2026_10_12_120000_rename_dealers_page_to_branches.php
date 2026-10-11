<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * IR Limited has two branches (Dhaka head office, Gazipur factory) and no dealers:
 * the seeded "Dealers & Distributors" page becomes "Our Branches" (/branches).
 * Only rows still holding the seeded English text are changed, so anything an editor
 * already edited stays as it is. Old Bengali translations of the changed fields are
 * removed; BanglaContentSeeder adds the new ones.
 */
return new class extends Migration
{
    public function up(): void
    {
        $forget = function (string $type, int $id, array $fields) {
            DB::table('translations')->where('translatable_type', $type)->where('translatable_id', $id)->whereIn('field', $fields)->delete();
        };

        $page = DB::table('pages')->where('slug', 'dealers')->where('title', 'Dealers & Distributors')->first();
        if ($page && ! DB::table('pages')->where('slug', 'branches')->exists()) {
            DB::table('pages')->where('id', $page->id)->update([
                'title' => 'Our Branches', 'slug' => 'branches', 'hero_title' => 'Our Branches',
                'hero_subtitle' => 'Visit our head office in Dhaka or our factory in Gazipur',
                'meta_description' => 'IR Limited branches: head office in Dhanmondi, Dhaka and factory in BSCIC Industrial Estate, Konabari, Gazipur.',
                'updated_at' => now(),
            ]);
            $forget('Page', $page->id, ['title', 'hero_title', 'hero_subtitle', 'meta_description']);

            foreach (DB::table('page_sections')->where('page_id', $page->id)->where('title', 'Find a Dealer Near You')->get() as $section) {
                DB::table('page_sections')->where('id', $section->id)->update(['eyebrow' => 'Visit Us', 'title' => 'Our Branches', 'updated_at' => now()]);
                $forget('PageSection', $section->id, ['eyebrow', 'title']);
            }
            foreach (DB::table('menu_items')->where('page_id', $page->id)->where('label', 'Dealers & Distributors')->get() as $item) {
                DB::table('menu_items')->where('id', $item->id)->update(['label' => 'Our Branches', 'updated_at' => now()]);
                $forget('MenuItem', $item->id, ['label']);
            }
        }

        foreach ([
            ['IR Limited Head Office (Direct Sales)', 'IR Limited Head Office', 'head_office'],
            ['IR Limited Factory Sales Office', 'IR Limited Factory', 'factory'],
        ] as [$old, $new, $type]) {
            foreach (DB::table('dealers')->where('name', $old)->get() as $dealer) {
                DB::table('dealers')->where('id', $dealer->id)->update(['name' => $new, 'type' => $type, 'updated_at' => now()]);
                $forget('Dealer', $dealer->id, ['name']);
            }
        }
    }

    public function down(): void
    {
        // Content change only; nothing to undo structurally.
    }
};
