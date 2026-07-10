<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Partner — Work with us
    |--------------------------------------------------------------------------
    |
    | UI strings for the partner application ("Work with us" form) and the
    | thank-you page.
    |
    */

    // Tab titles (Livewire components)
    'title_work_with_us' => 'AnimalAmo — Work with us',
    'title_thanks' => 'AnimalAmo — Thank you',

    // Heading
    'heading' => 'List your property or a pet-friendly business',
    'intro' => 'We are always open to expanding the options for the people who choose Animal-amo. Propose your business and partner with us!',

    // Form fields
    'first_name' => 'First name',
    'last_name' => 'Last name',
    'email' => 'Email',
    'phone' => 'Mobile',
    'website' => 'Website',
    'city' => 'City',
    'business_name' => 'Business name',
    'role' => 'Your role',
    'offer_type' => 'Offer type',
    'description' => 'Description',
    'description_placeholder' => 'Describe the service you would like to offer, the location and a few details',

    // Roles
    'role_owner' => 'Owner',
    'role_manager' => 'Manager',
    'role_employee' => 'Employee',
    'role_other' => 'Other',

    // Offer types
    'offer_accommodation' => 'Hotels and accommodation',
    'offer_activities' => 'Activities and Events',
    'offer_dining' => 'Dining',
    'offer_pet_services' => 'Pet services',
    'offer_other' => 'Other',

    // CTA
    'submit' => 'Send',

    // Thank-you page
    'thanks_heading' => 'Thank you!',
    'thanks_line_1' => 'Your request has been submitted successfully.',
    'thanks_line_2' => 'We will get back to you as soon as possible.',
    'back_home' => 'Back to Home',

    /*
    |--------------------------------------------------------------------------
    | B2B chrome (partner-area header + footer)
    |--------------------------------------------------------------------------
    */
    'nav_help' => 'Help',
    'nav_dashboard' => 'Dashboard',
    'nav_create_service' => 'Create service',
    'nav_my_services' => 'My services',
    'nav_bookings' => 'Bookings',
    'nav_profile' => 'Profile',
    'help_contact' => 'Contact us',
    'help_support' => 'Support',
    'help_faq' => 'Faq',

    'footer_company' => 'Company',
    'footer_about' => 'About Animal_Amo',
    'footer_website' => 'Website',
    'footer_help' => 'Help & Support',
    'footer_security' => 'Security',
    'footer_privacy' => 'Privacy Policy',
    'footer_cookie' => 'Cookie Policy',
    'footer_terms' => 'Terms and Conditions',

    /*
    |--------------------------------------------------------------------------
    | Partner registration — step 1 (Personal information)
    |--------------------------------------------------------------------------
    */
    'register' => [
        'title' => 'AnimalAmo — Partner registration',
        'heading' => 'Join us as a partner',
        'step' => 'Step 1 of 2',
        'section_personal' => 'Personal information',
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'business_name' => 'Company name',
        'email' => 'Email',
        'address' => 'Address',
        'province' => 'Province',
        'zip' => 'Postcode',
        'phone' => 'Mobile',
        'vat' => 'VAT number',
        'tax_code' => 'Tax code',
        'pec' => 'PEC',
        'sdi' => 'SDI',
        'back' => 'Back',
        'next' => 'Continue',
    ],

    /*
    |--------------------------------------------------------------------------
    | Partner registration — step 2 (service type selection)
    |--------------------------------------------------------------------------
    */
    'register2' => [
        'step' => 'Step 2 of 2',
        'section' => 'Select the service you want to offer',
        'struttura_title' => 'Accommodation',
        'struttura_subtitle' => 'Such as Hotel, Farm stay, B&B, and more',
        'attivita_title' => 'Activities and Events',
        'attivita_subtitle' => 'Such as a day trip or a meet-up with your pets',
        'servizi_title' => 'Services',
        'servizi_subtitle' => 'Such as Pet sitting, Training, and more',
        'submit' => 'Create an account',
        'error_required' => 'Select at least one service.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity/event — steps 6-10 (lists reuse the hotel_* strings)
    |--------------------------------------------------------------------------
    */
    'activity_included' => [
        'title' => 'AnimalAmo — What is included',
        'step' => 'Step 6 of 10',
        'heading' => 'What is included?',
        'section' => 'Add the information to describe the services available (you can select more than one option)',
        'helper' => 'It will help the user evaluate the structure.',
    ],
    'activity_animal_services' => [
        'title' => 'AnimalAmo — Animal services',
        'step' => 'Step 7 of 10',
        'heading' => 'What is included for the animals?',
        'section' => 'Add the information to describe the services you offer (you can select more than one option)',
        'helper' => 'It will help the user evaluate the structure.',
    ],
    'activity_cost' => [
        'title' => 'AnimalAmo — Cost',
        'step' => 'Step 8 of 10',
        'heading' => 'Activity cost',
        'section' => 'Select an option',
        'opt_paid' => 'Paid',
        'opt_free' => 'Free',
        'price_label' => 'Cost per person',
        'back' => 'Back',
        'next' => 'Next',
        'error_required' => 'Select a cost option.',
    ],
    'activity_photos' => [
        'title' => 'AnimalAmo — Photos',
        'step' => 'Step 9 of 10',
    ],
    'activity_cancellation' => [
        'title' => 'AnimalAmo — Cancellation',
        'step' => 'Step 10 of 10',
        'save' => 'Save',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity/event — general information (step 5 of 10)
    |--------------------------------------------------------------------------
    */
    'activity_info' => [
        'title' => 'AnimalAmo — General information',
        'step' => 'Step 5 of 10',
        'heading' => 'General information',
        'section_activity' => 'Add the information about your activity',
        'section_event' => 'Add the information about your event',
        'date_start' => 'Start date',
        'date_end' => 'End date',
        'time_start' => 'Start time',
        'time_end' => 'End time',
        'back' => 'Back',
        'next' => 'Next',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity/event — description (step 4 of 10)
    |--------------------------------------------------------------------------
    */
    'activity_description' => [
        'title' => 'AnimalAmo — Description',
        'step' => 'Step 4 of 10',
        'heading' => 'Description',
        'section' => 'Introduce your activity',
        'helper' => 'Give the customer a taste of what they will do in 2 or 3 sentences. This will be the first thing customers read after the title and will inspire them to keep going.',
        'detailed_label' => 'Describe your activity in detail',
        'placeholder' => 'Description',
        'chars' => 'characters',
        'back' => 'Back',
        'next' => 'Next',
        'error_required' => 'Enter a description.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity/event — location (step 3 of 10)
    |--------------------------------------------------------------------------
    */
    'activity_location' => [
        'title' => 'AnimalAmo — Location',
        'step' => 'Step 3 of 10',
        'heading' => 'Location',
        'section' => 'Add the information that describes your activity',
        'helper' => 'It will help the user better understand what the service is.',
        'address' => 'Address',
        'city' => 'City',
        'province' => 'Province',
        'zip' => 'Postcode',
        'meeting_point' => 'Meeting point',
        'back' => 'Back',
        'next' => 'Next',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity/event — name (step 2 of 10)
    |--------------------------------------------------------------------------
    */
    'activity_name' => [
        'title' => 'AnimalAmo — Activity name',
        'step' => 'Step 2 of 10',
        'heading' => 'Your activity name',
        'section' => 'What is the name of your activity?',
        'helper' => 'It will help users find your structure quickly',
        'field_label' => 'Activity name',
        'back' => 'Back',
        'next' => 'Next',
        'error_required' => 'Enter the activity name.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — title (step 2 of 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_name' => [
        'title' => 'AnimalAmo — Smartbox title',
        'step' => 'Step 2 of 12',
        'heading' => 'The smartbox title',
        'section' => 'What is the title of your smartbox?',
        'helper' => 'It will help users find your structure quickly',
        'field_label' => 'Smartbox title',
        'back' => 'Back',
        'next' => 'Next',
        'error_required' => 'Enter the smartbox title.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — description (step 3 of 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_description' => [
        'title' => 'AnimalAmo — Smartbox description',
        'step' => 'Step 3 of 12',
        'heading' => 'Description',
        'section' => 'Introduce your smartbox',
        'helper' => 'Give the customer a taste of what they will do in 2 or 3 sentences. This will be the first thing customers read after the title and will inspire them to continue.',
        'detailed_label' => 'Describe the smartbox in detail',
        'placeholder' => 'Description',
        'chars' => 'characters',
        'back' => 'Back',
        'next' => 'Next',
        'error_required' => 'Enter a description.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — duration (step 4 of 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_duration' => [
        'title' => 'AnimalAmo — Smartbox duration',
        'step' => 'Step 4 of 12',
        'heading' => 'Duration',
        'section' => 'Add the information about the smartbox duration',
        'helper' => 'How many days does the smartbox last?',
        'field_label' => 'No. Days',
        'back' => 'Back',
        'next' => 'Next',
        'error_required' => 'Indicate how many days the smartbox lasts.',
        'error_min' => 'The duration must be at least 1 day.',
        'error_max' => 'The duration cannot exceed 365 days.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — cancellation (step 5 of 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_cancellation' => [
        'title' => 'AnimalAmo — Smartbox cancellation',
        'step' => 'Step 5 of 12',
        'heading' => 'Cancellation',
        'section' => 'When can the guest cancel the booking for free?',
        'helper' => 'They will help the user choose the structure.',
        'when' => 'When?',
        'free' => 'Offer free cancellation',
        'pays' => 'The guest pays the full amount',
        'arrival' => 'Arrival date',
        'days_30' => '30 days',
        'days_15' => '15 days',
        'days_7' => '7 days',
        'days_1' => '1 day',
        'back' => 'Back',
        'next' => 'Next',
        'error_required' => 'Choose when cancellation is free.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — add structures (step 10 of 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_structures' => [
        'title' => 'AnimalAmo — Add structures',
        'step' => 'Step 10 of 12',
        'heading' => 'Add structures',
        'section' => 'Add the structures that will be visible in the smartbox and that users will be able to choose from',
        'load_more' => 'Load more',
        'back' => 'Back',
        'next' => 'Next',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — photos (step 11 of 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_photos' => [
        'title' => 'AnimalAmo — Smartbox photos',
        'step' => 'Step 11 of 12',
        'heading' => 'Photos',
        'section' => 'Upload some photos',
        'helper' => 'They will potentially help increase your conversion rate by an average of 2.7%, in other words, to increase your bookings and earnings.',
        'drop' => 'Drag your photos here',
        'hint' => 'You must upload at least 4 photos (7 or more recommended)',
        'uploading' => 'Uploading…',
        'delete' => 'Delete',
        'error_min' => 'You must upload at least 4 photos.',
        'back' => 'Back',
        'next' => 'Next',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — price / cost (step 12 of 12, final)
    |--------------------------------------------------------------------------
    */
    'smartbox_price' => [
        'title' => 'AnimalAmo — Smartbox cost',
        'step' => 'Step 12 of 12',
        'heading' => 'Smartbox cost',
        'section' => 'Enter the total cost of the smartbox',
        'field_label' => 'Price',
        'back' => 'Back',
        'save' => 'Save',
        'error_required' => 'Enter the smartbox cost.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — what is included (step 8 of 12) — options reuse hotel_services.svc_*
    |--------------------------------------------------------------------------
    */
    'smartbox_included' => [
        'title' => 'AnimalAmo — What is included',
        'step' => 'Step 8 of 12',
        'heading' => 'What is included?',
        'section' => 'Add the information to describe the services available (you can select more than one option)',
        'helper' => 'They will help the user assess the structure.',
        'back' => 'Back',
        'next' => 'Next',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — what is included for animals (step 9 of 12) — options reuse hotel_animal_services.*
    |--------------------------------------------------------------------------
    */
    'smartbox_included_animals' => [
        'title' => 'AnimalAmo — What is included for animals',
        'step' => 'Step 9 of 12',
        'heading' => 'What is included for animals?',
        'section' => 'Add the information to describe the services you offer (you can select more than one option)',
        'helper' => 'They will help the user assess the structure.',
        'back' => 'Back',
        'next' => 'Next',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — accommodation / what you will find (step 7 of 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_offers' => [
        'title' => 'AnimalAmo — What the smartbox offers',
        'step' => 'Step 7 of 12',
        'heading' => 'What the smartbox offers',
        'section' => 'Select the options that will be present in the smartbox',
        'helper' => 'They will help the user assess your proposal.',
        'additional_heading' => 'Additional services available',
        'amenity_bedroom' => 'Bedroom',
        'amenity_bathroom' => 'Bathroom',
        'amenity_kitchen' => 'Kitchen',
        'amenity_balcony' => 'Balcony',
        'amenity_terrace' => 'Terrace',
        'add_pool' => 'Pool',
        'add_spa' => 'Spa',
        'add_tennis' => 'Tennis court',
        'back' => 'Back',
        'next' => 'Next',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — food / meals (step 6 of 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_meals' => [
        'title' => 'AnimalAmo — Smartbox food',
        'step' => 'Step 6 of 12',
        'heading' => 'Food',
        'section' => 'Select one or more options',
        'meal_none' => 'None',
        'meal_breakfast' => 'Breakfast',
        'meal_lunch' => 'Lunch',
        'meal_dinner' => 'Dinner',
        'times_heading' => 'Enter the times',
        'time_from' => 'Start time',
        'time_to' => 'End time',
        'dietary_heading' => 'Which dietary restrictions can you accommodate?',
        'diet_diabetic' => 'Diabetic',
        'diet_vegan' => 'Vegan',
        'diet_vegetarian' => 'Vegetarian',
        'diet_gluten_free' => 'Gluten-free',
        'diet_egg_free' => 'Egg-free',
        'diet_lactose_free' => 'Lactose-free',
        'back' => 'Back',
        'next' => 'Next',
    ],

    /*
    |--------------------------------------------------------------------------
    | Create a smartbox — type (step 1 of 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_type' => [
        'title' => 'AnimalAmo — Create a smartbox',
        'step' => 'Step 1 of 12',
        'heading' => 'Create a smartbox',
        'soggiorno' => 'Stay',
        'benessere' => 'Wellness',
        'avventura' => 'Adventure',
        'back' => 'Back',
        'next' => 'Next',
        'error_required' => 'Select a type.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity/event type (step 1 of 10 of the activities & events flow)
    |--------------------------------------------------------------------------
    */
    'activity_type' => [
        'title' => 'AnimalAmo — Activities and Events',
        'step' => 'Step 1 of 10',
        'attivita' => 'Activity',
        'eventi' => 'Event',
        'back' => 'Back',
        'next' => 'Next',
        'error_required' => 'Select a type.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Create service (type selection)
    |--------------------------------------------------------------------------
    */
    'create_service' => [
        'title' => 'AnimalAmo — Create service',
        'heading' => 'Create a new service',
        'section' => 'Select the service you want to offer',
        'helper' => 'This helps us classify your product so that customers can find it.',
        'struttura_title' => 'Accommodation',
        'struttura_subtitle' => 'Such as Hotel, Farm stay, B&B, and more',
        'attivita_title' => 'Activities and Events',
        'attivita_subtitle' => 'Such as a day trip or a meet-up with your pets',
        'servizi_title' => 'Services',
        'servizi_subtitle' => 'Such as Pet sitting, Training, and more',
        'smartbox_title' => 'Smartbox',
        'smartbox_subtitle' => 'An experience to give as a gift',
        'back' => 'Back',
        'next' => 'Next',
        'error_required' => 'Select a service.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — payment method (step 11 of 11)
    |--------------------------------------------------------------------------
    */
    'hotel_payment' => [
        'title' => 'AnimalAmo — Payment method',
        'step' => 'Step 11 of 11',
        'heading' => 'Payment method',
        'section' => 'This information will allow you to receive payments',
        'account_holder' => 'Account holder',
        'iban' => 'IBAN',
        'sdi' => 'SDI',
        'bic' => 'BIC',
        'later' => 'Enter later',
        'back' => 'Back',
        'next' => 'Next',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — photos (step 10 of 11)
    |--------------------------------------------------------------------------
    */
    'hotel_photos' => [
        'title' => 'AnimalAmo — Photos',
        'step' => 'Step 10 of 11',
        'heading' => 'Photos',
        'section' => 'Upload some photos',
        'helper' => 'They will potentially increase your conversion rate by an average of 2.7%, in other words, to boost your bookings and earnings.',
        'drop' => 'Drag your photos here',
        'hint' => 'You must upload at least 4 photos (7 or more recommended)',
        'uploading' => 'Uploading…',
        'delete' => 'Delete',
        'back' => 'Back',
        'next' => 'Next',
        'error_min' => 'You must upload at least 4 photos.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — smartbox (step 9 of 11)
    |--------------------------------------------------------------------------
    */
    'hotel_smartbox' => [
        'title' => 'AnimalAmo — Smartbox',
        'step' => 'Step 9 of 11',
        'heading' => 'Additional information',
        'section' => 'Do you want to give your structure the option to be included in smartboxes?',
        'helper' => 'Anyone creating a smartbox will be able to add your structure to the list of structures. This way, users who buy the smartbox can come to your structure.',
        'yes' => 'Yes',
        'no' => 'No',
        'types_heading' => 'Select the service you want to join the smartboxes',
        'type_all' => 'The whole structure',
        'type_overnight' => 'Overnight stay',
        'type_wellness' => 'Wellness',
        'type_wellness_desc' => 'If there is a pool, spa, etc. that can be used even without an overnight stay',
        'type_adventure' => 'Adventure',
        'type_adventure_desc' => 'If there are affiliated guides, tastings, etc.',
        'back' => 'Back',
        'next' => 'Next',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — animal services (step 8 of 11)
    |--------------------------------------------------------------------------
    */
    'hotel_animal_services' => [
        'title' => 'AnimalAmo — Animal services',
        'step' => 'Step 8 of 11',
        'heading' => 'Services dedicated to animals',
        'section' => 'Add the information to describe the services you offer (you can select more than one option)',
        'helper' => 'It will help the user evaluate the structure.',
        'opt_none' => 'None',
        'opt_welcome' => 'Welcome gift',
        'opt_welcome_desc' => 'A welcome gift for the animal',
        'opt_petsitting' => 'Pet sitting',
        'opt_petsitting_desc' => 'Pet sitting service',
        'opt_vet' => 'Veterinary service',
        'opt_vet_desc' => 'Service inside the structure or nearby',
        'opt_area' => 'Animal area',
        'opt_area_desc' => 'An area dedicated to animals',
        'opt_other' => 'Other',
        'other_placeholder' => 'Describe the service',
        'back' => 'Back',
        'next' => 'Next',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — structure services (step 7 of 11)
    |--------------------------------------------------------------------------
    */
    'hotel_services' => [
        'title' => 'AnimalAmo — Structure services',
        'step' => 'Step 7 of 11',
        'heading' => 'Information about the structure services',
        'section' => 'Add the information to describe the services available (you can select more than one option)',
        'helper' => 'It will help the user evaluate the structure.',
        'additional_heading' => 'Additional services available',
        'rules_heading' => 'Structure rules',
        'other_placeholder' => 'Describe the service',
        'time_from' => 'Start time',
        'time_to' => 'End time',
        'svc_ac' => 'Air conditioning',
        'svc_heating' => 'Heating',
        'svc_wifi' => 'Free Wi-Fi',
        'svc_ev' => 'Electric vehicle charging station',
        'svc_tv' => 'TV',
        'svc_pool' => 'Swimming pool',
        'svc_sauna' => 'Sauna',
        'add_none' => 'None',
        'add_breakfast' => 'Breakfast',
        'add_lunch' => 'Lunch',
        'add_dinner' => 'Dinner',
        'add_other' => 'Other',
        'rule_no_smoking' => 'No smoking',
        'rule_no_parties' => 'No parties/events',
        'back' => 'Back',
        'next' => 'Next',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — cancellation (step 6 of 11)
    |--------------------------------------------------------------------------
    */
    'hotel_cancellation' => [
        'title' => 'AnimalAmo — Cancellation',
        'step' => 'Step 6 of 11',
        'heading' => 'Cancellation',
        'section' => 'When can the guest cancel the booking for free?',
        'helper' => 'It will help the user choose the structure.',
        'when' => 'When?',
        'free' => 'Offer free cancellation',
        'pays' => 'The guest pays the full amount',
        'arrival' => 'Arrival date',
        'days_30' => '30 days',
        'days_15' => '15 days',
        'days_7' => '7 days',
        'days_1' => '1 day',
        'back' => 'Back',
        'next' => 'Next',
        'error_required' => 'Select when free cancellation is possible.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — room information (step 5 of 11)
    |--------------------------------------------------------------------------
    */
    'hotel_rooms' => [
        'title' => 'AnimalAmo — Room information',
        'step' => 'Step 5 of 11',
        'heading' => 'Room information',
        'section' => 'Add the information that describes the rooms you offer',
        'helper' => 'It will help the user evaluate the structure.',
        'room_type' => 'Room type',
        'room_count' => 'Number of rooms',
        'price' => 'Price',
        'type_single' => 'Single',
        'type_double' => 'Double',
        'type_triple' => 'Triple',
        'type_suite' => 'Suite',
        'add_rooms' => 'Add rooms',
        'checkin' => 'Check in',
        'checkout' => 'Check out',
        'from' => 'From:',
        'to' => 'To:',
        'back' => 'Back',
        'next' => 'Next',
        'error_required' => 'Fill in the room information and the check-in/out times.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — description (step 4 of 11)
    |--------------------------------------------------------------------------
    */
    'hotel_description' => [
        'title' => 'AnimalAmo — Description',
        'step' => 'Step 4 of 11',
        'heading' => 'Description',
        'section' => 'Introduce your service',
        'helper' => 'Give the customer a taste of what they will do in 2 or 3 sentences. This will be the first thing customers read after the title and will inspire them to keep going.',
        'placeholder' => 'Description',
        'chars' => 'characters',
        'back' => 'Back',
        'next' => 'Next',
        'error_required' => 'Enter a description.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — location (step 3 of 11)
    |--------------------------------------------------------------------------
    */
    'hotel_location' => [
        'title' => 'AnimalAmo — Location',
        'step' => 'Step 3 of 11',
        'heading' => 'Location',
        'section' => 'Add the information that describes your service',
        'helper' => 'It will help the user better understand what the service is.',
        'address' => 'Address',
        'city' => 'City',
        'province' => 'Province',
        'zip' => 'Postcode',
        'license' => 'Opening licence',
        'back' => 'Back',
        'next' => 'Next',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — structure name (step 2 of 11)
    |--------------------------------------------------------------------------
    */
    'hotel_title' => [
        'title' => 'AnimalAmo — Structure name',
        'step' => 'Step 2 of 11',
        'heading' => 'Your structure name',
        'section' => 'What is the name of your structure?',
        'helper' => 'It will help users find your structure quickly',
        'field_label' => 'Structure name',
        'back' => 'Back',
        'next' => 'Next',
        'error_required' => 'Enter the structure name.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Structure type (step 1 of 11 of the accommodation flow)
    |--------------------------------------------------------------------------
    */
    'structure_type' => [
        'title' => 'AnimalAmo — Structure type',
        'step' => 'Step 1 of 11',
        'hotel' => 'Hotel',
        'bb' => 'B&B',
        'agriturismo' => 'Farm stay',
        'back' => 'Back',
        'next' => 'Next',
        'error_required' => 'Select a structure type.',
    ],

    /*
    |--------------------------------------------------------------------------
    | B2B dashboard
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'title' => 'AnimalAmo — Partner dashboard',
        'welcome' => 'Welcome :name',
        'intro' => 'Create your first product and share unforgettable experiences with millions of travellers.',
        'cta' => 'Create your first service',
        'stat_sold' => 'Experiences sold',
        'stat_cancelled' => 'Experiences cancelled',
        'stat_saved' => 'Experiences saved',
    ],

];
