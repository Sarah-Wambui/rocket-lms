<?php

/*
| CVH Africa — Homepage Editor field definitions (admin: Appearance > Homepage Editor).
| One entry per homepage section type; keys match the JSON the front-end section templates read
| (resources/views/landingBuilder/front/components/<name>/index.blade.php).
|
| Field types:
|   text, textarea, url, number, image (upload or path), icon (iconsax name e.g. bul-teacher),
|   select (options: [value => label] or source: courses|trend_categories|instructors|stat_sources|colors),
|   toggle (stored as "on"), group (fields: nested object), list (item: single field type, keyed map),
|   repeater (fields: object per item, keyed map; a "record" placeholder key is preserved untouched)
*/

$button = ['type' => 'group', 'fields' => [
    'label' => ['type' => 'text', 'label' => 'Button text'],
    'icon' => ['type' => 'icon', 'label' => 'Button icon'],
    'url' => ['type' => 'url', 'label' => 'Button link'],
]];

return [
    'two_columns_hero' => [
        'title' => 'Hero (top banner)',
        'fields' => [
            'upper_cta' => ['type' => 'group', 'label' => 'Small badge above the headline', 'fields' => [
                'badge_text' => ['type' => 'text', 'label' => 'Badge text (e.g. Live)'],
                'main_text' => ['type' => 'text', 'label' => 'Badge message'],
                'icon' => ['type' => 'icon', 'label' => 'Badge icon'],
                'url' => ['type' => 'url', 'label' => 'Badge link'],
            ]],
            'main_content' => ['type' => 'group', 'label' => 'Headline and text', 'fields' => [
                'title_line_1' => ['type' => 'text', 'label' => 'Headline — first word(s)', 'help' => 'Keep short (e.g. "Learn") so the rotating word fits on the same line.'],
                'highlight_words' => ['type' => 'list', 'label' => 'Rotating highlighted words', 'item' => ['type' => 'text']],
                'title_line_2' => ['type' => 'text', 'label' => 'Headline — second line'],
                'description' => ['type' => 'textarea', 'label' => 'Description'],
                'primary_button' => ['label' => 'Main button'] + $button,
                'secondary_button' => ['label' => 'Second button'] + $button,
            ]],
            'students_widget' => ['type' => 'group', 'label' => 'Learners badge', 'fields' => [
                'title' => ['type' => 'text', 'label' => 'Text (e.g. Join 257+ learners)'],
                'url' => ['type' => 'url', 'label' => 'Link'],
            ]],
            'students_widget_avatars' => ['type' => 'list', 'label' => 'Learner avatar photos', 'item' => ['type' => 'image']],
            'image_content' => ['type' => 'group', 'label' => 'Images', 'fields' => [
                'image' => ['type' => 'image', 'label' => 'Main photo (about 936 × 915)'],
                'overlay_image' => ['type' => 'image', 'label' => 'Overlay card image'],
                'spinning_image' => ['type' => 'image', 'label' => 'Spinning badge image'],
            ]],
            'background' => ['type' => 'image', 'label' => 'Background image'],
        ],
    ],

    'statistics' => [
        'title' => 'Statistics bar',
        'fields' => [
            'statistics' => ['type' => 'repeater', 'label' => 'Statistics', 'fields' => [
                'title' => ['type' => 'text', 'label' => 'Label'],
                'icon' => ['type' => 'icon', 'label' => 'Icon'],
                'data_type' => ['type' => 'select', 'label' => 'Value type', 'options' => ['manual' => 'Fixed value (type it below)', 'real' => 'Live count from the platform', 'semi_real' => 'Live count + starting number']],
                'manual_data' => ['type' => 'text', 'label' => 'Fixed value (e.g. 15+)'],
                'data_source' => ['type' => 'select', 'label' => 'Live count of', 'source' => 'stat_sources'],
                'start_from' => ['type' => 'number', 'label' => 'Starting number (live + starting number only)'],
            ]],
            'background_color' => ['type' => 'select', 'label' => 'Background colour', 'source' => 'colors'],
            'background' => ['type' => 'image', 'label' => 'Background pattern image'],
        ],
    ],

    'featured_courses' => [
        'title' => 'Featured courses',
        'fields' => [
            'main_content' => ['type' => 'group', 'label' => 'Heading', 'fields' => [
                'pre_title' => ['type' => 'text', 'label' => 'Small label'],
                'title' => ['type' => 'text', 'label' => 'Title'],
                'description' => ['type' => 'textarea', 'label' => 'Description'],
            ]],
            'enable_slider' => ['type' => 'toggle', 'label' => 'Show as a slider'],
            'featured_courses' => ['type' => 'repeater', 'label' => 'Featured courses', 'fields' => [
                'course' => ['type' => 'select', 'label' => 'Course', 'source' => 'courses'],
                'cover_image' => ['type' => 'image', 'label' => 'Wide cover image (about 1920 × 530)'],
                'checked_items' => ['type' => 'list', 'label' => 'Bullet points', 'item' => ['type' => 'text']],
            ]],
            'floating_background' => ['type' => 'image', 'label' => 'Decorative background'],
        ],
    ],

    'trending_categories' => [
        'title' => 'Course categories',
        'fields' => [
            'main_content' => ['type' => 'group', 'label' => 'Heading', 'fields' => [
                'pre_title' => ['type' => 'text', 'label' => 'Small label'],
                'title' => ['type' => 'text', 'label' => 'Title'],
            ]],
            'trending_categories' => ['type' => 'repeater', 'label' => 'Categories shown', 'fields' => [
                'category' => ['type' => 'select', 'label' => 'Category', 'source' => 'trend_categories'],
            ]],
            'floating_background' => ['type' => 'image', 'label' => 'Decorative background'],
            'background' => ['type' => 'image', 'label' => 'Background image'],
        ],
    ],

    'free_courses' => [
        'title' => 'Free courses',
        'fields' => [
            'main_content' => ['type' => 'group', 'label' => 'Heading', 'fields' => [
                'title' => ['type' => 'text', 'label' => 'Title'],
                'subtitle' => ['type' => 'textarea', 'label' => 'Subtitle'],
            ]],
            'cta_section' => ['type' => 'group', 'label' => 'Call-to-action box', 'fields' => [
                'title' => ['type' => 'text', 'label' => 'Title'],
                'description' => ['type' => 'textarea', 'label' => 'Description'],
                'link_title' => ['type' => 'text', 'label' => 'Link text'],
                'url' => ['type' => 'url', 'label' => 'Link'],
                'icon' => ['type' => 'image', 'label' => 'Illustration'],
            ]],
            'background_color' => ['type' => 'select', 'label' => 'Background colour', 'source' => 'colors'],
            'background' => ['type' => 'image', 'label' => 'Background image'],
        ],
    ],

    'newest_courses' => [
        'title' => 'Newest courses',
        'fields' => [
            'main_content' => ['type' => 'group', 'label' => 'Heading', 'fields' => [
                'pre_title' => ['type' => 'text', 'label' => 'Small label'],
                'title' => ['type' => 'text', 'label' => 'Title'],
                'subtitle' => ['type' => 'textarea', 'label' => 'Subtitle'],
            ]],
            'cta_section' => ['type' => 'group', 'label' => 'Call-to-action box', 'fields' => [
                'title_bold_text' => ['type' => 'text', 'label' => 'Bold text'],
                'title_regular_text' => ['type' => 'text', 'label' => 'Regular text'],
                'subtitle' => ['type' => 'text', 'label' => 'Subtitle'],
                'icon' => ['type' => 'icon', 'label' => 'Icon'],
                'button' => ['label' => 'Button'] + $button,
            ]],
            'background' => ['type' => 'image', 'label' => 'Background image'],
        ],
    ],

    'best_rated_courses' => [
        'title' => 'Top rated courses',
        'fields' => [
            'main_content' => ['type' => 'group', 'label' => 'Heading', 'fields' => [
                'title' => ['type' => 'text', 'label' => 'Title'],
                'subtitle' => ['type' => 'textarea', 'label' => 'Subtitle'],
                'icon' => ['type' => 'image', 'label' => 'Illustration'],
            ]],
            'background' => ['type' => 'image', 'label' => 'Background image'],
        ],
    ],

    'best_selling_courses' => [
        'title' => 'Best-selling courses',
        'fields' => [
            'main_content' => ['type' => 'group', 'label' => 'Heading', 'fields' => [
                'title' => ['type' => 'text', 'label' => 'Title'],
                'subtitle' => ['type' => 'textarea', 'label' => 'Subtitle'],
                'icon' => ['type' => 'image', 'label' => 'Illustration'],
            ]],
            'background' => ['type' => 'image', 'label' => 'Background image'],
        ],
    ],

    'discounted_courses' => [
        'title' => 'Discounted courses',
        'fields' => [
            'main_content' => ['type' => 'group', 'label' => 'Heading', 'fields' => [
                'title' => ['type' => 'text', 'label' => 'Title'],
                'subtitle' => ['type' => 'textarea', 'label' => 'Subtitle'],
                'icon' => ['type' => 'image', 'label' => 'Illustration'],
            ]],
            'cta_section' => ['type' => 'group', 'label' => 'Call-to-action box', 'fields' => [
                'title_bold_text' => ['type' => 'text', 'label' => 'Bold text'],
                'title_regular_text' => ['type' => 'text', 'label' => 'Regular text'],
                'icon' => ['type' => 'icon', 'label' => 'Icon'],
                'button' => ['label' => 'Button'] + $button,
            ]],
            'background' => ['type' => 'image', 'label' => 'Background image'],
        ],
    ],

    'instructors' => [
        'title' => 'Instructors',
        'fields' => [
            'main_content' => ['type' => 'group', 'label' => 'Heading', 'fields' => [
                'pre_title' => ['type' => 'text', 'label' => 'Small label'],
                'title' => ['type' => 'text', 'label' => 'Title'],
                'description' => ['type' => 'textarea', 'label' => 'Description'],
                'button' => ['label' => 'Button'] + $button,
            ]],
            'specific_instructors' => ['type' => 'repeater', 'label' => 'Instructors shown', 'fields' => [
                'instructor_id' => ['type' => 'select', 'label' => 'Instructor', 'source' => 'instructors'],
            ]],
            'cta_section' => ['type' => 'group', 'label' => 'Line under the list', 'fields' => [
                'title_bold_text' => ['type' => 'text', 'label' => 'Bold text'],
                'title_regular_text' => ['type' => 'text', 'label' => 'Regular text'],
                'description' => ['type' => 'text', 'label' => 'Description'],
                'icon' => ['type' => 'icon', 'label' => 'Icon'],
            ]],
            'background' => ['type' => 'image', 'label' => 'Background image'],
        ],
    ],

    'blog' => [
        'title' => 'Blog / news',
        'fields' => [
            'main_content' => ['type' => 'group', 'label' => 'Heading', 'fields' => [
                'pre_title' => ['type' => 'text', 'label' => 'Small label'],
                'title' => ['type' => 'text', 'label' => 'Title'],
                'description' => ['type' => 'textarea', 'label' => 'Description'],
                'button' => ['label' => 'Button'] + $button,
            ]],
            'background' => ['type' => 'image', 'label' => 'Background image'],
        ],
    ],

    'cvh_partners' => [
        'title' => 'Partners logos',
        'fields' => [
            'main_content' => ['type' => 'group', 'label' => 'Heading', 'fields' => [
                'pre_title' => ['type' => 'text', 'label' => 'Small label'],
                'title' => ['type' => 'text', 'label' => 'Title'],
                'description' => ['type' => 'textarea', 'label' => 'Description'],
            ]],
            'logos' => ['type' => 'repeater', 'label' => 'Partner logos', 'fields' => [
                'name' => ['type' => 'text', 'label' => 'Partner name'],
                'image' => ['type' => 'image', 'label' => 'Logo'],
            ]],
            'button' => ['type' => 'group', 'label' => 'Button', 'fields' => [
                'label' => ['type' => 'text', 'label' => 'Button text'],
                'url' => ['type' => 'url', 'label' => 'Button link'],
            ]],
        ],
    ],

    'vertical_spacer' => [
        'title' => 'Spacer',
        'fields' => [
            'space_number' => ['type' => 'number', 'label' => 'Height in pixels'],
        ],
    ],
];
