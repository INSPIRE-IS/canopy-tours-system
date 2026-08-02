<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingFee;
use App\Models\BookingInfo;
use App\Models\BookingInventory;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use App\Models\BookingVoucher;
use App\Models\BookingVoucherItem;
use App\Models\Closeout;
use App\Models\CloseoutDayTimeSlot;
use App\Models\Customer;
use App\Models\CustomerManifest;
use App\Models\Discount;
use App\Models\DiscountService;
use App\Models\Inventory;
use App\Models\InventoryTimeSlot;
use App\Models\InventoryTypeDay;
use Illuminate\Console\Command;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Season;
use App\Models\ServiceField;
use App\Models\ServiceFieldValue;
use App\Models\Service;
use App\Models\Team;
use App\Models\User;
use App\Models\TypeDay;
use App\Models\TypePayOption;
use App\Models\TypePayment;
use App\Models\TypeBooking;
use App\Models\TypeAffiliate;
use App\Models\TypeCategory;
use App\Models\TypeData;
use App\Models\TypeFile;
use App\Models\TypeGroup;
use App\Models\TypeInput;
use App\Models\File;
use App\Models\Gateway;
use App\Models\InvoiceTemplate;
use App\Models\Lookup;
use App\Models\LookupItem;
use App\Models\Promotion;
use App\Services\QuestionnaireSeeder;
use App\Models\StatusPayment;
use App\Models\TypeAlert;
use App\Models\TypeConfirmation;
use App\Models\TypeGender;
use App\Models\TypeInvoice;
use App\Models\TypeLocation;
use App\Models\TypeReferral;
use App\Models\VatRate;
use App\Models\Wholesaler;
use App\Models\WholesalerRate;
use Illuminate\Support\Facades\DB;
use Mockery\Matcher\Type;
use Str;

class SeedCoreData extends Command
{
    protected $signature = 'seed:core-data'; // php artisan seed:core-data
    protected $description = 'Create or update core seed data for permissions, roles, etc.';

