<?php
/**
 * CVH Africa rebrand — phase 2: instructors, courses, project bundles, live session, blog,
 * Innovations pages, navigation, homepage references, and hiding the template's demo content.
 * Run from the project root:  php database/cvh/02_content.php
 * Re-runnable: records are matched by slug/email and updated in place.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

const CVH = '/store/cvh';
const ADMIN_ID = 1;
$now = time();

function upsertId(string $table, array $match, array $values): int
{
    $id = DB::table($table)->where($match)->value('id');
    if ($id) {
        if ($values) {
            DB::table($table)->where('id', $id)->update($values);
        }
        return $id;
    }
    return DB::table($table)->insertGetId(array_merge($match, $values));
}

function translation(string $table, string $fk, int $id, array $values): void
{
    DB::table($table)->updateOrInsert([$fk => $id, 'locale' => 'en'], $values);
}

function updateLandingComponent(int $id, callable $mutate): void
{
    $row = DB::table('landing_component_translations')->where('landing_component_id', $id)->where('locale', 'en')->first();
    DB::table('landing_component_translations')->where('id', $row->id)
        ->update(['content' => json_encode($mutate(json_decode($row->content, true)))]);
}

function updateHeaderFooter(int $id, callable $mutate): void
{
    $row = DB::table('theme_header_footer_translations')->where('theme_header_footer_id', $id)->where('locale', 'en')->first();
    DB::table('theme_header_footer_translations')->where('id', $row->id)
        ->update(['content' => json_encode($mutate(json_decode($row->content, true)))]);
}

function ul(array $items): string
{
    return '<ul>' . implode('', array_map(fn($i) => "<li>{$i}</li>", $items)) . '</ul>';
}

$learningExperience = ul([
    '100% online and mobile-friendly — learn anywhere, anytime',
    'Multimedia content — videos, visuals and activities',
    'Downloadable resources and tools',
    'Knowledge checks and quizzes',
    'Self-paced — progress at your own speed',
    'Free certificate upon completion',
]);

DB::transaction(function () use ($now, $learningExperience) {

    /* ---------- Admin account shown as "CVH Africa" ---------- */
    DB::table('users')->where('id', ADMIN_ID)->update([
        'full_name' => 'CVH Africa', 'bio' => 'Community Virtual Hub', 'headline' => 'Community Virtual Hub',
        'avatar' => CVH . '/brand/favicon.png',
    ]);

    /* ---------- Instructors & mentor ---------- */
    $teachers = [];
    foreach ([
        ['Arthur Wamani', 'Learning Instructor', 'arthur-wamani'],
        ['Hamis Hincha', 'Learning Instructor', 'hamis-hincha'],
        ['Benadict Isiaho', 'Learning Instructor', 'benadict-isiaho'],
        ['Dr. Angeline Maina', 'Learning Instructor', 'dr-angeline-maina'],
        ['Dr. Sanjukta Badhai', 'Mentor — Business Development Expert', 'dr-sanjukta-badhai'],
    ] as [$name, $headline, $slug]) {
        // Placeholder e-mails: replace with real addresses in Admin → Users.
        $teachers[$slug] = upsertId('users', ['email' => "{$slug}@instructors.cvh.local"], [
            'full_name' => $name, 'role_name' => 'teacher', 'role_id' => 4,
            'password' => DB::table('users')->where('email', "{$slug}@instructors.cvh.local")->value('password') ?? Hash::make(Str::random(32)),
            'bio' => Str::limit($headline, 120, ''), 'headline' => $headline,
            'about' => "{$name} is a {$headline} at the Community Virtual Hub (CVH Africa).",
            'avatar' => CVH . "/people/{$slug}.jpg",
            'verified' => 1, 'status' => 'active', 'access_content' => 1, 'public_message' => 0,
            'timezone' => 'Africa/Nairobi', 'language' => 'EN', 'created_at' => $now,
        ]);
    }

    /* ---------- Courses (placeholders: titles, images, categories, project descriptions) ---------- */
    $cat = fn($slug) => DB::table('categories')->where('slug', $slug)->value('id');

    $hd4aIntro = '<p>This free course is part of the <strong>HealthyDiets4Africa (HD4A)</strong> learning programme on the Community Virtual Hub, designed to strengthen the capacity of African youth, women, food system actors and local communities to promote healthier, diverse and sustainable diets.</p>';
    $innoIntro = '<p>This module is part of the <strong>INNOECOFOOD</strong> Training of Trainers (ToT) programme — an EU Horizon-funded initiative transforming aquaculture and sustainable food production in six African countries by combining artificial intelligence (AI), Internet of Things (IoT) and circular bioeconomy principles.</p><p>The modules equip community-level trainers to support rural farmers, youth and women in establishing sustainable, innovative and market-oriented micro-enterprises using climate-smart technologies and renewable energy.</p>';

    $courses = [
        // slug, title, category, image, summary, intro
        ['acrylamide-occurrence-in-foods-health-concerns-and-mitigation-strategies', 'Acrylamide Occurrence in Foods, Health Concerns and Mitigation Strategies', 'Food-Safety', 'acrylamide',
            'Learn how acrylamide forms in foods, the health concerns it raises, and practical strategies to reduce it.', $hd4aIntro],
        ['mycotoxins-in-foods-the-health-risk-and-preventive-measures', 'Mycotoxins in Foods, The Health Risk and Preventive Measures', 'Food-Safety', 'mycotoxins',
            'Understand mycotoxin contamination in foods, the health risks it poses, and the measures that prevent it.', $hd4aIntro],
        ['reducing-heavy-metal-exposure-in-foods', 'Reducing Heavy Metal Exposure in Foods', 'Food-Safety', 'heavy-metals',
            'Explore how heavy metals enter the food chain and how to reduce exposure from farm to fork.', $hd4aIntro],
        ['basic-introduction-to-entrepreneurship', 'Basic Introduction to Entrepreneurship', 'Agripreneurship', 'entrepreneurship',
            'Foundational entrepreneurship skills for youth innovators and women agripreneurs in the food system.', $hd4aIntro],
        ['design-thinking-for-food-system-innovations', 'Design Thinking for Food System Innovations', 'Agripreneurship', 'design-thinking',
            'Apply the design thinking process — empathize, define, ideate, prototype, test — to food system innovations.', $hd4aIntro],
        ['managing-business-start-up', 'Managing Business Start-Up', 'Agripreneurship', 'business-start-up',
            'Find the business and financial skills you need to build and run your own agribusiness start-up.', $hd4aIntro],
        ['business-operations-part-1', 'Business Operations Part 1', 'Agripreneurship', 'business-operations',
            'Build the operational skills needed to run and scale a food or agribusiness venture.', $hd4aIntro],
        ['climate-change-and-agriculture', 'Climate Change and Agriculture', 'Climate-and-Agriculture', 'climate-agriculture',
            'Understand how climate change affects agriculture and how to build resilient, sustainable food systems.',
            '<p>This free Community Virtual Hub course connects to the <strong>Sustainability and Environmental Impact</strong> component of the food system, helping farmers and food system actors build resilience to climate and economic shocks.</p>'],
        ['innoecofood-fish', 'INNOECOFOOD: Fish Value Chain', 'Aquaculture', 'inno-fish',
            'Sustainable fish production, processing and marketing, including AI and IoT in aquaculture and RAS/IPRS technologies.', $innoIntro],
        ['innoecofood-spirulina', 'INNOECOFOOD: Spirulina Value Chain', 'Aquaculture', 'inno-spirulina',
            'Sustainable spirulina production and processing for nutrition, income and trade.', $innoIntro],
        ['innoecofood-insects', 'INNOECOFOOD: Insect Value Chain', 'Aquaculture', 'inno-insects',
            'Sustainable insect production and processing as a nutritious, circular food and feed source.', $innoIntro],
        ['innoecofood-circularity', 'INNOECOFOOD: Circularity', 'Aquaculture', 'inno-circularity',
            'Circular bioeconomy principles across fish, spirulina and insect production and processing.', $innoIntro],
    ];

    $courseIds = [];
    foreach ($courses as [$slug, $title, $category, $img, $summary, $intro]) {
        $id = upsertId('webinars', ['slug' => $slug], [
            'teacher_id' => ADMIN_ID, 'creator_id' => ADMIN_ID, 'category_id' => $cat($category), 'type' => 'course', 'private' => 0,
            'thumbnail' => CVH . "/courses/{$img}.jpg", 'image_cover' => CVH . "/courses/{$img}_cover.jpg",
            'price' => null, 'support' => 1, 'certificate' => 1, 'downloadable' => 0, 'forum' => 1,
            'timezone' => 'Africa/Nairobi', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
        ]);
        translation('webinar_translations', 'webinar_id', $id, [
            'title' => $title, 'seo_description' => $summary, 'summary' => $summary,
            'description' => "<p>{$summary}</p>{$intro}<h4>Your learning experience</h4>{$learningExperience}",
        ]);
        $courseIds[$slug] = $id;
    }

    /* ---------- Live mentorship session (draft until a date is set) ---------- */
    $liveId = upsertId('webinars', ['slug' => 'hd4a-agribusiness-agroinnovators-mentorship-session-9'], [
        'teacher_id' => $teachers['dr-sanjukta-badhai'], 'creator_id' => ADMIN_ID, 'category_id' => $cat('Agripreneurship'),
        'type' => 'webinar', 'private' => 0, 'start_date' => strtotime('2026-11-05 10:00 Africa/Nairobi'), 'duration' => 120,
        'timezone' => 'Africa/Nairobi', 'capacity' => 100,
        'thumbnail' => CVH . '/courses/live-session.jpg', 'image_cover' => CVH . '/courses/live-session_cover.jpg',
        'price' => null, 'support' => 1, 'certificate' => 0, 'forum' => 0,
        'status' => 'is_draft', 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
    ]);
    translation('webinar_translations', 'webinar_id', $liveId, [
        'title' => 'HD4A Agribusiness Agroinnovators Mentorship Session 9',
        'summary' => 'Live mentorship session for HD4A agroinnovators with business development expert Dr. Sanjukta Badhai.',
        'seo_description' => 'Live HD4A agribusiness mentorship session on CVH Africa.',
        'description' => '<p>Live mentorship session for innovators in the <strong>HealthyDiets4Africa Agribusiness Accelerator Program</strong>, led by business development expert Dr. Sanjukta Badhai.</p><p>Get support from the trainers and real-time feedback during the live session. Recordings are shared after the training.</p>',
    ]);

    /* ---------- Project bundles ---------- */
    $bundles = [
        [
            'slug' => 'HealthyDiets4Africa', 'title' => 'HealthyDiets4Africa (HD4A)', 'category' => 'Food-Safety', 'img' => 'bundle-hd4a',
            'courses' => array_slice(array_keys($courseIds), 0, 7),
            'summary' => 'Build the skills to promote healthier, sustainable diets across Africa — free, self-paced courses on food safety and agribusiness.',
            'description' => '<p>The <strong>Healthy Diets for Africa (HD4A) Project</strong> offers a free, self-paced online learning program designed to strengthen the capacity of African youth, women, food system actors and local communities to promote healthier, diverse, and sustainable diets in their countries. Whether you work in health, agriculture, education, policy, or community development, HD4A gives you the practical knowledge, tools, and real-world examples you need to make a measurable impact.</p>'
                . '<p>Healthydiets4Africa is an EU funded project focusing on the diversification of the food system to combat the triple burden of malnutrition while minimizing its environmental footprint in eight African countries.</p>'
                . '<h4>Who &amp; why you need to take a course</h4>'
                . ul(['<strong>Youth Innovators</strong> — I need business development skills. Find training and courses on in-demand agribusiness skills to help you get your innovation scaling up.',
                    '<strong>Women Agripreneurs</strong> — I want to start my own business. Find necessary business courses and financial skills to build and run your own start-up.',
                    '<strong>Food System Actors</strong> — I want to unleash my full potential. Start your lifelong learning journey with CVH and improve your information base.',
                    '<strong>Consumers</strong> — I want to handle my food safely. Explore courses that teach essential food safety practices, hygiene standards, and smart handling techniques.'])
                . '<h4>What is my learning experience</h4>' . $learningExperience
                . '<p>No prior background in nutrition is required — just the willingness to learn and make a difference.</p>',
            'faqs' => [
                ['Who should enroll?', 'HD4A is designed for health practitioners, nutritionists & dietitians, agriculture & food system practitioners, teachers & youth educators, NGO & development workers, policy makers & program managers, students & young professionals, and community leaders and volunteers. If you want to champion healthy eating in your community, organization, or country — this course is for you.'],
                ['Why do healthy diets matter?', 'Healthy diets are essential for preventing malnutrition in all its forms, reducing the burden of non-communicable diseases, supporting child development and academic performance, building climate-resilient and sustainable communities, and creating opportunities within local food systems.'],
                ['Will I earn a certificate?', 'Yes. After completing all modules and quizzes, you will receive a Certificate of Completion from CVH Africa, demonstrating your skills and commitment to promoting healthy diets — perfect for CVs, LinkedIn, job applications, and professional development portfolios.'],
                ['Do I need a background in nutrition?', 'No prior background in nutrition is required — just the willingness to learn and make a difference.'],
            ],
        ],
        [
            'slug' => 'INNOECOFOOD', 'title' => 'INNOECOFOOD Training Modules', 'category' => 'Aquaculture', 'img' => 'bundle-innoecofood',
            'courses' => array_slice(array_keys($courseIds), 8, 4),
            'summary' => 'Achieve your goal in aquaculture with 4 free certified modules on fish, spirulina, insects and circularity.',
            'description' => '<p><strong>The INNOECOFOOD project</strong> is an ambitious, multi-actor initiative funded under the EU Horizon programme that seeks to transform aquaculture and sustainable food production systems across six African countries — Kenya, Uganda, Tanzania, Egypt, Namibia &amp; Ghana. By combining artificial intelligence (AI), Internet of Things (IoT), and circular bioeconomy principles, the project aims to create innovative production and processing ECOHUBs that will enhance nutrition, income, and trade across EU-AU markets.</p>'
                . '<p>These Training Modules have been developed to guide the Training of Trainers (ToTs) within the INNOECOHUB framework and serve as a foundational resource to build capacity in fish, spirulina, and insect value chains — three key sustainable aquaculture systems with high nutritional, economic, and ecological value.</p>'
                . '<p>Whether you are looking to upskill in your current aquaculture, spirulina and insect value chains or you are planning to venture into them at different nodes of production, processing, marketing or circularity, CVH offers you 4 modules to sharpen your knowledge, skills and retooling — always free and at your convenience.</p>'
                . '<h4>Your benefits</h4>'
                . ul(['<strong>100% Free</strong> — you will not be charged to learn or to receive your certificate.',
                    '<strong>Accessible</strong> — access the courses through any browser on your phone or PC, at your own pace.',
                    '<strong>Certification</strong> — 4 certified courses designed by experts; receive a certificate for every successful course completion, free of charge.',
                    '<strong>Effective Support</strong> — support from trainers, learner forums and real-time feedback during live training sessions, plus recordings of live sessions.']),
            'faqs' => [
                ['Is INNOECOFOOD training free?', 'Yes. All courses and modules on INNOECOFOOD are free. You will not be charged to learn or to receive your certificate.'],
                ['What will I learn?', 'You can learn about AI and IoT in aquaculture, circularity in fish, spirulina and insect production and processing, understand the RAS and IPRS technologies, start your venture in sustainable aquaculture and much more.'],
            ],
        ],
    ];

    $bundleIds = [];
    foreach ($bundles as $b) {
        $id = upsertId('bundles', ['slug' => $b['slug']], [
            'creator_id' => ADMIN_ID, 'teacher_id' => ADMIN_ID, 'category_id' => $cat($b['category']),
            'thumbnail' => CVH . "/courses/{$b['img']}.jpg", 'image_cover' => CVH . "/courses/{$b['img']}_cover.jpg",
            'price' => null, 'private' => 0, 'subscribe' => 0, 'certificate' => 1, 'status' => 'active',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        translation('bundle_translations', 'bundle_id', $id, [
            'title' => $b['title'], 'summary' => $b['summary'], 'seo_description' => $b['summary'], 'description' => $b['description'],
        ]);
        DB::table('bundle_webinars')->where('bundle_id', $id)->delete();
        foreach ($b['courses'] as $i => $courseSlug) {
            DB::table('bundle_webinars')->insert(['creator_id' => ADMIN_ID, 'bundle_id' => $id, 'webinar_id' => $courseIds[$courseSlug], 'order' => $i + 1]);
        }
        foreach (DB::table('faqs')->where('bundle_id', $id)->pluck('id') as $faqId) {
            DB::table('faq_translations')->where('faq_id', $faqId)->delete();
        }
        DB::table('faqs')->where('bundle_id', $id)->delete();
        foreach ($b['faqs'] as $i => [$q, $a]) {
            $faqId = DB::table('faqs')->insertGetId(['creator_id' => ADMIN_ID, 'bundle_id' => $id, 'order' => $i + 1, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('faq_translations')->insert(['faq_id' => $faqId, 'locale' => 'en', 'title' => $q, 'answer' => $a]);
        }
        $bundleIds[$b['slug']] = $id;
    }

    /* ---------- Blog ---------- */
    $blogCats = [];
    foreach ([['News-and-Articles', 'News & Articles'], ['Innovator-Profiles', 'Innovator Profiles']] as [$slug, $title]) {
        $blogCats[$slug] = upsertId('blog_categories', ['slug' => $slug], []);
        translation('blog_category_translations', 'blog_category_id', $blogCats[$slug], ['title' => $title]);
    }

    $p = fn(...$paras) => implode('', array_map(fn($x) => "<p>{$x}</p>", $paras));
    $h = fn($t) => "<h3>{$t}</h3>";
    $posts = [
        ['navigating-urban-food-environments-the-rise-of-urban-food-eateries', 'News-and-Articles', 'urban-food', 6,
            'Navigating Urban Food Environments: The Rise of Urban Food Eateries',
            'The rise of urban food eateries reflects broader societal shifts toward convenience, health, and sustainability.',
            $p('The rise of urban food eateries reflects broader societal shifts toward convenience, health, and sustainability. As urban environments continue to evolve, these establishments will play a crucial role in shaping our culinary landscape. By navigating the challenges and leveraging the opportunities presented by urban food environments, food entrepreneurs can contribute to a vibrant and diverse food culture that meets the needs of modern city dwellers.',
                'In the end, urban food eateries are not just about feeding the masses; they are about creating experiences, building communities, and fostering a deeper appreciation for the food we consume. As we look to the future, one thing is clear: the culinary scene in our cities will continue to thrive, innovate, and inspire.')],
        ['innovator-of-the-month-ryans-food-kenya', 'Innovator-Profiles', 'ryans-foods', 4,
            "Innovator of the Month: Ryan's Food Kenya",
            "Benson Kagwima's Nairobi-based start-up produces nutritious pre-cooked foods from sorghum, soybeans and orange-fleshed sweet potatoes.",
            $p("<strong>Benson Kagwima — Ryan's Food Kenya, Nairobi.</strong> HD4A Agribusiness Accelerator Program, Cohort 2 winner.")
            . $h('Value Proposition')
            . $p("Ryan's Food Kenya is a Nairobi-based food startup pioneering the production of nutritious pre-cooked, convenient food products from locally sourced ingredients: sorghum, soybeans and orange-fleshed sweet potatoes.",
                'This provides consumers a quick, affordable, and nutrient-dense meal option. By reducing cooking time and energy use, the product also addresses environmental concerns, i.e. lowering carbon emissions linked to traditional cooking methods.',
                'Additionally, they are empowering communities in urban areas to continue embracing indigenous and underutilized food crops.')
            . $h('Current Business Need')
            . $p("Ryan's is seeking an <strong>investment of $78,000</strong> to expand production and distribution capacity and grow its market presence by centralizing processing and packaging operations, expanding distribution and upscaling inventory by buying more raw materials from farmers.")
            . $h('5-Year Plan')
            . $p('In the next 1 to 5 years, they aim to reach at least 80,000 consumers, including 40,000 children, and engage over 300 smallholder farmers in their supply chains in Kenya and the African region.')
            . $h('Why should an investor be interested?')
            . $p("Ryan's Foods is creating a ripple effect across the value chain, supporting smallholder farmers and improving livelihoods. With growing traction, including retail partnerships and media recognition, the enterprise is now seeking investment to scale production, logistics, and market reach. Their products are now in retail supermarkets in Nairobi and Kiambu which makes it a viable business to invest in. In the long term, they aim to reach 300,000 to 500,000 consumers annually and supply major humanitarian and school feeding programs.")],
        ['innovator-of-the-month-asili-farm-africa', 'Innovator-Profiles', 'asili-farm', 4,
            'Innovator of the Month: Asili Farm Africa',
            'Boaz Tuju helps smallholder farmers transition to organic farming with affordable, nature-based biofertilizers and biopesticides.',
            $p('<strong>Boaz Tuju — Asili Farm Africa, Nairobi.</strong> HD4A Agribusiness Accelerator Program, Cohort 2 winner.')
            . $h('Overview')
            . $p('Asili Farm Africa empowers smallholder farmers in organic farming transition through trainings and provision of affordable nature-based biofertilizers and biopesticides made from plant leaves, animal waste, jaggery, yeast, soil and water — leading to low dependence on synthetic fertilizers and pesticides, promoting the biodiversity activity of the soil, and improving natural fertility and water retention capacity.')
            . $h('Value Proposition')
            . $p('100% transition to organic farming, and access to quality and affordable biofertilizers and biopesticides. They are invested in problem-specific solutions to soil fertility and safe food production. This will enhance sequestration of up to 250,000 tons of carbon annually through agriculture and increased biodiversity activity in the soil.')
            . $h('Current Business Need')
            . $p('Asili Farm Africa is seeking a <strong>seed investment of KES 500,000</strong>. Use of funds: 40% product development, 30% customer acquisition &amp; partnerships, 20% team expansion (tech, agronomy) and 10% operations and legal expenses.')
            . $h('5-Year Plan')
            . $p('In the next 1 to 5 years, they are working to improve the sale of their organic farm inputs such as Boma Boost, Kifaru Boost, Floriguard, Mbao Boost, Majani Magic, and Biospice, and to develop their digital app to offer real-time agronomy advisory and act as the platform for selling products to smallholder farmers.')
            . $h('Why should an investor be interested?')
            . $p("Asili Farm Africa's nature-based solutions not only restore soil health and biodiversity but also enable farmers to produce safer, chemical-free food that meets rising consumer and export market demands. By combining product innovation with farmer training, they are building a pathway toward sustainable, profitable agriculture, supported by a clear plan to scale through organic agriculture. They work directly with farmers through farmer groups and local cooperatives and are now employing digital tools to reach more farmers through targeted SMS and extension services.")],
        ['innovator-of-the-month-momix-enterprise-crunchy-roots', 'Innovator-Profiles', 'momix', 4,
            'Innovator of the Month: Momix Enterprise (Crunchy Roots)',
            'Mary Ngugi turns locally grown sweet potatoes into nutritious Crunchy Roots snacks, already stocked in 13 supermarkets.',
            $p('<strong>Mary Ngugi — Momix Enterprise (Crunchy Roots), Kiambu.</strong> HD4A Agribusiness Accelerator Program, Cohort 2 winner.')
            . $h('Value Proposition')
            . $p('Momix Enterprises adds value to locally grown sweet potatoes, turning them into <strong>The Crunchy Roots Sweet-Potato Crackies</strong> — nutritious, high-quality, and delicious snacks. They address <strong>post-harvest losses (up to 40%)</strong>, limited farmer market access, and the growing demand for nutritious snack alternatives. Their business model centers on <strong>value addition and retail distribution</strong>, with products already stocked in <strong>13 supermarkets</strong>, demonstrating strong market traction.',
                'By partnering with local farmers, Momix Enterprises supports sustainable agriculture and empowers communities by training them on the best varieties and Good Agricultural Practices. Additionally, they have created job opportunities for the youth in processing and sales.')
            . $h('5-Year Plan')
            . $p("Momix Enterprises' five-year plan focuses on establishing a strong local market presence and expanding regionally, diversifying products, and strengthening sustainability of product supply. This will be achieved by acquiring bigger processing machines and setting up a processing facility. The company aims to support healthy eating, empower communities, and drive environmental impact through partnerships with farmers, eco-friendly practices, and innovative product offerings.")
            . $h('Current Business Need')
            . $p('To scale production and meet rising demand, Momix is seeking an <strong>investment of KES 500,000</strong> to expand capacity and grow its market presence. The funds will be used to buy equipment, improve packaging and boost marketing initiatives.')
            . $h('Why should an investor be interested?')
            . $p('Momix Enterprises is an innovative snack company poised for growth in the health and wellness market, offering a strong value proposition and significant market potential. Investment will enable the company to scale production, enhance packaging, expand marketing and distribution, and secure export certifications. By investing in Momix, you support local farmers, create jobs, and promote sustainable agriculture while gaining an early stake in a high-growth venture.')],
    ];
    $blogIds = [];
    foreach ($posts as $i => [$slug, $category, $img, $minutes, $title, $description, $content]) {
        $id = upsertId('blog', ['slug' => $slug], [
            'category_id' => $blogCats[$category], 'author_id' => ADMIN_ID, 'image' => CVH . "/blog/{$img}.jpg",
            'study_time' => $minutes, 'enable_comment' => 1, 'status' => 'publish',
            'created_at' => $now - $i * 60, 'updated_at' => $now,
        ]);
        translation('blog_translations', 'blog_id', $id, [
            'title' => $title, 'subtitle' => null, 'description' => $description, 'content' => $content, 'meta_description' => $description,
        ]);
        $blogIds[$slug] = $id;
    }

    /* ---------- Innovations pages ---------- */
    $innovators = json_decode(file_get_contents(__DIR__ . '/innovators.json'), true);
    $profileLinks = [
        'Benson Kagwima' => '/blog/innovator-of-the-month-ryans-food-kenya',
        'Boaz Tuju' => '/blog/innovator-of-the-month-asili-farm-africa',
        'Mary Ngugi' => '/blog/innovator-of-the-month-momix-enterprise-crunchy-roots',
    ];
    $card = function ($x) use ($profileLinks) {
        $photo = CVH . '/people/' . Str::slug(str_replace('.', '', $x['name'])) . '.jpg';
        $link = $profileLinks[$x['name']] ?? null;
        return '<div style="width:200px;background:var(--white);border:1px solid var(--gray-300);border-radius:16px;padding:14px;text-align:center;">'
            . '<img src="' . $photo . '" alt="' . e($x['name']) . '" style="width:120px;height:120px;border-radius:50%;object-fit:cover;margin:0 auto 10px;display:block;" loading="lazy">'
            . '<div style="font-weight:600;color:var(--dark);">' . e($x['name']) . '</div>'
            . '<div style="color:var(--primary);font-size:14px;margin-top:2px;">' . e($x['business']) . '</div>'
            . '<div style="color:var(--gray-500);font-size:13px;margin-top:2px;">' . e($x['location']) . '</div>'
            . ($link ? '<a href="' . $link . '" style="display:inline-block;margin-top:8px;font-size:13px;color:var(--primary);font-weight:600;">View profile →</a>' : '')
            . '</div>';
    };
    $grid = fn($list) => '<div style="display:flex;flex-wrap:wrap;gap:16px;margin:12px 0 28px;">' . implode('', array_map($card, $list)) . '</div>';
    $h2 = fn($t) => '<h2 style="color:var(--secondary);margin:28px 0 8px;">' . $t . '</h2>';
    $byName = collect($innovators)->keyBy('name');
    $intro = '<p>The innovators recognized in the <strong>HealthyDiets4Africa Agribusiness Accelerator Program</strong> don\'t just react to change; they designed for it. Their portfolios shape agro-processing and service sectors, influence markets and set new standards for Africa\'s food systems.</p>';

    $winnersHtml = '<h2 style="color:var(--secondary);margin:0 0 8px;">Where innovations create impact: Kenya\'s leading agro-innovators</h2>' . $intro
        . $h2('Meet Cohort 1 Winners') . $grid([$byName['Lilian Bitok'], $byName['Judy Mwende'], $byName['Mary Karoki']])
        . $h2('Meet Cohort 2 Winners') . $grid([$byName['Benson Kagwima'], $byName['Boaz Tuju'], $byName['Mary Ngugi'], $byName['Albanus Mwangangi'], $byName['Mercy Manyange']])
        . '<p><a href="/pages/innovations-cohorts" style="display:inline-block;background:var(--primary);color:#fff;padding:12px 28px;border-radius:12px;text-decoration:none;font-weight:600;">Meet all cohort finalists</a></p>';

    $cohortsHtml = '<h2 style="color:var(--secondary);margin:0 0 8px;">Where innovations create impact: Africa\'s leading agro-innovators</h2>' . $intro;
    foreach (collect($innovators)->groupBy('cohort') as $cohort => $list) {
        $heading = str_replace(['Finalist', 'Finalistss'], ['Finalists', 'Finalists'], $cohort);
        $heading = preg_match('/Cohort 3$/', $heading) ? 'Meet Cohort 3' : $heading;
        $cohortsHtml .= $h2($heading) . $grid($list->all());
    }

    foreach ([
        ['/innovations', 'Innovations', 'HD4A Innovations', 'Explore impact-driven agri-innovations in Africa', $winnersHtml],
        ['/innovations-cohorts', 'Innovations Cohorts', 'HD4A Innovation Cohorts', 'Meet the finalists of the HealthyDiets4Africa Agribusiness Accelerator Program', $cohortsHtml],
    ] as [$link, $name, $title, $subtitle, $html]) {
        $pageId = upsertId('pages', ['link' => $link], [
            'name' => $name, 'robot' => 1, 'status' => 'publish', 'created_at' => $now,
            'cover' => CVH . '/courses/bundle-hd4a_cover.jpg',
        ]);
        translation('page_translations', 'page_id', $pageId, [
            'title' => $title, 'subtitle' => $subtitle, 'seo_description' => $subtitle, 'content' => $html,
        ]);
    }

    /* ---------- Navigation ---------- */
    $nav = [
        ['Home', '/'], ['Courses', '/classes'], ['Projects', '/bundles'], ['Innovations', '/pages/innovations'],
        ['Instructors', '/instructors'], ['Blog', '/blog'], ['About', '/pages/about'],
    ];
    updateHeaderFooter(1, function ($c) use ($nav) {
        $c['specific_links'] = [];
        foreach ($nav as $i => [$t, $u]) {
            $c['specific_links']["cvhA{$i}"] = ['title' => $t, 'url' => $u];
        }
        $c['specific_links']['record'] = ['title' => null, 'url' => null];
        return $c;
    });
    $settingId = DB::table('settings')->where('name', 'navbar_links')->value('id');
    $navSetting = [];
    foreach ($nav as $i => [$t, $u]) {
        $navSetting["cvh0{$i}"] = ['title' => $t, 'link' => $u, 'order' => (string)($i + 1)];
    }
    DB::table('setting_translations')->where('setting_id', $settingId)->where('locale', 'en')->update(['value' => json_encode($navSetting)]);

    updateHeaderFooter(2, function ($c) {
        $c['specific_links'] = [
            'cvhF1' => ['title' => 'About us', 'url' => '/pages/about'],
            'cvhF2' => ['title' => 'HD4A Project', 'url' => '/bundles/HealthyDiets4Africa'],
            'cvhF3' => ['title' => 'INNOECOFOOD Project', 'url' => '/bundles/INNOECOFOOD'],
            'cvhF4' => ['title' => 'Innovations', 'url' => '/pages/innovations'],
            'cvhF5' => ['title' => 'Certificate Validation', 'url' => '/certificate_validation'],
            'cvhF6' => ['title' => 'Contact', 'url' => '/contact'],
            'cvhF7' => ['title' => 'Terms of use', 'url' => '/pages/terms'],
            'record' => ['title' => null, 'url' => null],
        ];
        return $c;
    });

    /* ---------- Homepage references ---------- */
    updateLandingComponent(1, function ($c) {
        $c['upper_cta']['url'] = '/bundles/HealthyDiets4Africa';
        return $c;
    });
    updateLandingComponent(3, function ($c) use ($courseIds) {
        $points = ['cvhP1' => '100% online and mobile-friendly', 'cvhP2' => 'Self-paced — learn at your own speed', 'cvhP3' => 'Knowledge checks and quizzes', 'cvhP4' => 'Downloadable resources and tools', 'cvhP5' => 'Free certificate upon completion'];
        $c['featured_courses'] = [
            'cvhFa' => ['course' => (string)$courseIds['acrylamide-occurrence-in-foods-health-concerns-and-mitigation-strategies'], 'cover_image' => CVH . '/courses/acrylamide_cover.jpg', 'checked_items' => $points],
            'cvhFb' => ['course' => (string)$courseIds['basic-introduction-to-entrepreneurship'], 'cover_image' => CVH . '/courses/bundle-hd4a_cover.jpg', 'checked_items' => $points],
            'cvhFc' => ['course' => (string)$courseIds['innoecofood-fish'], 'cover_image' => CVH . '/courses/inno-fish_cover.jpg', 'checked_items' => $points],
        ];
        return $c;
    });
    updateLandingComponent(12, function ($c) use ($teachers) {
        $c['specific_instructors'] = ['record' => ['instructor_id' => null]];
        foreach (array_values($teachers) as $i => $id) {
            $c['specific_instructors']["cvhI{$i}"] = ['instructor_id' => (string)$id];
        }
        return $c;
    });

    // "Top Rated" stays empty until CVH courses receive reviews — hide it (and its spacer) until then.
    $bestRatedOrder = DB::table('landing_components')->where('landing_id', 1)
        ->where('component_id', DB::table('landing_builder_components')->where('name', 'best_rated_courses')->value('id'))
        ->value('order');
    DB::table('landing_components')->where('landing_id', 1)->whereIn('order', [$bestRatedOrder, $bestRatedOrder + 1])->update(['enable' => 0]);

    /* ---------- Hide the template's demo content (reversible; nothing deleted) ---------- */
    $cvhCourseIds = array_merge(array_values($courseIds), [$liveId]);
    DB::table('webinars')->whereNotIn('id', $cvhCourseIds)->update(['status' => 'inactive']);
    DB::table('bundles')->whereNotIn('id', array_values($bundleIds))->update(['status' => 'inactive']);
    DB::table('blog')->whereNotIn('id', array_values($blogIds))->update(['status' => 'pending']);
    DB::table('users')->whereIn('role_name', ['teacher', 'organization'])->whereNotIn('id', array_values($teachers))->update(['status' => 'inactive']);
    DB::table('categories')->whereNotIn('slug', ['Food-Safety', 'Agripreneurship', 'Climate-and-Agriculture', 'Aquaculture'])->update(['enable' => 0]);
    DB::table('trend_categories')->whereNotIn('category_id', DB::table('categories')->where('enable', 1)->pluck('id'))->delete();
    DB::table('testimonials')->update(['status' => 'disable']);
    DB::table('products')->update(['status' => 'inactive']);
    DB::table('upcoming_courses')->update(['status' => 'inactive']);
    DB::table('special_offers')->update(['status' => 'inactive']);
    DB::table('feature_webinars')->update(['status' => 'pending']);
});

echo "CVH phase 2 applied.\n";
