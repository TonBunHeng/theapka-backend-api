<?php

namespace Database\Seeders;

use App\Enums\GiftEntryType;
use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Enums\WeddingStatus;
use App\Models\Announcement;
use App\Models\GiftRecord;
use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\Invitation;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\Template;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingDetail;
use App\Models\Wish;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure test users exist with flexible passwords
        $password = Hash::make('Password123!');
        $altPassword = Hash::make('password123');

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@theapka.com'],
            ['name' => 'TheapKa Super Admin', 'phone' => '+85512000001', 'password' => $password, 'is_active' => true, 'email_verified_at' => now()]
        );
        $superAdmin->syncRoles([RoleName::SUPER_ADMIN->value]);

        $superAdminAlt = User::firstOrCreate(
            ['email' => 'super@theapka.test'],
            ['name' => 'Vireak Dara (Super Admin)', 'phone' => '+85512000011', 'password' => $altPassword, 'is_active' => true, 'email_verified_at' => now()]
        );
        $superAdminAlt->syncRoles([RoleName::SUPER_ADMIN->value]);

        $staffAdmin = User::firstOrCreate(
            ['email' => 'admin@theapka.com'],
            ['name' => 'TheapKa Admin', 'phone' => '+85512000002', 'password' => $password, 'is_active' => true, 'email_verified_at' => now()]
        );
        $staffAdmin->syncRoles([RoleName::ADMIN->value]);

        $staffAdminAlt = User::firstOrCreate(
            ['email' => 'admin@theapka.test'],
            ['name' => 'Sophea Pich (Staff Admin)', 'phone' => '+85512000012', 'password' => $altPassword, 'is_active' => true, 'email_verified_at' => now()]
        );
        $staffAdminAlt->syncRoles([RoleName::ADMIN->value]);

        $couple = User::firstOrCreate(
            ['email' => 'couple@theapka.com'],
            ['name' => 'Sokha & Bopha', 'phone' => '+85512000003', 'password' => $password, 'is_active' => true, 'email_verified_at' => now()]
        );
        $couple->syncRoles([RoleName::USER->value]);

        $coupleAlt = User::firstOrCreate(
            ['email' => 'sovann@theapka.com'],
            ['name' => 'Sovann & Daline', 'phone' => '+85512000013', 'password' => $altPassword, 'is_active' => true, 'email_verified_at' => now()]
        );
        $coupleAlt->syncRoles([RoleName::USER->value]);

        // 2. Primary Couple Wedding (Sokha & Bopha)
        $wedding = Wedding::withoutGlobalScopes()->firstOrCreate(
            ['slug' => 'sokha-bopha'],
            [
                'owner_id' => $couple->id,
                'title' => 'ពិធីមង្គលការ សុខា & បុប្ផា',
                'wedding_date' => now()->addDays(45)->format('Y-m-d'),
                'venue_name' => 'NagaWorld Hotel & Entertainment Complex',
                'venue_address' => 'Samdach Techo Hun Sen Park, Phnom Penh, Cambodia',
                'venue_map_url' => 'https://maps.google.com/?q=Nagaworld+Hotel+Phnom+Penh',
                'timezone' => 'Asia/Phnom_Penh',
                'status' => WeddingStatus::PUBLISHED->value,
                'cover_image_url' => 'https://images.unsplash.com/photo-1519741497674-611481863552?w=1200&auto=format&fit=crop&q=80',
                'settings' => ['allow_wishes' => true, 'show_qr' => true],
            ]
        );

        // Also assign this wedding to coupleAlt if coupleAlt has no wedding
        if (! $coupleAlt->currentWedding()) {
            Wedding::withoutGlobalScopes()->firstOrCreate(
                ['slug' => 'sovann-daline'],
                [
                    'owner_id' => $coupleAlt->id,
                    'title' => 'ពិធីមង្គលការ សុវណ្ណ & ដាលីន',
                    'wedding_date' => now()->addDays(60)->format('Y-m-d'),
                    'venue_name' => 'Premier Centre Sen Sok',
                    'venue_address' => 'Phnom Penh, Cambodia',
                    'timezone' => 'Asia/Phnom_Penh',
                    'status' => WeddingStatus::PUBLISHED->value,
                    'cover_image_url' => 'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?w=1200&auto=format&fit=crop&q=80',
                ]
            );
        }

        // Wedding Details
        WeddingDetail::firstOrCreate(
            ['wedding_id' => $wedding->id],
            [
                'groom_name' => 'ចាន់ សុខា',
                'groom_title' => 'កូនកំលោះ',
                'groom_parents' => 'លោក ចាន់ សុវណ្ណ & លោកស្រី គង់ ផល្លី',
                'bride_name' => 'កែវ បុប្ផា',
                'bride_title' => 'កូនក្រមុំ',
                'bride_parents' => 'លោក កែវ វិសាល & លោកស្រី អ៊ុំ ធីតា',
                'story' => 'ស្នេហាដែលបានចាប់ផ្តើមតាំងពីថ្នាក់សាកលវិទ្យាល័យ រហូតមកដល់ថ្ងៃជួបជុំគ្រួសារដ៏មានសេចក្តីសុខនេះ។',
                'welcome_message' => 'សូមគោរពអញ្ជើញឯកឧត្តម លោកជំទាវ លោក លោកស្រី អ្នកនាងកញ្ញា ចូលរួមជាអធិបតី និងប្រសិទ្ធពរជ័យក្នុងពិធីមង្គលការយើងខ្ញុំ។',
                'dress_code' => 'Traditional Khmer Attire / Formal Evening Suit',
                'contact_phones' => ['+855 12 345 678', '+855 98 765 432'],
                'custom_fields' => [
                    'groom_name_kh' => 'ចាន់ សុខា',
                    'groom_name_en' => 'Chan Sokha',
                    'bride_name_kh' => 'កែវ បុប្ផា',
                    'bride_name_en' => 'Keo Bopha',
                ],
            ]
        );

        // Schedules
        $schedules = [
            ['title' => 'ពិធីហែជំនូន (Groom Procession)', 'description' => 'ការដង្ហែជំនូនចូលរោងជ័យ', 'start_time' => '07:00:00', 'end_time' => '08:30:00', 'location' => 'Grand Ballroom Entrance', 'order' => 1],
            ['title' => 'ពិធីកាត់សក់បង្កក់សិរី (Hair Cutting Ceremony)', 'description' => 'សាច់ញាតិ និងមិត្តភក្តិកាត់សក់ប្រសិទ្ធពរ', 'start_time' => '08:30:00', 'end_time' => '09:30:00', 'location' => 'Ballroom Stage B', 'order' => 2],
            ['title' => 'ពិធីសំពះផ្ទឹម និងចងដៃ (Knot-Tying Ceremony)', 'description' => 'បង្វិលពពិល និងចងអំបោះក្រហមជូនពរ', 'start_time' => '09:30:00', 'end_time' => '10:45:00', 'location' => 'Main Stage', 'order' => 3],
            ['title' => 'ពិធីទទួលទានភោជនាហារពេលថ្ងៃត្រង់ (Lunch Feast)', 'description' => 'ទទួលទានអាហារថ្ងៃត្រង់ជុំគ្នា', 'start_time' => '11:30:00', 'end_time' => '13:30:00', 'location' => 'Dining Hall', 'order' => 4],
            ['title' => 'ពិធីជប់លៀងពេលល្ងាច (Evening Banquet Reception)', 'description' => 'ទទួលភ្ញៀវកិត្តិយស និងកាត់នំខេកមង្គលការ', 'start_time' => '17:30:00', 'end_time' => '21:30:00', 'location' => 'Grand Ballroom Hall A & B', 'order' => 5],
        ];

        foreach ($schedules as $sched) {
            Schedule::withoutGlobalScopes()->firstOrCreate(
                ['wedding_id' => $wedding->id, 'title' => $sched['title']],
                $sched
            );
        }

        // Guest Groups
        $groupFamily = GuestGroup::withoutGlobalScopes()->firstOrCreate(['wedding_id' => $wedding->id, 'name' => 'គ្រួសារសាច់ញាតិ (Family)'], ['order' => 1]);
        $groupFriends = GuestGroup::withoutGlobalScopes()->firstOrCreate(['wedding_id' => $wedding->id, 'name' => 'មិត្តភក្តិជិតស្និទ្ធ (Close Friends)'], ['order' => 2]);
        $groupColleagues = GuestGroup::withoutGlobalScopes()->firstOrCreate(['wedding_id' => $wedding->id, 'name' => 'មិត្តរួមការងារ (Colleagues)'], ['order' => 3]);
        $groupVIP = GuestGroup::withoutGlobalScopes()->firstOrCreate(['wedding_id' => $wedding->id, 'name' => 'ភ្ញៀវកិត្តិយស VIP (VIP Guests)'], ['order' => 4]);

        // Guests List (Realistic Khmer Guests)
        $guestsData = [
            ['name' => 'ឯកឧត្តម ជា ស៊ីវុត្ថា', 'phone' => '+85512800101', 'email' => 'cheasivutha@gov.kh', 'side' => 'groom', 'seats' => 2, 'group_id' => $groupVIP->id, 'rsvp_status' => 'attending'],
            ['name' => 'លោកជំទាវ ម៉ៅ ពិសិដ្ឋ', 'phone' => '+85512800102', 'email' => 'maopiseth@gov.kh', 'side' => 'bride', 'seats' => 2, 'group_id' => $groupVIP->id, 'rsvp_status' => 'attending'],
            ['name' => 'លោក ចាន់ រិទ្ធី', 'phone' => '+85512800201', 'email' => 'chanrithy@gmail.com', 'side' => 'groom', 'seats' => 4, 'group_id' => $groupFamily->id, 'rsvp_status' => 'attending'],
            ['name' => 'អ្នកស្រី កែវ សុគន្ធា', 'phone' => '+85512800202', 'email' => 'keosokunthea@gmail.com', 'side' => 'bride', 'seats' => 3, 'group_id' => $groupFamily->id, 'rsvp_status' => 'attending'],
            ['name' => 'លោក ហេង បូរ៉ា', 'phone' => '+85512800203', 'email' => 'hengbora@yahoo.com', 'side' => 'groom', 'seats' => 2, 'group_id' => $groupFamily->id, 'rsvp_status' => 'declined'],
            ['name' => 'កញ្ញា លឹម ស្រីនិច', 'phone' => '+85512800301', 'email' => 'limsreynich@gmail.com', 'side' => 'bride', 'seats' => 1, 'group_id' => $groupFriends->id, 'rsvp_status' => 'attending'],
            ['name' => 'លោក ពេជ្រ វណ្ណៈ', 'phone' => '+85512800302', 'email' => 'pichvannak@gmail.com', 'side' => 'groom', 'seats' => 2, 'group_id' => $groupFriends->id, 'rsvp_status' => 'attending'],
            ['name' => 'កញ្ញា ម៉េង ចរិយា', 'phone' => '+85512800303', 'email' => 'mengchariya@gmail.com', 'side' => 'bride', 'seats' => 1, 'group_id' => $groupFriends->id, 'rsvp_status' => 'pending'],
            ['name' => 'លោក សេង ដារ៉ូ', 'phone' => '+85512800401', 'email' => 'sengdaro@smart.com.kh', 'side' => 'groom', 'seats' => 2, 'group_id' => $groupColleagues->id, 'rsvp_status' => 'attending'],
            ['name' => 'អ្នកនាង គង់ សុម៉ាលី', 'phone' => '+85512800402', 'email' => 'kongsomaly@aba.com.kh', 'side' => 'bride', 'seats' => 2, 'group_id' => $groupColleagues->id, 'rsvp_status' => 'pending'],
            ['name' => 'លោក ជ័យ វិបុល', 'phone' => '+85512800403', 'email' => 'cheyvibul@company.com', 'side' => 'groom', 'seats' => 1, 'group_id' => $groupColleagues->id, 'rsvp_status' => 'attending'],
            ['name' => 'កញ្ញា ឈិន មុន្នីរ័ត្ន', 'phone' => '+85512800304', 'email' => 'chhinmonyroth@gmail.com', 'side' => 'bride', 'seats' => 2, 'group_id' => $groupFriends->id, 'rsvp_status' => 'attending'],
        ];

        $createdGuests = [];
        foreach ($guestsData as $idx => $g) {
            $guest = Guest::withoutGlobalScopes()->firstOrCreate(
                ['wedding_id' => $wedding->id, 'phone' => $g['phone']],
                [
                    'name' => $g['name'],
                    'email' => $g['email'],
                    'side' => $g['side'],
                    'seats' => $g['seats'],
                    'group_id' => $g['group_id'],
                    'token' => 'guest_token_' . str_pad((string) ($idx + 1), 6, '0', STR_PAD_LEFT),
                ]
            );
            $createdGuests[] = $guest;

            // Rsvp
            \App\Models\Rsvp::firstOrCreate(
                ['guest_id' => $guest->id, 'wedding_id' => $wedding->id],
                [
                    'status' => $g['rsvp_status'],
                    'attending_count' => $g['rsvp_status'] === 'attending' ? $g['seats'] : 0,
                    'responded_at' => $g['rsvp_status'] !== 'pending' ? now()->subDays(rand(1, 5)) : null,
                ]
            );
        }

        // Digital Invitation
        $template = Template::first();
        $invitation = Invitation::withoutGlobalScopes()->firstOrCreate(
            ['wedding_id' => $wedding->id],
            [
                'template_id' => $template?->id,
                'slug' => $wedding->slug,
                'title' => 'លិខិតអញ្ជើញមង្គលការ សុខា & បុប្ផា',
                'custom_css' => ':root { --primary-gold: #c59b27; --deep-maroon: #721c24; }',
                'content' => [
                    'theme' => 'khmer_royal_gold',
                    'quote' => 'ស្នេហាគឺជាការយោគយល់ និងការចែករំលែកនូវក្តីសុខរួមគ្នា',
                    'greeting' => 'សូមគោរពអញ្ជើញ',
                ],
                'music_url' => '/music/ភ្ជាប់និស្ស័យ.mp3',
                'view_count' => 142,
                'status' => 'published',
                'is_moderation_enabled' => false,
                'published_at' => now()->subDays(5),
            ]
        );

        // Invitation Sends
        foreach ($createdGuests as $cg) {
            \App\Models\InvitationSend::firstOrCreate(
                ['invitation_id' => $invitation->id, 'guest_id' => $cg->id, 'channel' => 'telegram'],
                [
                    'sent_at' => now()->subDays(rand(3, 7)),
                    'opened_at' => now()->subDays(rand(1, 2)),
                ]
            );
        }

        // Wishes
        $wishes = [
            ['guest_name' => 'លោក ចាន់ រិទ្ធី', 'message' => 'សូមជូនពរប្អូនទាំងពីរស្រឡាញ់គ្នារហូតដល់ចាស់កោងខ្នង រកស៊ីមានបាន ត្រជាក់ត្រជុំ!', 'is_visible' => true],
            ['guest_name' => 'កញ្ញា លឹម ស្រីនិច', 'message' => 'Congratulations to the most beautiful couple! Wishing you a lifetime of love and happiness!', 'is_visible' => true],
            ['guest_name' => 'លោក សេង ដារ៉ូ', 'message' => 'អបអរសាទរមិត្តសម្លាញ់! ជូនពរជួបតែសេចក្តីសុខ និងសំណាងល្អគ្រប់ប្រការ!', 'is_visible' => true],
        ];

        foreach ($wishes as $w) {
            Wish::withoutGlobalScopes()->firstOrCreate(
                ['wedding_id' => $wedding->id, 'message' => $w['message']],
                [
                    'sender_name' => $w['guest_name'],
                    'is_visible' => $w['is_visible'],
                ]
            );
        }

        // 3. Gift Ledger (ចំណងដៃ) — Append-Only Real Cambodian Records
        $khrGifts = [
            ['guest_name' => 'លោក ចាន់ រិទ្ធី', 'amount' => '400000.00', 'currency' => 'KHR', 'method' => 'cash', 'notes' => 'ចំណងដៃពីគ្រួសារបងប្រុស'],
            ['guest_name' => 'អ្នកស្រី កែវ សុគន្ធា', 'amount' => '400000.00', 'currency' => 'KHR', 'method' => 'khqr', 'notes' => 'ផ្ទេរតាម Bakong KHQR'],
            ['guest_name' => 'លោក ពេជ្រ វណ្ណៈ', 'amount' => '200000.00', 'currency' => 'KHR', 'method' => 'khqr', 'notes' => 'ចំណងដៃមិត្តវិទ្យាល័យ'],
            ['guest_name' => 'លោក ជ័យ វិបុល', 'amount' => '200000.00', 'currency' => 'KHR', 'method' => 'cash', 'notes' => 'ចំណងដៃស្រោមសំបុត្រ'],
            ['guest_name' => 'លោក ហេង បូរ៉ា', 'amount' => '200000.00', 'currency' => 'KHR', 'method' => 'khqr', 'notes' => 'ផ្ទេរជូនពរពីចម្ងាយ'],
        ];

        $usdGifts = [
            ['guest_name' => 'ឯកឧត្តម ជា ស៊ីវុត្ថា', 'amount' => '200.00', 'currency' => 'USD', 'method' => 'cash', 'notes' => 'ស្រោមសំបុត្រកិត្តិយស'],
            ['guest_name' => 'លោកជំទាវ ម៉ៅ ពិសិដ្ឋ', 'amount' => '200.00', 'currency' => 'USD', 'method' => 'cash', 'notes' => 'ស្រោមសំបុត្រកិត្តិយស'],
            ['guest_name' => 'កញ្ញា លឹម ស្រីនិច', 'amount' => '50.00', 'currency' => 'USD', 'method' => 'aba', 'notes' => 'ABA PayWay Digital Gift'],
            ['guest_name' => 'លោក សេង ដារ៉ូ', 'amount' => '100.00', 'currency' => 'USD', 'method' => 'cash', 'notes' => 'ចំណងដៃមិត្តរួមការងារ'],
            ['guest_name' => 'កញ្ញា ឈិន មុន្នីរ័ត្ន', 'amount' => '50.00', 'currency' => 'USD', 'method' => 'aba', 'notes' => 'Best wishes from Moniroth'],
        ];

        foreach ($khrGifts as $idx => $gift) {
            $g = $createdGuests[$idx] ?? null;
            GiftRecord::withoutGlobalScopes()->firstOrCreate(
                ['wedding_id' => $wedding->id, 'client_uuid' => "seed_khr_gift_{$idx}"],
                [
                    'guest_id' => $g?->id,
                    'giver_name' => $gift['guest_name'],
                    'amount' => $gift['amount'],
                    'currency' => 'KHR',
                    'method' => $gift['method'],
                    'notes' => $gift['notes'],
                    'entry_type' => GiftEntryType::GIFT->value,
                    'recorded_by' => $couple->id,
                ]
            );
        }

        foreach ($usdGifts as $idx => $gift) {
            $g = $createdGuests[$idx + 5] ?? null;
            GiftRecord::withoutGlobalScopes()->firstOrCreate(
                ['wedding_id' => $wedding->id, 'client_uuid' => "seed_usd_gift_{$idx}"],
                [
                    'guest_id' => $g?->id,
                    'giver_name' => $gift['guest_name'],
                    'amount' => $gift['amount'],
                    'currency' => 'USD',
                    'method' => $gift['method'],
                    'notes' => $gift['notes'],
                    'entry_type' => GiftEntryType::GIFT->value,
                    'recorded_by' => $couple->id,
                ]
            );
        }

        // 4. Active Subscription & Payment Record
        $plan = Plan::where('slug', 'premium')->first() ?: Plan::first();
        if ($plan) {
            $sub = Subscription::firstOrCreate(
                ['wedding_id' => $wedding->id, 'plan_id' => $plan->id],
                [
                    'status' => 'active',
                    'starts_at' => now()->subDays(10),
                    'ends_at' => now()->addDays(355),
                ]
            );

            Payment::firstOrCreate(
                ['wedding_id' => $wedding->id, 'reference' => 'PAY-SOKHA-001'],
                [
                    'subscription_id' => $sub->id,
                    'user_id' => $couple->id,
                    'amount' => $plan->price,
                    'currency' => 'USD',
                    'provider' => 'bakong_khqr',
                    'status' => PaymentStatus::PAID->value,
                    'paid_at' => now()->subDays(10),
                ]
            );
        }

        // 5. Additional Realistic Cambodian Weddings for Admin Platform View
        $extraWeddings = [
            ['title' => 'ពិធីមង្គលការ រិទ្ធី & សុជាតា', 'slug' => 'rithy-socheata', 'owner_name' => 'Chhorn Rithy', 'owner_email' => 'rithy@theapka.test', 'status' => 'published', 'venue' => 'Sokha Phnom Penh Hotel', 'date' => now()->addDays(20)->format('Y-m-d')],
            ['title' => 'ពិធីមង្គលការ វិសាល & គីមសួរ', 'slug' => 'visal-kimsour', 'owner_name' => 'Meas Visal', 'owner_email' => 'visal@theapka.test', 'status' => 'published', 'venue' => 'Sofitel Phnom Penh Phokeethra', 'date' => now()->addDays(35)->format('Y-m-d')],
            ['title' => 'ពិធីមង្គលការ កុសល & ធីតា', 'slug' => 'kosal-thyda', 'owner_name' => 'Ouk Kosal', 'owner_email' => 'kosal@theapka.test', 'status' => 'draft', 'venue' => 'Diamond Island Convention Center', 'date' => now()->addDays(75)->format('Y-m-d')],
            ['title' => 'ពិធីមង្គលការ ណារិទ្ធ & មុន្នីតា', 'slug' => 'narith-monita', 'owner_name' => 'Tep Narith', 'owner_email' => 'narith@theapka.test', 'status' => 'archived', 'venue' => 'Hyatt Regency Phnom Penh', 'date' => now()->subDays(15)->format('Y-m-d')],
            ['title' => 'ពិធីមង្គលការ វណ្ណារ៉ា & ចរិយា', 'slug' => 'vannara-chariya', 'owner_name' => 'Chea Vannara', 'owner_email' => 'vannara@theapka.test', 'status' => 'suspended', 'venue' => 'Himawari Hotel', 'date' => now()->addDays(50)->format('Y-m-d')],
        ];

        foreach ($extraWeddings as $ew) {
            $extraUser = User::firstOrCreate(
                ['email' => $ew['owner_email']],
                ['name' => $ew['owner_name'], 'phone' => '+85512' . rand(100000, 999999), 'password' => $altPassword, 'is_active' => true]
            );
            $extraUser->syncRoles([RoleName::USER->value]);

            $ewModel = Wedding::withoutGlobalScopes()->firstOrCreate(
                ['slug' => $ew['slug']],
                [
                    'owner_id' => $extraUser->id,
                    'title' => $ew['title'],
                    'wedding_date' => $ew['date'],
                    'venue_name' => $ew['venue'],
                    'status' => $ew['status'],
                    'cover_image_url' => 'https://images.unsplash.com/photo-1519741497674-611481863552?w=1200&auto=format&fit=crop&q=80',
                ]
            );

            WeddingDetail::firstOrCreate(
                ['wedding_id' => $ewModel->id],
                [
                    'groom_name' => explode(' & ', str_replace('ពិធីមង្គលការ ', '', $ew['title']))[0] ?? 'Groom',
                    'bride_name' => explode(' & ', str_replace('ពិធីមង្គលការ ', '', $ew['title']))[1] ?? 'Bride',
                    'story' => 'ពិធីមង្គលការបែបប្រពៃណីខ្មែរដ៏ស្រស់ស្អាត។',
                ]
            );

            // Add some guests
            for ($k = 1; $k <= 8; $k++) {
                $eg = Guest::withoutGlobalScopes()->firstOrCreate(
                    ['wedding_id' => $ewModel->id, 'token' => "guest_{$ewModel->id}_{$k}"],
                    [
                        'name' => "ភ្ញៀវកិត្តិយស {$k}",
                        'phone' => '+85512' . rand(200000, 899999),
                        'side' => $k % 2 === 0 ? 'groom' : 'bride',
                        'seats' => rand(1, 3),
                    ]
                );

                \App\Models\Rsvp::firstOrCreate(
                    ['guest_id' => $eg->id, 'wedding_id' => $ewModel->id],
                    [
                        'status' => $k % 3 === 0 ? 'attending' : ($k % 3 === 1 ? 'pending' : 'declined'),
                        'attending_count' => $k % 3 === 0 ? rand(1, 2) : 0,
                    ]
                );
            }

            // Subscription & Payment
            if ($ew['status'] === 'published' || $ew['status'] === 'archived') {
                $ewSub = null;
                if ($plan) {
                    $ewSub = Subscription::firstOrCreate(
                        ['wedding_id' => $ewModel->id, 'plan_id' => $plan->id],
                        [
                            'status' => 'active',
                            'starts_at' => now()->subDays(rand(10, 30)),
                            'ends_at' => now()->addDays(rand(300, 350)),
                        ]
                    );
                }

                Payment::firstOrCreate(
                    ['wedding_id' => $ewModel->id, 'reference' => "PAY-REF-{$ewModel->id}"],
                    [
                        'subscription_id' => $ewSub?->id,
                        'user_id' => $extraUser->id,
                        'amount' => 49.00,
                        'currency' => 'USD',
                        'provider' => 'bakong_khqr',
                        'status' => PaymentStatus::PAID->value,
                        'paid_at' => now()->subDays(rand(2, 20)),
                    ]
                );
            }
        }

        // 6. Announcements
        $announcements = [
            [
                'title' => 'Welcome to Wedding Season 2026! / រដូវកាលមង្គលការឆ្នាំ២០២៦',
                'content' => 'TheapKa Online wishes all lovely couples joy, harmony, and prosperity. Our digital invitation templates have been upgraded with high-resolution vector graphics!',
                'target_role' => 'all',
                'is_active' => true,
            ],
            [
                'title' => 'Bakong KHQR 2.0 Integration Live',
                'content' => 'Fast, zero-fee direct guest gifts via Bakong KHQR are now automatically enabled across all wedding guest invitation pages.',
                'target_role' => 'user',
                'is_active' => true,
            ],
            [
                'title' => 'Administrative System Notice: Scheduled Maintenance Window',
                'content' => 'Routine backup optimization is scheduled for the first Sunday of every month at 02:00 AM ICT.',
                'target_role' => 'admin',
                'is_active' => true,
            ],
        ];

        foreach ($announcements as $ann) {
            Announcement::firstOrCreate(
                ['title' => $ann['title']],
                [
                    'created_by' => $superAdmin->id,
                    'content' => $ann['content'],
                    'target_role' => $ann['target_role'],
                    'is_active' => $ann['is_active'],
                    'starts_at' => now()->subDays(5),
                    'ends_at' => now()->addDays(90),
                ]
            );
        }

        // 7. Support Tickets
        $tickets = [
            ['subject' => 'Need help adjusting table arrangement seats', 'priority' => 'medium', 'status' => 'open'],
            ['subject' => 'How to export gift ledger in Excel format?', 'priority' => 'low', 'status' => 'in_progress'],
            ['subject' => 'Payment verification for Gold Plan', 'priority' => 'high', 'status' => 'resolved'],
        ];

        foreach ($tickets as $idx => $t) {
            $ticket = SupportTicket::firstOrCreate(
                ['subject' => $t['subject']],
                [
                    'user_id' => $couple->id,
                    'priority' => $t['priority'],
                    'status' => $t['status'],
                    'assigned_to' => $staffAdmin->id,
                ]
            );

            TicketMessage::firstOrCreate(
                ['ticket_id' => $ticket->id, 'message' => "Hello, I have an inquiry regarding: {$t['subject']}"],
                [
                    'user_id' => $couple->id,
                ]
            );
        }

        // 8. Settings in settings table
        Setting::set('platform_name', 'TheapKa Online', 'general', true);
        Setting::set('default_lang', 'km', 'general', true);
        Setting::set('contact_email', 'support@theapka.com', 'general', true);
        Setting::set('contact_phone', '+855 12 888 999', 'general', true);
        Setting::set('max_guests_free', 50, 'general', true);
        Setting::set('max_guests_premium', 500, 'general', true);
        Setting::set('allow_khqr', true, 'general', true);
        Setting::set('allow_payway', true, 'general', true);

        Setting::set('session_lifetime', 120, 'security', false);
        Setting::set('session_timeout_minutes', 120, 'security', false);
        Setting::set('max_login_attempts', 5, 'security', false);
        Setting::set('enforce_2fa', false, 'security', false);
        Setting::set('require_2fa', false, 'security', false);
        Setting::set('password_min_length', 8, 'security', false);
        Setting::set('ip_allowlist', '', 'security', false);

        Setting::set('maintenance_mode', false, 'maintenance', true);
        Setting::set('maintenance_message', 'The service is currently undergoing scheduled maintenance.', 'maintenance', true);
        Setting::set('maintenance_message_km', 'ប្រព័ន្ធកំពុងស្ថិតក្រោមការថែទាំ។ សូមអភ័យទោសចំពោះការរំខាន។', 'maintenance', true);
        Setting::set('maintenance_message_en', 'The service is currently undergoing scheduled maintenance.', 'maintenance', true);
    }
}