    public function handle()
    {
        $this->info('Seeding core data...');

        // TypeDays
        $typeDays = [
            ['id' => 1, 'name' => 'm'],
            ['id' => 2, 'name' => 't'],
            ['id' => 3, 'name' => 'w'],
            ['id' => 4, 'name' => 't'],
            ['id' => 5, 'name' => 'f'],
            ['id' => 6, 'name' => 's'],
            ['id' => 7, 'name' => 's'],
        ];
        foreach ($typeDays as $day) {
            TypeDay::updateOrCreate(['id' => $day['id']], $day);
        }

        // TypePayOptions
        $payOptions = [
            ['id' => 1, 'name' => 'Pay Offline'],
            ['id' => 2, 'name' => 'Pay OnLine'],
            ['id' => 3, 'name' => 'Split Payments'],
        ];
        foreach ($payOptions as $opt) {
            TypePayOption::updateOrCreate(['id' => $opt['id']], $opt);
        }

        // StatusPayments
        $statusPayments = [
            ['id' => 1, 'name' => 'pending'],
            ['id' => 2, 'name' => 'paid'],
            ['id' => 3, 'name' => 'failed'],
            ['id' => 4, 'name' => 'cancelled'],
        ];
        foreach ($statusPayments as $opt) {
            StatusPayment::updateOrCreate(['id' => $opt['id']], $opt);
        }

        // Gateways
        $gateways = [
            ['id' => 1, 'name' => 'PayFast'],
            ['id' => 2, 'name' => 'Manual'],
            ['id' => 3, 'name' => 'Stripe'],
        ];
        foreach ($gateways as $opt) {
            Gateway::updateOrCreate(['id' => $opt['id']], $opt);
        }

        // TypePayments
        $typePayments = [
            ['id' => 1, 'name' => 'Cash'],
            ['id' => 3, 'name' => 'Credit Card'],
            ['id' => 5, 'name' => 'Direct Deposit / EFT'],
            ['id' => 6, 'name' => 'Masterpass'],
            ['id' => 7, 'name' => 'Paypal'],
            ['id' => 8, 'name' => 'Snapscan'],
            ['id' => 9, 'name' => 'Zapper'],
            ['id' => 10, 'name' => 'Voucher'],
        ];
        foreach ($typePayments as $payment) {
            TypePayment::updateOrCreate(['id' => $payment['id']], $payment);
        }
        TypePayment::whereIn('id', [2, 4])->delete(); // Legacy: Cheque/Debit Card are no longer offered.

        $lookups = [
            [
                'id' => 1,
                'name' => 'VAT Rates',
                'slug' => 'vat_rate',
            ],
            [
                'id' => 2,
                'name' => 'deposit_due',
                'slug' => 'deposit_due',
            ],
            [
                'id' => 3,
                'name' => 'due_today',
                'slug' => 'due_today',
            ],
            [
                'id' => 4,
                'name' => 'Settings',
                'slug' => 'settings',
            ],
        ];

        foreach ($lookups as $lookup) {
            Lookup::updateOrCreate(['id' => $lookup['id']], $lookup);
        }

        $lookup_items = [
            [
                'id' => 1,
                'slug' => 'vat_rate',
                'rate' => 0.15,
                'multiplier' => 1.15,
                'effective_from' => '2020-01-01',
                'is_default' => 1,
                'is_active' => 1,
                'team_id' => null,
                'lookup_id' => 1,
            ],
            [
                'id' => 2,
                'slug' => 'deposit_due',
                'rate' => 0.00,
                'multiplier' => 1.00,
                'effective_from' => '2020-01-01',
                'is_default' => 1,
                'is_active' => 1,
                'team_id' => null,
                'lookup_id' => 2,
            ],
            [
                'id' => 3,
                'slug' => 'due_today',
                'rate' => 0.00,
                'multiplier' => 1.00,
                'effective_from' => '2020-01-01',
                'is_default' => 1,
                'is_active' => 1,
                'team_id' => null,
                'lookup_id' => 3,
            ],
            [
                'id' => 4,
                'name' => 'Enable Pickup/Dropoff',
                'slug' => 'enable_pickup_dropoff',
                'rate' => null,
                'multiplier' => null,
                'value' => 0,
                'effective_from' => '2020-01-01',
                'is_default' => 1,
                'is_active' => 1,
                'team_id' => null,
                'lookup_id' => 4,
            ],
            [
                'id' => 5,
                'name' => 'Auto Send Invoice on Booking',
                'slug' => 'auto_send_invoice_on_booking',
                'rate' => null,
                'multiplier' => null,
                'value' => 0,
                'effective_from' => '2020-01-01',
                'is_default' => 1,
                'is_active' => 1,
                'team_id' => null,
                'lookup_id' => 4,
            ],
            [
                'id' => 6,
                'name' => 'Auto Send Quote on Booking',
                'slug' => 'auto_send_quote_on_booking',
                'rate' => null,
                'multiplier' => null,
                'value' => 0,
                'effective_from' => '2020-01-01',
                'is_default' => 1,
                'is_active' => 1,
                'team_id' => null,
                'lookup_id' => 4,
            ],
            [
                'id' => 7,
                'name' => 'Enable Assign Staff',
                'slug' => 'enable_assign_staff',
                'rate' => null,
                'multiplier' => null,
                'value' => 0,
                'effective_from' => '2020-01-01',
                'is_default' => 1,
                'is_active' => 1,
                'team_id' => null,
                'lookup_id' => 4,
            ],
            [
                'id' => 8,
                'name' => 'Show Send Invoice Checkbox',
                'slug' => 'show_send_invoice_checkbox',
                'rate' => null,
                'multiplier' => null,
                'value' => 1,
                'effective_from' => '2020-01-01',
                'is_default' => 1,
                'is_active' => 1,
                'team_id' => null,
                'lookup_id' => 4,
            ],
            [
                'id' => 9,
                'name' => 'Show Send Quote Checkbox',
                'slug' => 'show_send_quote_checkbox',
                'rate' => null,
                'multiplier' => null,
                'value' => 1,
                'effective_from' => '2020-01-01',
                'is_default' => 1,
                'is_active' => 1,
                'team_id' => null,
                'lookup_id' => 4,
            ],
            [
                'id' => 10,
                'name' => 'Show Booking Information',
                'slug' => 'show_booking_information',
                'rate' => null,
                'multiplier' => null,
                'value' => 1,
                'effective_from' => '2020-01-01',
                'is_default' => 1,
                'is_active' => 1,
                'team_id' => null,
                'lookup_id' => 4,
            ],
        ];

        foreach ($lookup_items as $lookup_item) {
            LookupItem::updateOrCreate(['id' => $lookup_item['id']], $lookup_item);
        }

        foreach ([
            [
                'name' => 'Enable Guest Email Sending',
                'slug' => 'email_sending_enabled',
                'info' => 'Master site switch for sending guest emails. If disabled, automatic and manual guest emails are blocked.',
                'value' => 1,
            ],
            [
                'name' => 'Enable Manual Email Sending',
                'slug' => 'manual_email_sending_enabled',
                'info' => 'Allow staff to deliberately send guest emails such as payment links, invoices, check-in links, and guest messages.',
                'value' => 1,
            ],
            [
                'name' => 'Auto Send Payment Confirmation',
                'slug' => 'auto_send_payment_confirmation',
                'info' => 'Automatically send confirmation emails after frontend PayFast or simulated payment flows complete.',
                'value' => 0,
            ],
            [
                'name' => 'Auto Send Questionnaire Confirmation',
                'slug' => 'auto_send_questionnaire_confirmation',
                'info' => 'Automatically send indemnity or feedback confirmation emails after guests complete questionnaire flows.',
                'value' => 0,
            ],
        ] as $emailSetting) {
            LookupItem::updateOrCreate(
                [
                    'slug' => $emailSetting['slug'],
                    'lookup_id' => 4,
                    'team_id' => null,
                ],
                [
                    'name' => $emailSetting['name'],
                    'info' => $emailSetting['info'],
                    'rate' => null,
                    'multiplier' => null,
                    'value' => (int) ($emailSetting['value'] ?? 0),
                    'effective_from' => '2020-01-01',
                    'effective_to' => null,
                    'is_default' => 1,
                    'is_active' => 1,
                ]
            );
        }

        foreach ([
            [
                'name' => 'Franchise Royalty Percent',
                'slug' => 'franchise_royalty_percent',
                'info' => 'Hidden admin setting for Franchise Fees royalty percentage. Leave rate blank to use 10%.',
            ],
            [
                'name' => 'Franchise Marketing Percent',
                'slug' => 'franchise_marketing_percent',
                'info' => 'Hidden admin setting for Franchise Fees marketing percentage. Leave rate blank to use 2.5%.',
            ],
        ] as $reportSetting) {
            LookupItem::updateOrCreate(
                [
                    'slug' => $reportSetting['slug'],
                    'lookup_id' => 4,
                    'team_id' => null,
                ],
                [
                    'name' => $reportSetting['name'],
                    'info' => $reportSetting['info'],
                    'rate' => null,
                    'multiplier' => null,
                    'value' => null,
                    'effective_from' => '2020-01-01',
                    'effective_to' => null,
                    'is_default' => 1,
                    'is_active' => 1,
                ]
            );
        }


        // StatusBookings
        $type_bookings = [
            ['id' => 1, 'name' => 'quote'],
            ['id' => 2, 'name' => 'invoice'],
        ];
        foreach ($type_bookings as $status) {
            TypeBooking::updateOrCreate(['id' => $status['id']], $status);
        }

        $groupTypes = [
            ['id' => 1, 'name' => 'Individual'],
            ['id' => 2, 'name' => 'Couple'],
            ['id' => 3, 'name' => 'Family'],
            ['id' => 4, 'name' => 'Friends/Group'],
            ['id' => 5, 'name' => 'Corporate'],
            ['id' => 6, 'name' => 'Organised Group (school, hiking, etc)'],
        ];
        foreach ($groupTypes as $groupType) {
            TypeGroup::updateOrCreate(['id' => $groupType['id']], $groupType);
        }

        $affiliateTypes = [
            ['id' => 1, 'name' => 'Repeat Guest'],
            ['id' => 2, 'name' => 'Newspaper / magazine / brochure'],
            ['id' => 3, 'name' => 'Internet'],
            ['id' => 5, 'name' => 'Travel Agent'],
            ['id' => 6, 'name' => 'TV and radio'],
            ['id' => 7, 'name' => 'Word of mouth'],
            ['id' => 8, 'name' => 'Accommodation'],
            ['id' => 9, 'name' => 'Road sign'],
            ['id' => 10, 'name' => 'Other'],
        ];
        foreach ($affiliateTypes as $affiliateType) {
            TypeReferral::withTrashed()->updateOrCreate(
                ['id' => $affiliateType['id']],
                $affiliateType + ['deleted_at' => null]
            );
        }

        TypeReferral::where('name', 'Travel Guide')->delete();

        $type_categories = [
            ['id' => 1, 'name' => 'Details'],
            ['id' => 2, 'name' => 'Financial Details'],
            ['id' => 3, 'name' => 'Locations'],
            ['id' => 4, 'name' => 'Notifications'],
            ['id' => 5, 'name' => 'Shared Resources'],
        ];
        foreach ($type_categories as $type_category) {
            TypeCategory::updateOrCreate(['id' => $type_category['id']], $type_category);
        }
        // <ul class="dropdown-menu">
        // <li><a href="/services/11271/edit_details">Details</a></li>
        // <li><a href="/services/11271/edit_transport">Transport &amp; Locations</a></li>

        // <li><a href="/services/11271/edit_resources">Resources</a></li>
        // <li><a href="/services/11271/edit_financial">Financial Details</a></li>
        // <li><a href="/services/11271/edit_distribution">Distribution</a></li>
        // <li><a href="/services/11271/edit_seasons">Seasons</a></li>
        // <li><a href="/services/11271/edit_rates">Rates</a></li>
        // <li><a href="/services/11271/edit_inventories">Inventories</a>
        // </li><li><a href="/services/11271/edit_tickets">Tickets &amp; Manifest</a>
        // </li><li><a href="/services/11271/edit_check_in">Check-In</a></li>
        // <li><a href="/services/11271/confirmation_templates">Confirmation Templates</a></li>
        // <li><a href="/services/11271/option_groups">Service Options</a></li>
        // <li><a href="/services/11271/additional_informations/edit">Additional Information</a></li>
        // <li><a href="/services/11271/portal_featured/edit">Featured Services</a></li>
        // <li><a href="/services/11271/edit_integrated_distributors">Integrated Distributors</a></li></ul>

        // mostly html 5 input types
        $type_inputs = [
            ['id' => 1, 'name' => 'text'],       // <input type="text">
            ['id' => 2, 'name' => 'textarea'],   // <textarea>
            ['id' => 3, 'name' => 'number'],     // <input type="number">
            ['id' => 4, 'name' => 'date'],       // <input type="date">
            ['id' => 5, 'name' => 'time'],       // <input type="time">
            ['id' => 6, 'name' => 'checkbox'],   // <input type="checkbox">
            ['id' => 7, 'name' => 'radio'],   // <input type="checkbox">
            ['id' => 8, 'name' => 'select'],   // <input type="checkbox">
            ['id' => 9, 'name' => 'image'],   // <input type="checkbox">
            ['id' => 10, 'name' => 'signature'],   // <input type="checkbox">
            ['id' => 11, 'name' => 'continent'],   // <input type="checkbox">
            ['id' => 12, 'name' => 'country'],   // <input type="checkbox">
            ['id' => 13, 'name' => 'province'],   // <input type="checkbox">
            ['id' => 14, 'name' => 'lead guide'],   // <input type="checkbox">
            ['id' => 15, 'name' => 'safety guide'],   // <input type="checkbox">
            ['id' => 16, 'name' => 'group type'],   // <input type="checkbox">
            ['id' => 17, 'name' => 'referral type'],   // <input type="checkbox">
            ['id' => 18, 'name' => 'confirmation type'],   // <input type="checkbox">
            ['id' => 19, 'name' => 'age type'],   // <input type="checkbox">
        ]; //'text', 'textarea', 'number', 'select', 'radio', 'checkbox', 'date' 
        foreach ($type_inputs as $type_input) {
            TypeInput::updateOrCreate(['id' => $type_input['id']], $type_input);
        }
        // mostly database store types
        $type_datas = [
            ['id' => 1, 'name' => 'string'],
            ['id' => 2, 'name' => 'text'],
            ['id' => 3, 'name' => 'integer'],
            ['id' => 4, 'name' => 'float'],
            ['id' => 5, 'name' => 'date'],
            ['id' => 6, 'name' => 'time'],
            ['id' => 7, 'name' => 'boolean'],
            ['id' => 8, 'name' => 'json'],
        ]; // ['string', 'integer', 'boolean', 'date'],
        foreach ($type_datas as $type_data) {
            TypeData::updateOrCreate(['id' => $type_data['id']], $type_data);
        }

        $type_locations = [
            ['id' => 1, 'name' => 'pickup'],
            ['id' => 2, 'name' => 'start'],
            ['id' => 3, 'name' => 'dropoff'],
            ['id' => 4, 'name' => 'end'],
        ];
        foreach ($type_locations as $type_location) {
            TypeLocation::updateOrCreate(['id' => $type_location['id']], $type_location);
        }

        $type_confirmations = [
            ['id' => 1, 'name' => 'Yes'],
            ['id' => 2, 'name' => 'No'],
            ['id' => 3, 'name' => 'N/A'],
        ];
        foreach ($type_confirmations as $type_confirmation) {
            TypeConfirmation::updateOrCreate(['id' => $type_confirmation['id']], $type_confirmation);
        }

        $type_genders = [
            ['id' => 1, 'name' => 'Male'],
            ['id' => 2, 'name' => 'Female'],
            ['id' => 3, 'name' => 'N/A'],
        ];
        foreach ($type_genders as $type_gender) {
            TypeGender::updateOrCreate(['id' => $type_gender['id']], $type_gender);
        }

        $type_alerts = [
            ['id' => 1, 'name' => 'Payment Confirmation', 'slug' => 'payment_confirmation'],
            ['id' => 2, 'name' => 'Payment Overdue', 'slug' => 'payment_overdue'],
            ['id' => 3, 'name' => 'Booking Confirmation', 'slug' => 'booking_confirmation'],
            ['id' => 4, 'name' => 'Booking Arrival', 'slug' => 'booking_arrival'],
            ['id' => 5, 'name' => 'has Medical', 'slug' => 'has_medical'],
            ['id' => 6, 'name' => 'Manifest Completed', 'slug' => 'manifest_completed'],
            ['id' => 7, 'name' => 'Not Fit', 'slug' => 'not_fit'],
            ['id' => 8, 'name' => 'Not Agree', 'slug' => 'not_agree'],
            ['id' => 9, 'name' => 'Check-in Completed', 'slug' => 'check_in_completed'],
            ['id' => 10, 'name' => 'feedback Completed', 'slug' => 'feedback_completed'],
            ['id' => 11, 'name' => 'Pregnancy', 'slug' => 'pregnancy'],
        ];
        foreach ($type_alerts as $type_alert) {
            TypeAlert::updateOrCreate(['id' => $type_alert['id']], $type_alert);
        }


        $service_fields = [
            //['id' => 1, 'name' => 'header_pillbox_text','slug' => 'header_pillbox_text', 'type_category' => 1, 'order' => 1, 'type_data_id' => 1, 'type_input_id' => 1, 'required' => 1],
            ['id' => 1, 'name' => 'Description', 'slug' => 'description', 'type_category' => 1, 'order' => 1, 'type_data_id' => 2, 'type_input_id' => 2, 'required' => 1],
            ['id' => 2, 'name' => 'Condition Text', 'slug' => 'condition_text', 'type_category' => 1, 'order' => 2, 'type_data_id' => 2, 'type_input_id' => 2, 'required' => 1],
            ['id' => 3, 'name' => 'Activity Description', 'slug' => 'activity_description', 'type_category' => 1, 'order' => 3, 'type_data_id' => 2, 'type_input_id' => 2, 'required' => 1],
            ['id' => 4, 'name' => 'Arrangements', 'slug' => 'arrangements', 'type_category' => 1, 'order' => 4, 'type_data_id' => 2, 'type_input_id' => 2, 'required' => 1],
            ['id' => 5, 'name' => 'Requirements', 'slug' => 'requirements', 'type_category' => 1, 'order' => 5, 'type_data_id' => 2, 'type_input_id' => 2, 'required' => 1],
            ['id' => 6, 'name' => 'Inclusions', 'slug' => 'inclusions', 'type_category' => 1, 'order' => 6, 'type_data_id' => 2, 'type_input_id' => 2, 'required' => 1],
            ['id' => 7, 'name' => 'Exclusions', 'slug' => 'exclusions', 'type_category' => 1, 'order' => 7, 'type_data_id' => 2, 'type_input_id' => 2, 'required' => 1],
            ['id' => 8, 'name' => 'Notes', 'slug' => 'notes', 'type_category' => 1, 'order' => 8, 'type_data_id' => 2, 'type_input_id' => 2, 'required' => 1],
            ['id' => 9, 'name' => 'lat-lon start', 'slug' => 'lat_lon_start', 'type_category' => 3, 'order' => 9, 'type_data_id' => 1, 'type_input_id' => 1, 'required' => 1],
            ['id' => 10, 'name' => 'lat-long end', 'slug' => 'lat_long_end', 'type_category' => 3, 'order' => 10, 'type_data_id' => 1, 'type_input_id' => 1, 'required' => 1],
            //['id' => 12, 'name' => 'pillbox_condition_text','slug' => 'pillbox_condition_text', 'type_category' => 1, 'order' => 8, 'type_data_id' => 1, 'type_input_id' => 1, 'required' => 1],

        ];

        foreach ($service_fields as $service_field) {
            ServiceField::updateOrCreate(['id' => $service_field['id']], $service_field);
        }

        $type_files = [
            ['id' => 1, 'name' => 'logo'],
            ['id' => 2, 'name' => 'image'],
            ['id' => 3, 'name' => 'service'],
            ['id' => 4, 'name' => 'invoice'],
            ['id' => 5, 'name' => 'checkin'],
            ['id' => 6, 'name' => 'booking_voucher'],
            ['id' => 7, 'name' => 'signature'],
            ['id' => 8, 'name' => 'invoice_quote_icon'],
        ];
        foreach ($type_files as $type_file) {
            TypeFile::updateOrCreate(['id' => $type_file['id']], $type_file);
        }

        $wholesalers = [
            [
                'id' => 1,
                'name' => 'Wholesaler 1',
                'team_id' => 4,
            ],
            [
                'id' => 2,
                'name' => 'Wholesaler 2',
                'team_id' => 7,
            ],
            [
                'id' => 3,
                'name' => 'Wholesaler 3',
                'team_id' => 7,
            ],
            [
                'id' => 4,
                'name' => 'Wholesaler 4',
                'team_id' => 7,
            ],
        ];
        foreach ($wholesalers as $wholesaler) {
            Wholesaler::updateOrCreate(['id' => $wholesaler['id']], $wholesaler);
        }

        $services = [
            [
                'id' => 1,
                'tag' => 'Canopy Tour',
                'name' => 'Tsitsikamma Canopy Tour',
                'duration' => '02:00:00',
                'capacity' => 8,
                'allow_internal_capacity_override' => 1,
                'pillbox_conditions' => 'Book Now From 21 February 2025',
                'team_id' => 7,
            ],
            [
                'id' => 2,
                'tag' => 'Woodcutters',
                'name' => 'Tsitsikamma Woodcutters Journey',
                'duration' => '02:00:00',
                'capacity' => 4,
                'allow_internal_capacity_override' => 1,
                'pillbox_conditions' => 'Book Now From 21 February 2025',
                'team_id' => 7,
            ],
            [
                'id' => 3,
                'tag' => 'Guided Hikes',
                'name' => 'Tsitsikamma Guided Hikes Green route: 5.09km',
                'duration' => '02:00:00',
                'capacity' => 6,
                'allow_internal_capacity_override' => 1,
                'pillbox_conditions' => 'Book Now From 21 February 2025',
                'team_id' => 7,
            ],
            [
                'id' => 4,
                'tag' => 'Guided Hikes',
                'name' => 'Tsitsikamma Guided Hikes Red route: 7.78km',
                'duration' => '02:00:00',
                'capacity' => 5,
                'allow_internal_capacity_override' => 1,
                'pillbox_conditions' => 'Book Now From 21 February 2025',
                'team_id' => 7,
            ],
            [
                'id' => 5,
                'tag' => 'Guided Hikes',
                'name' => 'Tsitsikamma Guided Hikes Yellow route 8.09km',
                'duration' => '02:00:00',
                'capacity' => 10,
                'allow_internal_capacity_override' => 1,
                'pillbox_conditions' => 'Book Now From 21 February 2025',
                'team_id' => 7,
            ],
            [
                'id' => 6,
                'tag' => 'Team Building',
                'name' => 'Tsitsikamma Canopy Tour Photographic Scavenger Hunt',
                'duration' => '02:00:00',
                'capacity' => 20,
                'allow_internal_capacity_override' => 1,
                'pillbox_conditions' => 'Book Now From 21 February 2025',
                'team_id' => 7,
            ],
        ];
        foreach ($services as $service) {
            Service::updateOrCreate(['id' => $service['id']], $service);
        }


        $description = "Come and Experience a World of Diversity in One Forest A Unique Tsitsikamma Experience You'll Never Forget!!!";
        $condition_text = "";
        $activity = "Experience a World of Diversity in One Forest – Just a short drive from Knysna and 2 hours from Port-Elizabeth. Situated in the Garden of the Garden Route and heart of Tsitsikamma – Stormsriver Village. Tsitsikamma Canopy Tour is an Award-Winning Canopy Tour company that turns Tourism activities into Tourism experiences! Enjoy being suspended 30m above the forest floor on the Tsitsikamma Canopy Tour, cable sliding from ten platforms, tree to tree while discovering the spectacular and unique wilderness of the Tsitsikamma Indigenous forest. Surrounded by birdlife and giant trees like the Outeniqua Yellowwood, known to us as Uncle Sam who is over 600 years old! One can easily feel like a dwarf standing against it. The tour begins with a detailed safety briefing followed by a ‘kitting up’ session where a full-body harness, pulleys, and climbing equipment is issued and checked. A short drive into the Tsitsikamma forest and it is time to monkey around! From the start on the launch platform, a new world unfolds as one gently glides on a steel cable to the first tree platform. All groups are escorted through the forest canopy by a lead guide and a follow guide to ensure your safety as you slide from tree to tree. The platforms built high up in the giant Outeniqua Yellowwoods, provide an unbelievable view of this enchanting new world – any fear of heights is soon lost as you gather in your new surroundings.";
        $arrangements = "Duration: Open Ended<br>When: All Year Round<br>Where: Stormsriver Village, Tsitsikamma<br>Weather: All-weather activity. Bring appropriate clothing.<br>Things to Bring: All additional items";
        $requirements = "Minimum Age: 5 years<br>Minimum Height: 0.0m<br>Fitness Level: Average<br>Certification: Not Applicable";
        $inclusions = "Inclusions: Bottled water, safety gear, guide, certificate<br>Exclusions: All additional items";
        $exclusions = "Exclusions All additional items.";
        $notes = "Things to remember Guests who are differently abled or have unique needs must please book via telephone or email to share their specific requirements so that we can cater to their needs and ensure you enjoy your experience to the fullest. Discount vouchers and over-the-counter or telephonic promotions are not applicable to online bookings after full payment has been captured on an online booking. Tours include bottled water, gear, and safety equipment as well as guides Clients are advised that the tour includes a 600m hike out afterward. Moms to be: We apologize – this tour is not for you while you are pregnant. We suggest a weight restriction per person of approximately 120kg to 130kg – this restriction is not weight-related but rather imposed by the size of the harnesses we use and is based on the circumference of the straps around the body. ●Our minimum age is 5 years old, and the children can do the tour in tandem with the guide. The activity is an all-weather activity. Even in the rain – just bring something warm to wear and come and enjoy a rainforest experience with us. It is in the Tsitsikamma Indigenous forest and is always a magical experience. A rain jacket will be supplied. Comfortable clothes should be worn and any footwear that is secure on your feet and that will not fall if you are suspended in mid-air. (Closed shoes) Long/Braided or Styled Hair: Please note that a helmet will have to fit onto your head. Hair cannot be tied on top of your head obstructing the helmet to comfortably protect your head.";
        $lat_lon_start = "0,0";
        $lat_long_end = "1,1";

        $serviceFieldValues = [
            ['service_id' => 1, 'field_id' => 1, 'value' => $description,],
            ['service_id' => 1, 'field_id' => 2, 'value' => '1 Adult',],
            ['service_id' => 1, 'field_id' => 3, 'value' => $activity,],
            ['service_id' => 1, 'field_id' => 4, 'value' => $arrangements,],
            ['service_id' => 1, 'field_id' => 5, 'value' => $requirements,],
            ['service_id' => 1, 'field_id' => 6, 'value' => $inclusions,],
            ['service_id' => 1, 'field_id' => 7, 'value' => $exclusions,],
            ['service_id' => 1, 'field_id' => 8, 'value' => $notes,],
            ['service_id' => 1, 'field_id' => 9, 'value' => $lat_lon_start,],
            ['service_id' => 1, 'field_id' => 10, 'value' => $lat_long_end,],
            ['service_id' => 2, 'field_id' => 1, 'value' => $description,],
            ['service_id' => 2, 'field_id' => 2, 'value' => 'Requires a minimum of 2 People',],
            ['service_id' => 2, 'field_id' => 3, 'value' => $activity,],
            ['service_id' => 2, 'field_id' => 4, 'value' => $arrangements,],
            ['service_id' => 2, 'field_id' => 5, 'value' => $requirements,],
            ['service_id' => 2, 'field_id' => 6, 'value' => $inclusions,],
            ['service_id' => 2, 'field_id' => 7, 'value' => $exclusions,],
            ['service_id' => 2, 'field_id' => 8, 'value' => $notes,],
            ['service_id' => 2, 'field_id' => 9, 'value' => $lat_lon_start,],
            ['service_id' => 2, 'field_id' => 10, 'value' => $lat_long_end,],
            ['service_id' => 3, 'field_id' => 1, 'value' => $description,],
            ['service_id' => 3, 'field_id' => 2, 'value' => 'Book Now From 24 February 2025',],
            ['service_id' => 3, 'field_id' => 3, 'value' => $activity,],
            ['service_id' => 3, 'field_id' => 4, 'value' => $arrangements,],
            ['service_id' => 3, 'field_id' => 5, 'value' => $requirements,],
            ['service_id' => 3, 'field_id' => 6, 'value' => $inclusions,],
            ['service_id' => 3, 'field_id' => 7, 'value' => $exclusions,],
            ['service_id' => 3, 'field_id' => 8, 'value' => $notes,],
            ['service_id' => 3, 'field_id' => 9, 'value' => $lat_lon_start,],
            ['service_id' => 3, 'field_id' => 10, 'value' => $lat_long_end,],
            ['service_id' => 4, 'field_id' => 1, 'value' => $description,],
            ['service_id' => 4, 'field_id' => 2, 'value' => 'Requires a minimum of 2 People',],
            ['service_id' => 4, 'field_id' => 3, 'value' => $activity,],
            ['service_id' => 4, 'field_id' => 4, 'value' => $arrangements,],
            ['service_id' => 4, 'field_id' => 5, 'value' => $requirements,],
            ['service_id' => 4, 'field_id' => 6, 'value' => $inclusions,],
            ['service_id' => 4, 'field_id' => 7, 'value' => $exclusions,],
            ['service_id' => 4, 'field_id' => 8, 'value' => $notes,],
            ['service_id' => 4, 'field_id' => 9, 'value' => $lat_lon_start,],
            ['service_id' => 4, 'field_id' => 10, 'value' => $lat_long_end,],
        ];

        foreach ($serviceFieldValues as $serviceFieldValue) {
            ServiceFieldValue::updateOrCreate(
                [
                    'service_id' => $serviceFieldValue['service_id'],
                    'field_id' => $serviceFieldValue['field_id'],
                ],
                [
                    'value' => $serviceFieldValue['value'],
                    'team_id' => null,
                ]
            );
        }

        $files = [
            [
                "id" => 1,
                "table_name" => "services",
                "table_row_id" => "1",
                "file_name" => "card1.jpg",
                "file_type" => "image/jpeg",
                "path" => "services/1/VG1575vJQfQGmo6TFAk5gE0DknbgZ00qYNv5XR2Q.jpg",
                "expiry" => null,
                "team_id" => 7,
                "type_file_id" => 3,
                "booking_id" => null,
            ],
            [
                "id" => 2,
                "table_name" => "services",
                "table_row_id" => "6",
                "file_name" => "card1.jpg",
                "file_type" => "image/jpeg",
                "path" => "services/6/A8f8mauVaWW4C6REWtltV3WXnrK1u7bRqNqvDMo0.jpg",
                "expiry" => null,
                "team_id" => 7,
                "type_file_id" => 3,
                "booking_id" => null,
            ],
            [
                "id" => 3,
                "table_name" => "services",
                "table_row_id" => "3",
                "file_name" => "card3.jpg",
                "file_type" => "image/jpeg",
                "path" => "services/3/gSjvgA1MyCqhhcxqdVOFNu4AAX9hAUdocjhZuVLV.jpg",
                "expiry" => null,
                "team_id" => 7,
                "type_file_id" => 3,
                "booking_id" => null,
            ],
            [
                "id" => 4,
                "table_name" => "services",
                "table_row_id" => "4",
                "file_name" => "card4.jpg",
                "file_type" => "image/jpeg",
                "path" => "services/4/ahRAMEjm1Udj3OGILUq9YnzSz7KNsSVl3KDRIxUc.jpg",
                "expiry" => null,
                "team_id" => 7,
                "type_file_id" => 3,
                "booking_id" => null,
            ],
            [
                "id" => 5,
                "table_name" => "services",
                "table_row_id" => "5",
                "file_name" => "card5.jpg",
                "file_type" => "image/jpeg",
                "path" => "services/5/P9BTOjmG1DiaLDMfxU7S7Tg3AewtJBitOzP1TTyd.jpg",
                "expiry" => null,
                "team_id" => 7,
                "type_file_id" => 3,
                "booking_id" => null,
            ],
            [
                "id" => 6,
                "table_name" => "services",
                "table_row_id" => "2",
                "file_name" => "card6.jpg",
                "file_type" => "image/jpeg",
                "path" => "services/2/2y6BtQAwOEuAEt98H5p0Nuyab00mhWM4yqgJHJXW.jpg",
                "expiry" => null,
                "team_id" => 7,
                "type_file_id" => 3,
                "booking_id" => null,
            ],
            [
                "id" => 7,
                "table_name" => "teams",
                "table_row_id" => "7",
                "file_name" => "logo.png",
                "file_type" => "image/jpeg",
                "path" => "teams/7/KQheuzHHSpe3SM5xfX5O3MdI4gc3F2f41qd9a7v1.png",
                "expiry" => null,
                "team_id" => 7,
                "type_file_id" => 1,
                "booking_id" => null,
            ],
            [
                "id" => 8,
                "table_name" => "teams",
                "table_row_id" => "7",
                "file_name" => "main1.jpg",
                "file_type" => "image/jpeg",
                "path" => "teams/7/GtHzovMTDkSqnN4KbjVVLcBWozdNjg8UsY4s2CuF.jpg",
                "expiry" => null,
                "team_id" => 7,
                "type_file_id" => 2,
                "booking_id" => null,
            ]
        ];

        foreach ($files as $file) {
            File::updateOrCreate(
                [
                    'team_id'      => $file['team_id'],
                    'table_name'   => $file['table_name'],
                    'table_row_id' => $file['table_row_id'],
                    'file_name'    => $file['file_name'],
                    'file_type'    => $file['file_type'],
                    'type_file_id' => $file['type_file_id'],
                    'path'         => $file['path'],
                    'expiry'       => $file['expiry'],
                    'booking_id'   => $file['booking_id'],
                ],
            );
        }
        $this->info('Files seeded successfully.');


        $this->info('Services seeded successfully.');
        $seasons = [
            [
                'id' => 1,
                'name' => '2025-2026',
                'start_date' => '2025-02-01',
                'end_date' => '2026-02-28',
                'rake_rate_adult' => '600.00',
                'rake_rate_child' => '300.00',
                'rake_rate_guide' => '0.00',
                'allow_internal_price_override' => 1,
                'status' => 0,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'team_id' => 7,
                'service_id' => 1,
            ],
            [
                'id' => 2,
                'name' => '2025-2026',
                'start_date' => '2025-03-01',
                'end_date' => '2026-02-28',
                'rake_rate_adult' => '600.00',
                'rake_rate_child' => '300.00',
                'rake_rate_guide' => '0.00',
                'allow_internal_price_override' => 1,
                'status' => 0,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'team_id' => 7,
                'service_id' => 2,
            ],
            [
                'id' => 3,
                'name' => '2025-2026',
                'start_date' => '2025-03-01',
                'end_date' => '2026-02-28',
                'rake_rate_adult' => '600.00',
                'rake_rate_child' => '300.00',
                'rake_rate_guide' => '0.00',
                'allow_internal_price_override' => 1,
                'status' => 0,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'team_id' => 7,
                'service_id' => 3,
            ],
            [
                'id' => 4,
                'name' => '2025 mar-june',
                'start_date' => '2025-03-01',
                'end_date' => '2026-06-01',
                'rake_rate_adult' => '600.00',
                'rake_rate_child' => '300.00',
                'rake_rate_guide' => '0.00',
                'status' => 0,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'team_id' => 7,
                'service_id' => 4,
            ],
            [
                'id' => 5,
                'name' => '2025 feb-aug',
                'start_date' => '2025-02-01',
                'end_date' => '2025-08-28',
                'rake_rate_adult' => '600.00',
                'rake_rate_child' => '300.00',
                'rake_rate_guide' => '0.00',
                'allow_internal_price_override' => 1,
                'status' => 0,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'team_id' => 7,
                'service_id' => 5,
            ],
            [
                'id' => 6,
                'name' => '2025-2026',
                'start_date' => '2025-03-01',
                'end_date' => '2026-02-28',
                'rake_rate_adult' => '600.00',
                'rake_rate_child' => '300.00',
                'rake_rate_guide' => '0.00',
                'allow_internal_override' => 1,
                'status' => 0,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'team_id' => 7,
                'service_id' => 6,
            ],

            [
                'id' => 7,
                'name' => '2026-2027',
                'start_date' => '2026-03-01',
                'end_date' => '2027-02-28',
                'rake_rate_adult' => '600.00',
                'rake_rate_child' => '300.00',
                'rake_rate_guide' => '0.00',
                'capacity' => 6,
                'allow_internal_override' => 1,
                'status' => 0,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'team_id' => 7,
                'service_id' => 6,
            ],
        ];

        foreach ($seasons as $season) {
            $season['rake_rate_adult'] = floatval(str_replace('R', '', $season['rake_rate_adult']));
            $season['rake_rate_child'] = floatval(str_replace('R', '', $season['rake_rate_child']));
            $season['rake_rate_guide'] = floatval(str_replace('R', '', $season['rake_rate_guide']));

            Season::updateOrCreate(['id' => $season['id']], $season);
        }
        $this->info('Seasons seeded successfully.');

        $type_invoices = [
            [
                'id' => 1,
                'name' => 'Standard Invoice Inline',
                'slug' => 'std',
            ],
            [
                'id' => 2,
                'name' => 'Wholesale Invoice',
                'slug' => 'wholesale',
            ],
            [
                'id' => 3,
                'name' => 'Quote',
                'slug' => 'quote',
            ],
            [
                'id' => 4,
                'name' => 'Proforma',
                'slug' => 'proforma',
            ],
            [
                'id' => 5,
                'name' => 'Receipt',
                'slug' => 'receipt',
            ],
            [
                'id' => 6,
                'name' => 'Booking Confirmation',
                'slug' => 'booking_confirmation',
            ],
            [
                'id' => 7,
                'name' => 'message Guest',
                'slug' => 'message_guest',
            ],
            [
                'id' => 8,
                'name' => 'Booking Voucher',
                'slug' => 'booking_voucher',
            ],
        ];
        foreach ($type_invoices as $type_invoice) {
            TypeInvoice::updateOrCreate(['id' => $type_invoice['id']], $type_invoice);
        }


        $invoice_templates = [
            [
                'id' => 1,
                'name' => 'std',
                'content' => '<p style="padding-left: 880px;">{{logo}}</p>
                <p>{{site_name}}</p>
                <p>{{site_reg_no}} &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;INVOICE</p>
                <table style="border:1px solid white; border-collapse: collapse; width: 100%;">
                    <colgroup>
                        <col style="width: 64.8577%; border:1px solid white;">
                        <col style="width: 35.1423%; border:1px solid white;">
                    </colgroup>
                    <tbody>
                        <tr>
                            <td>
                                <table style="border:1px solid black; border-collapse: collapse; width: 79.3275%; height: 85px;">
                                    <colgroup>
                                        <col style="width: 46.5876%;">
                                        <col style="width: 53.4894%;">
                                    </colgroup>
                                    <tbody>
                                        <tr style="height: 21px;">
                                            <td>From:</td>
                                            <td>To:</td>
                                        </tr>
                                        <tr style="height: 21px;">
                                            <td>{{site_name}}</td>
                                            <td>{{client_name}}</td>
                                        </tr>
                                        <tr style="height: 21px;">
                                            <td>{{site_address}}</td>
                                            <td>{{client_phone}}</td>
                                        </tr>
                                        <tr style="height: 10px;">
                                            <td>{{site_address_code}}</td>
                                            <td>{{client_email}}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td>
                                <table style="border:1px solid black; border-collapse: collapse; width: 101.773%; height: 73px;">
                                    <colgroup>
                                        <col style="width: 35.0316%;">
                                        <col style="width: 65.1228%;">
                                    </colgroup>
                                    <tbody>
                                        <tr style="height: 21px;">
                                            <td>Invoice #:</td>
                                            <td>{{invoice_number}}</td>
                                        </tr>
                                        <tr style="height: 21px;">
                                            <td>Booking #:</td>
                                            <td>{{booking_ref}}</td>
                                        </tr>
                                        <tr style="height: 21px;">
                                            <td>Date:</td>
                                            <td>{{invoice_date}}</td>
                                        </tr>
                                        <tr style="height: 10px;">
                                            <td>Amount Due:</td>
                                            <td>{{balance_due}}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p>&nbsp;</p>
                <table style="border:1px solid black; border-collapse: collapse; width: 100%; height: 228px;">
                    <colgroup>
                        <col style="width: 20%; border-bottom:1px solid black;">
                        <col style="width: 20%; border-bottom:1px solid black;">
                        <col style="width: 20%; border-bottom:1px solid black;">
                        <col style="width: 20%; border-bottom:1px solid black;">
                        <col style="width: 20%; border-bottom:1px solid black;">
                    </colgroup>
                    <tbody>
                        <tr style="height: 21px;">
                            <td style="border: 1px solid black;">Service</td>
                            <td style="border: 1px solid black;">Adults (Qty)</td>
                            <td style="border: 1px solid black;">Children (Qty)</td>
                            <td style="border: 1px solid black;">Guides (Qty)</td>
                            <td style="border: 1px solid black;">Total Cost (ZAR)</td>
                        </tr>
                        <tr style="height: 39px;">
                            <td style="border: 1px solid black;">{{service_name}}</td>
                            <td style="border: 1px solid black;">{{service_adults}}</td>
                            <td style="border: 1px solid black;">{{service_children}}</td>
                            <td style="border: 1px solid black;">{{service_guides}}</td>
                            <td style="border: 1px solid black;">{{balance_due}}</td>
                        </tr>
                        <tr style="height: 21px;">
                            <td colspan="2" style="border-bottom: 1px solid white;"></td>
                            <td style="border: 1px solid black;">Sub-total</td>
                            <td style="border: 1px solid black;">(Excl VAT)</td>
                            <td style="border: 1px solid black;">{{vat_amount}}</td>
                        </tr>
                        <tr style="height: 21px;">
                            <td colspan="3"></td>
                            <td style="border: 1px solid black;">VAT - 15.0%</td>
                            <td style="border: 1px solid black;">{{vat}}</td>
                        </tr>
                        <tr style="height: 21px;">
                            <td colspan="3"></td>
                            <td style="border: 1px solid black;">Total:</td>
                            <td style="border: 1px solid black;">{{vat_amount}}</td>
                        </tr>
                        <tr style="height: 21px;">
                            <td colspan="3"></td>
                            <td style="border: 1px solid black;">Amount Paid:</td>
                            <td style="border: 1px solid black;">{{amount_paid}}</td>
                        </tr>
                        <tr style="height: 21px;">
                            <td colspan="3"></td>
                            <td style="border: 1px solid black;">Balance Due:</td>
                            <td style="border: 1px solid black;">{{balance_due}}</td>
                        </tr>
                    </tbody>
                </table>
                <p>&nbsp;</p>
                <table style="border-collapse: collapse; width: 100%;" border="1">
                    <colgroup>
                        <col style="width: 51.057%;">
                        <col style="width: 48.943%;">
                    </colgroup>
                    <tbody>
                        <tr>
                            <td>NOTES:</td>
                            <td>Banking Details</td>
                        </tr>
                        <tr>
                            <td>{{notes}}</td>
                            <td>
                                <p>Account Owner:&nbsp; {{bank_account_owner}}</p>
                                <p>Account&nbsp;Number: {{bank_account_no}}</p>
                                <p>Account Type: {{bank_account_type}}</p>
                                <p>Bank Name: {{bank_name}}</p>
                                <p>Branch Code: {{bank_branch_code}}</p>
                                <p>Reference: {{booking_ref}}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>',
                'is_default' => 1,
                'team_id' => 7,
                'type_invoice_id' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Booking confirmation',
                'content' => '<p>Hello {{customer_name}},</p><p>Your booking has been paid.</p><p>Thank you for choosing Canopy Tours.</p><p>Your booking reference is {{booking_reference}}.</p><p>Please Contact us if you have any questions.</p>',
                'is_default' => 0,
                'type_invoice_id' => 6,
                'team_id' => 7,
            ],
            [
                'id' => 3,
                'name' => 'message guest',
                'content' => '<p>Hello {{customer_name}},</p><p>Your booking has been paid.</p><p>Thank you for choosing Canopy Tours.</p><p>Your booking reference is {{booking_reference}}.</p><p>Please Contact us if you have any questions.</p>',
                'is_default' => 0,
                'team_id' => 7,
                'type_invoice_id' => 7,
            ],
        ];

        foreach ($invoice_templates as $template) {
            InvoiceTemplate::updateOrCreate(['id' => $template['id']], $template);
        }

        $wholesaler_rates = [
            [
                'id' => 1,
                'wholesaler_id' => 1,
                'service_id' => 1,
                'season_id' => 1,
                'price_adult' => '590.00',
                'price_child' => '290.00',
                'team_id' => 4,
            ],
            [
                'id' => 2,
                'wholesaler_id' => 1,
                'service_id' => 4,
                'season_id' => 4,
                'price_adult' => '500.00',
                'price_child' => '200.00',
                'team_id' => 4,
            ],
            [
                'id' => 3,
                'wholesaler_id' => 1,
                'service_id' => 5,
                'season_id' => 5,
                'price_adult' => '560.00',
                'price_child' => '260.00',
                'team_id' => 4,
            ],
            [
                'id' => 4,
                'wholesaler_id' => 2,
                'service_id' => 2,
                'season_id' => 2,
                'price_adult' => '540.00',
                'price_child' => '240.00',
                'team_id' => 7,
            ],
            [
                'id' => 5,
                'wholesaler_id' => 3,
                'service_id' => 3,
                'season_id' => 3,
                'price_adult' => '580.00',
                'price_child' => '280.00',
                'team_id' => 7,
            ],
            [
                'id' => 6,
                'wholesaler_id' => 4,
                'service_id' => 4,
                'season_id' => 4,
                'price_adult' => '570.00',
                'price_child' => '270.00',
                'team_id' => 7,
            ],
        ];

        foreach ($wholesaler_rates as $wholesaler_rate) {
            WholesalerRate::updateOrCreate(['id' => $wholesaler_rate['id']], $wholesaler_rate);
        }

        $inventories = [
            [
                'id' => 1,
                'allow_internal_override' => 1,
                'service_id' => 1,
                'season_id' => 1,
                'team_id' => 7,

            ],
            [
                'id' => 2,
                'allow_internal_override' => 1,
                'service_id' => 2,
                'season_id' => 2,
                'team_id' => 7,

            ],
            [
                'id' => 3,
                'allow_internal_override' => 1,
                'service_id' => 3,
                'season_id' => 3,
                'team_id' => 7,

            ],
            [
                'id' => 4,
                'allow_internal_override' => 1,
                'service_id' => 4,
                'season_id' => 4,
                'team_id' => 7,

            ],
            [
                'id' => 5,
                'allow_internal_override' => 1,
                'service_id' => 5,
                'season_id' => 5,
                'team_id' => 7,

            ],
            [
                'id' => 6,
                'allow_internal_override' => 1,
                'service_id' => 6,
                'season_id' => 6,
                'team_id' => 7,

            ],
            [
                'id' => 7,
                'allow_internal_override' => 1,
                'service_id' => 6,
                'season_id' => 7,
                'team_id' => 7,
            ],
        ];

        foreach ($inventories as $inv) {
            Inventory::updateOrCreate(['id' => $inv['id']], $inv);
        }

        $inventory_time_slots = [
            [
                'id' => 1,
                'inventory_id' => 1,
                'start_time' => '10:00:00',
            ],
            [
                'id' => 2,
                'inventory_id' => 1,
                'start_time' => '12:00:00',
            ],
            [
                'id' => 3,
                'inventory_id' => 1,
                'start_time' => '14:00:00',
            ],
            [
                'id' => 4,
                'inventory_id' => 1,
                'start_time' => '16:00:00',
            ],
            [
                'id' => 5,
                'inventory_id' => 2,
                'start_time' => '08:00:00',
            ],
            [
                'id' => 6,
                'inventory_id' => 2,
                'start_time' => '10:00:00',
            ],
            [
                'id' => 7,
                'inventory_id' => 2,
                'start_time' => '14:00:00',
            ],
            [
                'id' => 8,
                'inventory_id' => 2,
                'start_time' => '16:00:00',
            ],
            [
                'id' => 9,
                'inventory_id' => 3,
                'start_time' => '08:00:00',
            ],
            [
                'id' => 10,
                'inventory_id' => 3,
                'start_time' => '10:00:00',
            ],
            [
                'id' => 11,
                'inventory_id' => 3,
                'start_time' => '14:00:00',
            ],
            [
                'id' => 12,
                'inventory_id' => 3,
                'start_time' => '16:00:00',
            ],
            [
                'id' => 13,
                'inventory_id' => 4,
                'start_time' => '08:00:00',
            ],
            [
                'id' => 14,
                'inventory_id' => 4,
                'start_time' => '10:00:00',
            ],
            [
                'id' => 15,
                'inventory_id' => 4,
                'start_time' => '14:00:00',
            ],
            [
                'id' => 16,
                'inventory_id' => 4,
                'start_time' => '16:00:00',
            ],

            [
                'id' => 17,
                'inventory_id' => 5,
                'start_time' => '08:00:00',
            ],
            [
                'id' => 18,
                'inventory_id' => 5,
                'start_time' => '10:00:00',
            ],
            [
                'id' => 19,
                'inventory_id' => 5,
                'start_time' => '14:00:00',
            ],
            [
                'id' => 20,
                'inventory_id' => 5,
                'start_time' => '16:00:00',
            ],
            [
                'id' => 21,
                'inventory_id' => 6,
                'start_time' => '08:00:00',
            ],
            [
                'id' => 22,
                'inventory_id' => 6,
                'start_time' => '10:00:00',
            ],
            [
                'id' => 23,
                'inventory_id' => 6,
                'start_time' => '14:00:00',
            ],
            [
                'id' => 24,
                'inventory_id' => 6,
                'start_time' => '16:00:00',
            ],
            [
                'id' => 25,
                'inventory_id' => 7,
                'start_time' => '08:00:00',
            ],
            [
                'id' => 26,
                'inventory_id' => 7,
                'start_time' => '10:00:00',
            ],
            [
                'id' => 27,
                'inventory_id' => 7,
                'start_time' => '14:00:00',
            ],
            [
                'id' => 28,
                'inventory_id' => 7,
                'start_time' => '16:00:00',
            ],
        ];

        foreach ($inventory_time_slots as $invTs) {
            InventoryTimeSlot::updateOrCreate(['id' => $invTs['id']], $invTs);
        }
        $this->info('InventoryTimeSlots seeded successfully.');
        $inventory_type_days = [
            ['id' => 1, 'inventory_id' => 1, 'type_day_id' => 1],
            ['id' => 2, 'inventory_id' => 1, 'type_day_id' => 2],
            ['id' => 3, 'inventory_id' => 1, 'type_day_id' => 3],
            ['id' => 4, 'inventory_id' => 1, 'type_day_id' => 4],
            ['id' => 5, 'inventory_id' => 1, 'type_day_id' => 5],
            ['id' => 6, 'inventory_id' => 1, 'type_day_id' => 6],
            ['id' => 7, 'inventory_id' => 1, 'type_day_id' => 7],
            ['id' => 8, 'inventory_id' => 2, 'type_day_id' => 1],
            ['id' => 9, 'inventory_id' => 2, 'type_day_id' => 2],
            ['id' => 10, 'inventory_id' => 2, 'type_day_id' => 3],
            ['id' => 11, 'inventory_id' => 2, 'type_day_id' => 4],
            ['id' => 12, 'inventory_id' => 2, 'type_day_id' => 5],
            ['id' => 13, 'inventory_id' => 2, 'type_day_id' => 6],
            ['id' => 14, 'inventory_id' => 2, 'type_day_id' => 7],
            ['id' => 15, 'inventory_id' => 3, 'type_day_id' => 1],
            ['id' => 16, 'inventory_id' => 3, 'type_day_id' => 2],
            ['id' => 17, 'inventory_id' => 3, 'type_day_id' => 3],
            ['id' => 18, 'inventory_id' => 3, 'type_day_id' => 4],
            ['id' => 19, 'inventory_id' => 3, 'type_day_id' => 5],
            ['id' => 20, 'inventory_id' => 3, 'type_day_id' => 6],
            ['id' => 21, 'inventory_id' => 3, 'type_day_id' => 7],
            ['id' => 22, 'inventory_id' => 4, 'type_day_id' => 1],
            ['id' => 23, 'inventory_id' => 4, 'type_day_id' => 2],
            ['id' => 24, 'inventory_id' => 4, 'type_day_id' => 3],
            ['id' => 25, 'inventory_id' => 4, 'type_day_id' => 4],
            ['id' => 26, 'inventory_id' => 4, 'type_day_id' => 5],
            ['id' => 27, 'inventory_id' => 4, 'type_day_id' => 6],
            ['id' => 28, 'inventory_id' => 4, 'type_day_id' => 7],
            ['id' => 29, 'inventory_id' => 5, 'type_day_id' => 1],
            ['id' => 30, 'inventory_id' => 5, 'type_day_id' => 2],
            ['id' => 31, 'inventory_id' => 5, 'type_day_id' => 3],
            ['id' => 32, 'inventory_id' => 5, 'type_day_id' => 4],
            ['id' => 33, 'inventory_id' => 5, 'type_day_id' => 5],
            ['id' => 34, 'inventory_id' => 5, 'type_day_id' => 6],
            ['id' => 35, 'inventory_id' => 5, 'type_day_id' => 7],
            ['id' => 36, 'inventory_id' => 6, 'type_day_id' => 1],
            ['id' => 37, 'inventory_id' => 6, 'type_day_id' => 2],
            ['id' => 38, 'inventory_id' => 6, 'type_day_id' => 3],
            ['id' => 39, 'inventory_id' => 6, 'type_day_id' => 4],
            ['id' => 40, 'inventory_id' => 6, 'type_day_id' => 5],
            ['id' => 41, 'inventory_id' => 6, 'type_day_id' => 6],
            ['id' => 42, 'inventory_id' => 6, 'type_day_id' => 7],
        ];
        foreach ($inventory_type_days as $invTypeDay) {
            InventoryTypeDay::updateOrCreate(['id' => $invTypeDay['id']], $invTypeDay);
        }

        $closeouts = [
            [
                'id' => 1,
                'service_id' => 1,
                'season_id' => 1,
                'name' => 'renovations',
                'start_date' => '2025-06-20',
                'end_date' => '2025-06-27',
                'reason' => 'general maintenance',
                'team_id' => 7,
            ],
            [
                'id' => 2,
                'service_id' => 1,
                'season_id' => 1,
                'name' => 'holiday',
                'start_date' => '2025-07-25',
                'end_date' => '2025-07-26',
                'reason' => 'general',
                'team_id' => 7,
            ],
            [
                'id' => 3,
                'service_id' => 2,
                'season_id' => 2,
                'name' => 'holiday',
                'start_date' => '2025-12-25',
                'end_date' => '2025-12-26',
                'reason' => 'general',
                'team_id' => 7,
            ],
        ];

        foreach ($closeouts as $closeout) {
            Closeout::updateOrCreate(['id' => $closeout['id']], $closeout);
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $closeoutDayTimeSlots = [
            [
                'id' => 1,
                'closeout_id' => 1,
                'day_id' => 6,
                'time_slot_id' => 1,
            ],
            [
                'id' => 2,
                'closeout_id' => 1,
                'day_id' => 7,
                'time_slot_id' => 2,
            ],
            [
                'id' => 3,
                'closeout_id' => 2,
                'day_id' => 6,
                'time_slot_id' => 3,
            ],
            [
                'id' => 4,
                'closeout_id' => 2,
                'day_id' => 7,
                'time_slot_id' => 4,
            ],
            [
                'id' => 5,
                'closeout_id' => 3,
                'day_id' => 7,
                'time_slot_id' => 5,
            ]
        ];

        foreach ($closeoutDayTimeSlots as $closeoutDayTimeSlot) {
            CloseoutDayTimeSlot::updateOrCreate(['id' => $closeoutDayTimeSlot['id']], $closeoutDayTimeSlot);
        }

        $discounts = [
            [
                'id' => 1,
                'name' => 'Pensioner',
                'applies_to' => 'line_item',
                'percent_off' => 8,
                'team_id' => 7,
            ],
            [
                'id' => 2,
                'name' => 'Student',
                'applies_to' => 'line_item',
                'percent_off' => 5,
                'team_id' => 7,
            ],
            [
                'id' => 3,
                'name' => 'Regular',
                'applies_to' => 'line_item',
                'percent_off' => 10,
                'team_id' => 7,
            ],
            [
                'id' => 4,
                'name' => 'Local',
                'applies_to' => 'line_item',
                'percent_off' => 10,
                'team_id' => 7,
            ],
            [
                'id' => 5,
                'name' => '3% Invoice Pax(8)',
                'applies_to' => 'invoice',
                'percent_off' => 3,
                'team_id' => 7,
            ],
            [
                'id' => 6,
                'name' => '5% Invoice Pax(15)',
                'applies_to' => 'invoice',
                'percent_off' => 5,
                'team_id' => 7,
            ]
        ];

        foreach ($discounts as $discount) {
            Discount::updateOrCreate(['id' => $discount['id']], $discount);
        }

        $discount_services = [
            [
                'id' => 1,
                'discount_id' => 1,
                'service_id' => 1,
            ],
            [
                'id' => 2,
                'discount_id' => 2,
                'service_id' => 1,
            ],
            [
                'id' => 3,
                'discount_id' => 3,
                'service_id' => 1,
            ],
            [
                'id' => 4,
                'discount_id' => 4,
                'service_id' => 1,
            ],
            [
                'id' => 5,
                'discount_id' => 5,
                'service_id' => 1,
            ],
            [
                'id' => 6,
                'discount_id' => 6,
                'service_id' => 1,
            ],
            [
                'id' => 7,
                'discount_id' => 1,
                'service_id' => 2,
            ],
            [
                'id' => 8,
                'discount_id' => 2,
                'service_id' => 2,
            ],
            [
                'id' => 9,
                'discount_id' => 3,
                'service_id' => 2,
            ],
            [
                'id' => 10,
                'discount_id' => 4,
                'service_id' => 2,
            ],
            [
                'id' => 11,
                'discount_id' => 5,
                'service_id' => 2,
            ],
            [
                'id' => 12,
                'discount_id' => 6,
                'service_id' => 2,
            ],
            [
                'id' => 13,
                'discount_id' => 1,
                'service_id' => 3,
            ],
            [
                'id' => 14,
                'discount_id' => 2,
                'service_id' => 3,
            ],
            [
                'id' => 15,
                'discount_id' => 3,
                'service_id' => 3,
            ],
            [
                'id' => 16,
                'discount_id' => 4,
                'service_id' => 3,
            ],
            [
                'id' => 17,
                'discount_id' => 5,
                'service_id' => 3,
            ],
            [
                'id' => 18,
                'discount_id' => 6,
                'service_id' => 3,
            ],
            [
                'id' => 19,
                'discount_id' => 1,
                'service_id' => 4,
            ],
            [
                'id' => 20,
                'discount_id' => 2,
                'service_id' => 4,
            ],
        ];

        foreach ($discount_services as $discount_service) {
            DiscountService::updateOrCreate(['id' => $discount_service['id']], $discount_service);
        }

        $promotions = [
            [
                'id' => 1,
                'name' => 'Percent',
                'promo_code' => 'PERCENT',
                'type' => 'percent',
                'value' => 10,
                'params' => null,
                'service_id' => 1,
                'booking_start_date' => '2025-08-01',
                'booking_end_date' => '2025-08-31',
                'activity_start_date' => '2025-08-01',
                'activity_end_date' => '2025-08-31',
                'internal_use_only' => 0,
                'team_id' => 7,
            ],
            [
                'id' => 2,
                'name' => 'Fixed Value',
                'promo_code' => 'FIXED_VALUE',
                'type' => 'fixed_value',
                'value' => 10,
                'params' => null,
                'service_id' => 1,
                'booking_start_date' => '2025-08-01',
                'booking_end_date' => '2025-12-31',
                'activity_start_date' => '2025-11-01',
                'activity_end_date' => '2025-12-31',
                'internal_use_only' => 0,
                'team_id' => 7,
            ],
            [
                'id' => 3, // {"lookup":{"1":"300","2":"300","3":"300","4":"250","5":"250","6":"200","7":"200","8":"200"}}
                'name' => 'Graduated',
                'promo_code' => 'GRADUATED',
                'type' => 'graduated',
                'value' => 0,
                'params' => [
                    'lookup' => [
                        '1' => '300',
                        '2' => '300',
                        '3' => '300',
                        '4' => '250',
                        '5' => '250',
                        '6' => '200',
                        '7' => '200',
                        '8' => '200',
                    ],
                ],
                'service_id' => 1,
                'booking_start_date' => '2025-08-01',
                'booking_end_date' => '2025-12-31',
                'activity_start_date' => '2025-10-01',
                'activity_end_date' => '2025-12-31',
                'internal_use_only' => 0,
                'team_id' => 7,
            ],
            [ // {"min_adults":"5","child_discount_percent":"10"} 
                'id' => 4,
                'name' => 'Varied Child',
                'promo_code' => 'VARIED_CHILD',
                'type' => 'varied_child',
                'value' => 0,
                'params' =>
                [
                    'min_adults' => 2,
                    'child_discount_percent' => 20,
                ],
                'service_id' => 1,
                'booking_start_date' => '2025-08-01',
                'booking_end_date' => '2025-12-31',
                'activity_start_date' => '2025-11-01',
                'activity_end_date' => '2025-12-31',
                'internal_use_only' => 0,
                'team_id' => 7,
            ],
            [
                'id' => 5, // {"tiers":[{"pax":"6","type":"percent","value":"4"},{"pax":"10","type":"percent","value":"6"},{"pax":"20","type":"percent","value":"8"}]}
                'name' => 'Volume Discount',
                'promo_code' => 'VOLUME_DISCOUNT',
                'type' => 'volume_discount',
                'value' => 0,
                'params' => [
                    'tiers' => [
                        [
                            'pax' => 4,
                            'type' => "percent",
                            'value' => 5,
                        ],
                        [
                            'pax' => 8,
                            'type' => "percent",
                            'value' => 10,
                        ],
                    ],
                ],
                'service_id' => 1,
                'booking_start_date' => '2025-08-01',
                'booking_end_date' => '2025-12-31',
                'activity_start_date' => '2025-08-01',
                'activity_end_date' => '2025-12-31',
                'internal_use_only' => 0,
                'team_id' => 7,
            ],
            [
                'id' => 6,
                'name' => 'Time Slot Oct 10am',
                'promo_code' => 'OCT-10/15',
                'type' => 'time_slot',
                'value' => 15.00,
                'params' => null,
                'service_id' => 1,
                'season_id' => 1,
                'booking_start_date' => '2025-10-01',
                'booking_end_date' => '2025-10-31',
                'activity_start_date' => '2025-10-01',
                'activity_end_date' => '2025-10-31',
                'internal_use_only' => 0,
                'team_id' => 7,
            ],

        ];

        foreach ($promotions as $promo) {
            $promo = Promotion::updateOrCreate(['id' => $promo['id']], $promo);

            if ($promo->type == 'time_slot') {
                $promo->promo_day_time_slots()->updateOrCreate([
                    'day_id' => 4,
                    'time_slot_id' => 1,
                    'promotion_id' => $promo['id'],
                ]);

                $promo->promo_day_time_slots()->updateOrCreate([
                    'day_id' => 3,
                    'time_slot_id' => 1,
                    'promotion_id' => $promo['id'],
                ]);
                $promo->promo_day_time_slots()->updateOrCreate([
                    'day_id' => 2,
                    'time_slot_id' => 1,
                    'promotion_id' => $promo['id'],
                ]);
            }
        }

        $booking_vouchers = [
            [
                'id' => 1,
                'name' => 'Booking Voucher 1',
                'service_id' => 1,
                'season_id' => 1,
                'team_id' => 7,
            ],
            [
                'id' => 2,
                'name' => 'Booking Voucher 2',
                'service_id' => 2,
                'season_id' => 2,
                'team_id' => 7,
            ],
            [
                'id' => 3,
                'name' => 'Booking Voucher 3',
                'service_id' => 3,
                'season_id' => 3,
                'team_id' => 7,
            ],
            [
                'id' => 4,
                'name' => 'Booking Voucher 4',
                'service_id' => 4,
                'season_id' => 4,
                'team_id' => 7,
            ],
            [
                'id' => 5,
                'name' => 'Booking Voucher 5',
                'service_id' => 5,
                'season_id' => 5,
                'team_id' => 7,
            ],
            [
                'id' => 6,
                'name' => 'Booking Voucher 6',
                'service_id' => 6,
                'season_id' => 6,
                'team_id' => 7,
            ],
        ];

        $booking_voucher_items = [
            [
                'id' => 1,
                'booking_voucher_id' => 1,
                'name' => 'Welcome Message',
                'helper' => 'This is the welcome message for the voucher',
                'description' => 'This voucher is valid to secure delivery of the service set out below by Tsitsikamma Canopy Tour on the
                    date and time indicated, to the lead participant and the number of additional participants included here below.',
                'disabled' => false,
                'type' => 'textarea',
                'format' => 'paragraph',
            ],
            [
                'id' => 2,
                'booking_voucher_id' => 1,
                'name' => 'Reference',
                'helper' => 'This is the description for the booking voucher item',
                'description' => '{{reference}}',
                'disabled' => true,
                'type' => 'text',
                'format' => 'table',
            ],
            [
                'id' => 3,
                'booking_voucher_id' => 1,
                'name' => 'Provider',
                'helper' => 'This is the name of the provider',
                'description' => '{{provider}}',
                'disabled' => true,
                'type' => 'text',
                'format' => 'table',
            ],
            [
                'id' => 4,
                'booking_voucher_id' => 1,
                'name' => 'Service',
                'helper' => 'This is the name of the service',
                'description' => '{{service}}',
                'disabled' => true,
                'type' => 'text',
                'format' => 'table',
            ],
            [
                'id' => 5,
                'booking_voucher_id' => 1,
                'name' => 'Location',
                'helper' => 'This is the location of the service',
                'description' => 'Experience a World of Diversity in One Forest – Just a short drive from Knysna and 2 hours from Port-Elizabeth. 
                    Situated in the Garden of the Garden Route and heart of Tsitsikamma – Stormsriver Village. 
                    Tsitsikamma Canopy Tour is an Award-Winning Canopy Tour company that turns Tourism activities into Tourism experiences! 
                    Enjoy being suspended 30m above the forest floor on the Tsitsikamma Canopy Tour, cable sliding from ten platforms, 
                    tree to tree while discovering the spectacular and unique wilderness of the Tsitsikamma Indigenous forest. 
                    Surrounded by birdlife and giant trees like the Outeniqua Yellowwood, known to us as Uncle Sam who is over 
                    600 years old! One can easily feel like a dwarf standing against it. The tour begins with a detailed safety briefing 
                    followed by a ‘kitting up’ session where a full-body harness, pulleys, and climbing equipment is issued and checked. 
                    A short drive into the Tsitsikamma forest and it is time to monkey around! From the start on the launch platform, 
                    a new world unfolds as one gently glides on a steel cable to the first tree platform. All groups are escorted through 
                    the forest canopy by a lead guide and a follow guide to ensure your safety as you slide from tree to tree. 
                    The platforms built high up in the giant Outeniqua Yellowwoods, provide an unbelievable view of this enchanting 
                    new world – any fear of heights is soon lost as you gather in your new surroundings.',
                'disabled' => false,
                'type' => 'textarea',
                'format' => 'table',
            ],
            [
                'id' => 6,
                'booking_voucher_id' => 1,
                'name' => 'What to Wear',
                'helper' => 'This is the what to wear for the service',
                'description' => 'Please wear river shoes, strops or trainers that can get wet (not
                    flip-flops), swimming costume, shorts and t-shirt and sunscreen.
                    Bring a towel, dry clothes and a warm jacket for after.',
                'disabled' => false,
                'type' => 'textarea',
                'format' => 'table',
            ],
            [
                'id' => 7,
                'booking_voucher_id' => 1,
                'name' => 'Starting Time and Date',
                'helper' => 'This is the starting time and date for the service',
                'description' => '{{start_time_and_date}}',
                'disabled' => true,
                'type' => 'text',
                'format' => 'table',
            ],
            [
                'id' => 8,
                'booking_voucher_id' => 1,
                'name' => 'Starting Location',
                'helper' => 'This is the starting location for the service',
                'description' => 'Tsitsikamma Canopy Tour, short drive from Knysna',
                'disabled' => false,
                'type' => 'textarea',
                'format' => 'table',
            ],
            [
                'id' => 9,
                'booking_voucher_id' => 1,
                'name' => 'Ending Location',
                'helper' => 'This is the ending location for the service',
                'description' => 'Tsitsikamma Canopy Tour, short drive from Knysna',
                'disabled' => false,
                'type' => 'textarea',
                'format' => 'table',
            ],
            [
                'id' => 10,
                'booking_voucher_id' => 1,
                'name' => 'Lead Passenger Name',
                'helper' => 'This is the lead name for the service',
                'description' => '{{lead_name}}',
                'disabled' => true,
                'type' => 'text',
                'format' => 'table',
            ],
            [
                'id' => 11,
                'booking_voucher_id' => 1,
                'name' => 'Lead Passenger Email',
                'helper' => 'This is the lead email for the service',
                'description' => '{{lead_email}}',
                'disabled' => true,
                'type' => 'text',
                'format' => 'table',
            ],
            [
                'id' => 12,
                'booking_voucher_id' => 1,
                'name' => 'Number in Party',
                'helper' => 'This is the number in party for the service',
                'description' => '{{number_in_party}}',
                'disabled' => true,
                'type' => 'text',
                'format' => 'table',
            ],
            [
                'id' => 13,
                'booking_voucher_id' => 1,
                'name' => 'Supplier Email',
                'helper' => 'This is the supplier email for the service',
                'description' => 'manager@magaliesberg.com',
                'disabled' => false,
                'type' => 'email',
                'format' => 'table',
            ],
            [
                'id' => 14,
                'booking_voucher_id' => 1,
                'name' => 'Supplier Landline',
                'helper' => 'This is the supplier landline for the service',
                'description' => '0000000000',
                'disabled' => false,
                'type' => 'text',
                'format' => 'table',
            ],
            [
                'id' => 15,
                'booking_voucher_id' => 1,
                'name' => 'Supplier Mobile',
                'helper' => 'This is the supplier mobile for the service',
                'description' => '0000000000',
                'disabled' => false,
                'type' => 'text',
                'format' => 'table',
            ],

        ];

        foreach ($booking_vouchers as $booking_voucher) {
            $voucher = BookingVoucher::updateOrCreate(
                [
                    'id' => $booking_voucher['id'],

                ],
                [
                    'name' => $booking_voucher['name'],
                    'service_id' => $booking_voucher['service_id'],
                    'season_id' => $booking_voucher['season_id'],
                    'team_id' => $booking_voucher['team_id'],
                ]
            );

            foreach ($booking_voucher_items as $booking_voucher_item) {
                $voucherItem = BookingVoucherItem::updateOrCreate(
                    [
                        'id' => $booking_voucher_item['id'],
                        'booking_voucher_id' => $voucher->id,
                    ],
                    [
                        'name' => $booking_voucher_item['name'],
                        'helper' => $booking_voucher_item['helper'],
                        'description' => $booking_voucher_item['description'],
                        'disabled' => $booking_voucher_item['disabled'],
                        'type' => $booking_voucher_item['type'],
                        'format' => $booking_voucher_item['format'],
                    ]
                );
            }
        }

        if (false) {
            $customers = [
                [
                    'id'             => 1,
                    'full_name'      => 'Customer1',
                    'email_address'  => 'Customer1@email.com',
                    'contact_number' => '1234567890',
                    'nationality_id'    => '',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                ],
                [
                    'id'             => 2,
                    'full_name'           => 'Customer2',
                    'email_address'          => 'Customer2@email.com',
                    'contact_number' => '0987654321',
                    'nationality'    => '',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                ],
                [
                    'id'             => 3,
                    'full_name'           => 'Customer3',
                    'email_address'          => 'Customer3@email.com',
                    'contact_number' => '9876543210',
                    'nationality'    => '',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'parent_id'      => 1,
                ],
                [
                    'id'             => 4,
                    'full_name'           => 'Customer4',
                    'email_address'          => 'Customer4@email.com',
                    'contact_number' => '9876543211',
                    'nationality'    => '',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                ],
                [
                    'id'             => 5,
                    'full_name'           => 'Customer5',
                    'email_address'          => 'Customer5@email.com',
                    'contact_number' => '9876543212',
                    'nationality'    => '',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                ],
                [
                    'id'             => 6,
                    'full_name'           => 'Customer6',
                    'email_address'          => 'Customer6@email.com',
                    'contact_number' => '9876543213',
                    'nationality'    => '',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                ],
                [
                    'id'             => 7,
                    'full_name'           => 'Customer7',
                    'email_address'          => 'Customer7@email.com',
                    'contact_number' => '9876543214',
                    'nationality'    => '',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                ],
                [
                    'id'             => 8,
                    'full_name'           => 'Customer8',
                    'email_address'          => 'Customer8@email.com',
                    'contact_number' => '9876543215',
                    'nationality'    => '',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'parent_id'      => 7,
                ],
                [
                    'id'             => 9,
                    'full_name'           => 'Customer9',
                    'email_address'          => 'Customer9@email.com',
                    'contact_number' => '9876543216',
                    'nationality'    => '',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                ],
                [
                    'id'             => 10,
                    'full_name'           => 'Customer10',
                    'email_address'          => 'Customer10@email.com',
                    'contact_number' => '9876543217',
                    'nationality'    => '',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                ],
            ];

            foreach ($customers as $customer) {
                Customer::updateOrCreate(['id' => $customer['id']], $customer);
            }

            $bookings = [
                [
                    'id'             => 1,
                    'lead_name'      => 'Customer1',
                    'user_id'        => 1,
                    'type_booking_id' => 2,
                    'status_payment_id' => 2,
                    'is_sms'         => 0,
                    'is_corporate'   => 0,
                    'due_date'       => '2025-03-01',
                    'tot_paid'       => 0,
                    'grand_tot_due'  => 1500.00,
                    'reference'      => 'REF-' . strtoupper(Str::random(6)), //Str::uuid(),
                    'customer_id'     => 1,
                    'updated_by_id'  => 1,
                    'customers'      => [1, 2, 3],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 2,
                    'lead_name'      => 'Customer4',
                    'user_id'        => 1,
                    'type_booking_id' => 2,
                    'status_payment_id' => 2,
                    'is_sms'         => 0,
                    'is_corporate'   => 0,
                    'due_date'       => '2025-04-02',
                    'tot_paid'       => 0,
                    'grand_tot_due'  => 1800.00,
                    'reference'      => 'REF-' . strtoupper(Str::random(6)), //Str::uuid(),
                    'customer_id'     => 4,
                    'updated_by_id'  => 1,
                    'customers'      => [4, 5, 6],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 3,
                    'lead_name'      => 'Customer7',
                    'user_id'        => 1,
                    'type_booking_id' => 2,
                    'status_payment_id' => 2,
                    'is_sms'         => 0,
                    'is_corporate'   => 0,
                    'due_date'       => '2025-07-03',
                    'tot_paid'       => 0,
                    'grand_tot_due'  => 900.00,
                    'reference'      => 'REF-' . strtoupper(Str::random(6)), //Str::uuid(),
                    'customer_id'     => 7,
                    'updated_by_id'  => 1,
                    'customers'      => [7, 8],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 4,
                    'lead_name'      => 'Customer9',
                    'user_id'        => 1,
                    'type_booking_id' => 2,
                    'status_payment_id' => 2,
                    'is_sms'         => 0,
                    'is_corporate'   => 0,
                    'due_date'       => '2025-07-04',
                    'tot_paid'       => 0,
                    'grand_tot_due'  => 1200.00,
                    'reference'      => 'REF-' . strtoupper(Str::random(6)), //Str::uuid(),
                    'customer_id'     => 9,
                    'updated_by_id'  => 1,
                    'customers'      => [9, 10],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
            ];

            foreach ($bookings as $item) {
                $booking =  Booking::updateOrCreate(['id' => $item['id']], $item);
            }
            $this->info('booking seeded successfully.');

            $bookingItems = [
                [
                    'id'             => 1,
                    'service_id'     => 1,
                    'season_id'      => 1,
                    'inventory_id'   => 1,
                    'booking_id'     => 1,
                    'price_adult'    => 600.00,
                    'price_child'    => 300.00,
                    'price_guide'    => 0.00,
                    'promotion_id'   => null,
                    'pax_adults'    => 2,
                    'pax_children'    => 1,
                    'pax_guides'    => 0,
                    'customers' => [1, 2, 3],
                ],
                [
                    'id'             => 2,
                    'service_id'     => 2,
                    'season_id'      => 2,
                    'inventory_id'   => 2,
                    'booking_id'     => 2,
                    'price_adult'    => 600.00,
                    'price_child'    => 300.00,
                    'price_guide'    => 0.00,
                    'promotion_id'   => null,
                    'pax_adults'    => 3,
                    'pax_children'    => 0,
                    'pax_guides'    => 0,
                    'customers' => [4, 5, 6],
                ],
                [
                    'id'             => 3,
                    'service_id'     => 3,
                    'season_id'      => 3,
                    'inventory_id'   => 3,
                    'booking_id'     => 3,
                    'price_adult'    => 600.00,
                    'price_child'    => 300.00,
                    'price_guide'    => 0.00,
                    'promotion_id'   => null,
                    'pax_adults'    => 1,
                    'pax_children'    => 1,
                    'pax_guides'    => 1,
                    'customers' => [7, 8],
                ],
                [
                    'id'             => 4,
                    'service_id'     => 4,
                    'season_id'      => 4,
                    'inventory_id'   => 4,
                    'booking_id'     => 4,
                    'price_adult'    => 600.00,
                    'price_child'    => 300.00,
                    'price_guide'    => 0.00,
                    'promotion_id'   => null,
                    'pax_adults'    => 2,
                    'pax_children'    => 0,
                    'pax_guides'    => 1,
                    'customers' => [9, 10],
                ],
            ];

            foreach ($bookingItems as $bookingItem) {
                $bookingItem['price_adult'] = floatval(str_replace('R', '', $bookingItem['price_adult']));
                $bookingItem['price_child'] = floatval(str_replace('R', '', $bookingItem['price_child']));
                $bookingItem['price_guide'] = floatval(str_replace('R', '', $bookingItem['price_guide']));

                $out =  BookingItem::updateOrCreate(['id' => $bookingItem['id']], $bookingItem);
                // Prepare pivot data
                $pivotData = [];

                foreach ($bookingItem['customers'] as $index => $customerId) {
                    $pivotData[$customerId] = [
                        'team_id' => $item['team_id'],         // Or use logic to assign different teams
                        'is_lead' => $index === 0 ? 1 : 0,     // First customer is lead, rest are not
                    ];
                }

                // Sync with pivot data
                $out->customers()->sync($pivotData);
                //$booking->customers()->sync($item['customers']);


            }
            $this->info('bookingItems seeded successfully.');

            $customerManifests = [
                [
                    'id'             => 1,
                    'position'       => '1',
                    'notes'          => '',
                    'is_indemnity'   => 0,
                    'customer_id'    => 1,
                    'booking_item_id'     => 1,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 2,
                    'position'       => '2',
                    'notes'          => '',
                    'is_indemnity'   => 0,
                    'customer_id'    => 2,
                    'booking_item_id'     => 1,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 3,
                    'position'       => '3',
                    'notes'          => '',
                    'is_indemnity'   => 0,
                    'customer_id'    => 3,
                    'booking_item_id'     => 1,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                    'type'           => 'child',
                ],
                [
                    'id'             => 4,
                    'position'       => '1',
                    'notes'          => '',
                    'is_indemnity'   => 0,
                    'customer_id'    => 4,
                    'booking_item_id'     => 2,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 5,
                    'position'       => '2',
                    'notes'          => '',
                    'is_indemnity'   => 0,
                    'customer_id'    => 5,
                    'booking_item_id'     => 2,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 6,
                    'position'       => '3',
                    'notes'          => '',
                    'is_indemnity'   => 0,
                    'customer_id'    => 6,
                    'booking_item_id'     => 2,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 7,
                    'position'       => '1',
                    'notes'          => '',
                    'is_indemnity'   => 0,
                    'customer_id'    => 7,
                    'booking_item_id'     => 3,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 8,
                    'position'       => '2',
                    'notes'          => '',
                    'is_indemnity'   => 0,
                    'customer_id'    => 8,
                    'booking_item_id'     => 3,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                    'type'           => 'child',
                ],
                [
                    'id'             => 9,
                    'position'       => '1',
                    'notes'          => '',
                    'is_indemnity'   => 0,
                    'customer_id'    => 9,
                    'booking_item_id'     => 4,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 10,
                    'position'       => '2',
                    'notes'          => '',
                    'is_indemnity'   => 0,
                    'customer_id'    => 10,
                    'booking_item_id'     => 4,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
            ];

            foreach ($customerManifests as $customerManifest) {
                CustomerManifest::updateOrCreate(['id' => $customerManifest['id']], $customerManifest);
            }

            $bookingDetails = [
                [
                    'id'             => 1,
                    'booking_item_id'     => 1,
                    'is_wholesaler_booking' => 0,
                    'is_needs_pickup' => 0,
                    'is_web_booking' => 0,
                    'is_videos_photos' => 0,
                    'is_exclusive_resource_use' => 0,
                    'is_override_time_slots' => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 2,
                    'booking_item_id'     => 2,
                    'is_wholesaler_booking' => 0,
                    'is_needs_pickup' => 0,
                    'is_web_booking' => 0,
                    'is_videos_photos' => 0,
                    'is_exclusive_resource_use' => 0,
                    'is_override_time_slots' => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 3,
                    'booking_item_id'     => 3,
                    'is_wholesaler_booking' => 0,
                    'is_needs_pickup' => 0,
                    'is_web_booking' => 0,
                    'is_videos_photos' => 0,
                    'is_exclusive_resource_use' => 0,
                    'is_override_time_slots' => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 4,
                    'booking_item_id'     => 4,
                    'is_wholesaler_booking' => 0,
                    'is_needs_pickup' => 0,
                    'is_web_booking' => 0,
                    'is_videos_photos' => 0,
                    'is_exclusive_resource_use' => 0,
                    'is_override_time_slots' => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
            ];

            foreach ($bookingDetails as $bookingDetail) {
                BookingDetail::updateOrCreate(['id' => $bookingDetail['id']], $bookingDetail);
            }

            $booking_inventories = [
                [
                    'id'             => 1,
                    'booking_item_id'     => 1,
                    'booking_detail_id' => 1,
                    'date'           => '2025-03-01',
                    'adults'     => 2,
                    'children'   => 1,
                    'guides'     => 1,
                    'pax'            => 3,
                    'inventory_type_day_id' => 7,
                    'inventory_time_slot_id' => 1,
                    'inventory_override_id' => null,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 2,
                    'booking_item_id'     => 2,
                    'booking_detail_id' => 2,
                    'date'           => '2025-04-02',
                    'adults'     => 3,
                    'children'   => 0,
                    'guides'     => 1,
                    'pax'            => 3,
                    'inventory_type_day_id' => 1,
                    'inventory_time_slot_id' => 2,
                    'inventory_override_id' => null,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 3,
                    'booking_item_id'     => 3,
                    'booking_detail_id' => 3,
                    'date'           => '2025-07-03',
                    'adults'     => 1,
                    'children'   => 1,
                    'guides'     => 1,
                    'pax'            => 2,
                    'inventory_type_day_id' => 2,
                    'inventory_time_slot_id' => 2,
                    'inventory_override_id' => null,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 4,
                    'booking_item_id'     => 4,
                    'booking_detail_id' => 4,
                    'date'           => '2025-07-04',
                    'adults'     => 2,
                    'children'   => 0,
                    'guides'     => 1,
                    'pax'            => 2,
                    'inventory_type_day_id' => 3,
                    'inventory_time_slot_id' => 1,
                    'inventory_override_id' => null,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
            ];

            foreach ($booking_inventories as $booking_inventory) {
                BookingInventory::updateOrCreate(['id' => $booking_inventory['id']], $booking_inventory);
            }

            $bookingFees = [
                [
                    'id'             => 1,
                    'booking_item_id'     => 1,
                    'booking_detail_id' => 1,
                    'tot_adults'     => 1200.00,
                    'tot_children'   => 300.00,
                    'tot_guides'     => 0,
                    'is_voucher'     => 0,
                    'voucher_code'   => '',
                    'invoice_note'   => '',
                    'is_email_reminder' => 0,
                    'booking_note'   => '',
                    'tot_amount'     => 1500.00,
                    'tot_paid'       => 1500.00,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 2,
                    'booking_item_id'     => 2,
                    'booking_detail_id' => 2,
                    'tot_adults'     => 1800.00,
                    'tot_children'   => 0,
                    'tot_guides'     => 0,
                    'is_voucher'     => 0,
                    'voucher_code'   => '',
                    'invoice_note'   => '',
                    'is_email_reminder' => 0,
                    'booking_note'   => '',
                    'tot_amount'     => 1800.00,
                    'tot_paid'       => 1800.00,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 3,
                    'booking_item_id'     => 3,
                    'booking_detail_id' => 3,
                    'tot_adults'     => 600.00,
                    'tot_children'   => 300.00,
                    'tot_guides'     => 0,
                    'is_voucher'     => 0,
                    'voucher_code'   => '',
                    'invoice_note'   => '',
                    'is_email_reminder' => 0,
                    'booking_note'   => '',
                    'tot_amount'     => 900.00,
                    'tot_paid'       => 900.00,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
                [
                    'id'             => 4,
                    'booking_item_id'     => 4,
                    'booking_detail_id' => 4,
                    'tot_adults'     => 1200.00,
                    'tot_children'   => 0,
                    'tot_guides'     => 0,
                    'is_voucher'     => 0,
                    'voucher_code'   => '',
                    'invoice_note'   => '',
                    'is_email_reminder' => 0,
                    'booking_note'   => '',
                    'tot_amount'     => 1200.00,
                    'tot_paid'       => 1200.00,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => 7,
                ],
            ];
            foreach ($bookingFees as $bookingFee) {
                BookingFee::updateOrCreate(['id' => $bookingFee['id']], $bookingFee);
            }

            $bookingInfos = [
                [
                    'id'             => 1,
                    'booking_item_id'     => 1,
                    'type_location_id' => 1,
                    'location'         => 'pickup',
                    'time'             => '08:00:00',
                    'meridiem'         => 'AM',
                    'team_id'        => 7,
                ],
                [
                    'id'             => 2,
                    'booking_item_id'     => 1,
                    'type_location_id' => 2,
                    'location'         => 'start',
                    'time'             => '08:00:00',
                    'meridiem'         => 'AM',
                    'team_id'        => 7,
                ],
                [
                    'id'             => 3,
                    'booking_item_id'     => 1,
                    'type_location_id' => 3,
                    'location'         => 'dropoff',
                    'time'             => '08:00:00',
                    'meridiem'         => 'AM',
                    'team_id'        => 7,
                ],
                [
                    'id'             => 4,
                    'booking_item_id'     => 2,
                    'type_location_id' => 1,
                    'location'         => 'pickup',
                    'time'             => '08:00:00',
                    'meridiem'         => 'AM',
                    'team_id'        => 7,
                ],
                [
                    'id'             => 5,
                    'booking_item_id'     => 2,
                    'type_location_id' => 2,
                    'location'         => 'start',
                    'time'             => '08:00:00',
                    'meridiem'         => 'AM',
                    'team_id'        => 7,
                ],
                [
                    'id'             => 6,
                    'booking_item_id'     => 2,
                    'type_location_id' => 3,
                    'location'         => 'dropoff',
                    'time'             => '08:00:00',
                    'meridiem'         => 'AM',
                    'team_id'        => 7,
                ],
                [
                    'id'             => 7,
                    'booking_item_id'     => 3,
                    'type_location_id' => 1,
                    'location'         => 'pickup',
                    'time'             => '08:00:00',
                    'meridiem'         => 'AM',
                    'team_id'        => 7,
                ],
                [
                    'id'             => 8,
                    'booking_item_id'     => 3,
                    'type_location_id' => 2,
                    'location'         => 'start',
                    'time'             => '08:00:00',
                    'meridiem'         => 'AM',
                    'team_id'        => 7,
                ],
                [
                    'id'             => 9,
                    'booking_item_id'     => 3,
                    'type_location_id' => 3,
                    'location'         => 'dropoff',
                    'time'             => '08:00:00',
                    'meridiem'         => 'AM',
                    'team_id'        => 7,
                ],
                [
                    'id'             => 10,
                    'booking_item_id'     => 4,
                    'type_location_id' => 1,
                    'location'         => 'pickup',
                    'time'             => '08:00:00',
                    'meridiem'         => 'AM',
                    'team_id'        => 7,
                ],
                [
                    'id'             => 11,
                    'booking_item_id'     => 4,
                    'type_location_id' => 2,
                    'location'         => 'start',
                    'time'             => '08:00:00',
                    'meridiem'         => 'AM',
                    'team_id'        => 7,
                ],
                [
                    'id'             => 12,
                    'booking_item_id'     => 4,
                    'type_location_id' => 3,
                    'location'         => 'dropoff',
                    'time'             => '08:00:00',
                    'meridiem'         => 'AM',
                    'team_id'        => 7,
                ],
            ];

            foreach ($bookingInfos as $info) {
                BookingInfo::updateOrCreate(
                    [
                        'booking_item_id' => $info['booking_item_id'],
                        'type_location_id' => $info['type_location_id'],
                    ],
                    $info
                );
            }


            $bookingPayments = [
                [
                    'id'             => 1,
                    'booking_id'     => 1,
                    'type_pay_option_id'   => 1,     // or 'Pay Offline'
                    'type_payment_id' => 1, // Cash // 5 eft
                    'status_payment_id'         => 2,        // 'paid', 'failed', 'cancelled'
                    'ex_vat_amount'         => 1500.00,          // full amount due or partial
                    'lookup_item_id'         => 1,          // full amount due or partial
                    'vat'         => 0.15,          // full amount due or partial
                    'vat_amount'         => 1650.00,          // full amount due or partial
                    'reference'      => $bookings[0]['reference'], //  'OFF-' . strtoupper(Str::random(6)), // can be order ref or external tx
                    'gateway_id'        => 1,        // 'Manual'
                    'meta'           => json_encode([
                        'file_path' => '/assets/icons/plus.svg',
                        'note' => '',
                        'original_name' => '',
                    ]),
                    'payment_date'   =>  now(),
                    'redeemer_name'  => '',
                    'voucher_code' => null,
                    'note'           => '',
                    'updated_by_id'  => null,
                    'deleted_at'     => null,
                    'team_id'        => null,
                ],
                [
                    'id'             => 2,
                    'booking_id'     => 2,
                    'type_pay_option_id'   => 1,     // or 'Pay Offline'
                    'type_payment_id' => 1, // Cash // 5 eft
                    'status_payment_id'         => 2,        // 'paid', 'failed', 'cancelled'
                    'ex_vat_amount'         => 1800.00,          // full amount due or partial
                    'lookup_item_id'         => 1,          // full amount due or partial
                    'vat'         => 0.15,          // full amount due or partial
                    'vat_amount'         => 2070.00,          // full amount due or partial                
                    'reference'      => $bookings[1]['reference'], // 'OFF-' . strtoupper(Str::random(6)), // can be order ref or external tx
                    'gateway_id'        => 1,        // 'Manual'
                    'meta'           => json_encode([
                        'file_path' => '/assets/icons/plus.svg',
                        'note' => '',
                        'original_name' => '',
                    ]),
                    'payment_date'   =>  now(),
                    'redeemer_name'  => '',
                    'voucher_code' => null,
                    'note'           => '',
                    'updated_by_id'  => null,
                    'deleted_at'     => null,
                    'team_id'        => null,
                ],
                [
                    'id'             => 3,
                    'booking_id'     => 3,
                    'type_pay_option_id'   => 1,     // or 'Pay Offline'
                    'type_payment_id' => 1, // Cash // 5 eft
                    'status_payment_id'         => 2,        // 'paid', 'failed', 'cancelled'
                    'ex_vat_amount'         => 900.00,          // full amount due or partial
                    'lookup_item_id'         => 1,          // full amount due or partial
                    'vat'         => 0.15,          // full amount due or partial
                    'vat_amount'         => 1035.00,          // full amount due or partial                
                    'reference'      => $bookings[2]['reference'], //'OFF-' . strtoupper(Str::random(6)), // can be order ref or external tx
                    'gateway_id'        => 1,        // 'Manual'
                    'meta'           => json_encode([
                        'file_path' => '/assets/icons/plus.svg',
                        'note' => '',
                        'original_name' => '',
                    ]),
                    'payment_date'   =>  now(),
                    'redeemer_name'  => '',
                    'voucher_code' => null,
                    'note'           => '',
                    'updated_by_id'  => null,
                    'deleted_at'     => null,
                    'team_id'        => null,
                ],
                [
                    'id'             => 4,
                    'booking_id'     => 4,
                    'type_pay_option_id'   => 1,     // or 'Pay Offline'
                    'type_payment_id' => 1, // Cash // 5 eft
                    'status_payment_id'         => 2,        // 'paid', 'failed', 'cancelled'
                    'ex_vat_amount'         => 1200.00,          // full amount due or partial
                    'lookup_item_id'         => 1,          // full amount due or partial
                    'vat'         => 0.15,          // full amount due or partial
                    'vat_amount'         => 1320.00,          // full amount due or partial                
                    'reference'      => $bookings[3]['reference'], //'OFF-' . strtoupper(Str::random(6)), // can be order ref or external tx
                    'gateway_id'        => 1,        // 'Manual'
                    'meta'           => json_encode([
                        'file_path' => '/assets/icons/plus.svg',
                        'note' => '',
                        'original_name' => '',
                    ]),
                    'payment_date'   =>  now(),
                    'redeemer_name'  => '',
                    'voucher_code' => null,
                    'note'           => '',
                    'updated_by_id'  => null,
                    'deleted_at'     => null,
                    'team_id'        => null,
                ],
            ];
            foreach ($bookingPayments as $bookingPayment) {
                $booking = Booking::findOrFail($bookingPayment['booking_id']);

                // Mark as paid (in real app, you'd validate PayFast response)
                $booking->update([
                    // 'status_booking_id' => 2, // e.g., "paid"
                    'tot_paid' => $bookingPayment['ex_vat_amount'],
                ]);


                BookingPayment::updateOrCreate(['id' => $bookingPayment['id']], $bookingPayment);
                app(QuestionnaireSeeder::class)->seedAll();
                $this->info('Questionnaire defaults seeded successfully.');

                //    booking_id: booking.id,
                //     booking_item_id: bookingItem.id,
                //     team_id: booking?.team_id,




                // php artisan seed:core-data
            }
        }
        $this->info('Core data seeded successfully.');
    }
}
