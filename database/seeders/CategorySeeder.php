<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The full category list, grouped. Safe to run again: existing categories
 * (matched by slug) keep their products and just get their group and order updated.
 *   php artisan db:seed --class=CategorySeeder
 */
class CategorySeeder extends Seeder
{
    public const GROUPS = [
        'AI' => [
            'AI Tools', 'AI Assistants & Chatbots', 'AI Agents', 'AI Writing', 'AI Image Generation',
            'AI Video', 'AI Voice & Audio', 'AI Coding', 'AI Productivity',
        ],
        'Developer' => [
            'Developer Tools', 'APIs & SDKs', 'Databases & Backend', 'DevOps & Hosting', 'Testing & QA',
            'Monitoring & Logging', 'Security', 'Open Source', 'Code Editors & IDEs',
        ],
        'Productivity' => [
            'Productivity', 'Notes & Docs', 'Task & Project Management', 'Calendar & Scheduling',
            'Communication & Chat', 'Team Collaboration', 'Time Tracking', 'Automation & Workflows',
        ],
        'Marketing & Sales' => [
            'Marketing', 'SEO', 'Social Media', 'Email Marketing', 'Content Creation', 'Advertising',
            'Sales & Leads', 'CRM', 'Affiliate & Referral', 'Landing Pages',
        ],
        'Design' => [
            'Design', 'UI Kits & Templates', 'Website Builders', 'No-Code', 'Photo Editing',
            'Video Editing', 'Icons & Illustrations', 'Prototyping',
        ],
        'Business' => [
            'Analytics', 'Finance', 'Payments & Invoicing', 'Accounting', 'E-commerce', 'Customer Support',
            'HR & Recruiting', 'Legal', 'Forms & Surveys', 'Real Estate',
        ],
        'Personal' => [
            'Education & Learning', 'Health & Fitness', 'Personal Finance', 'Travel', 'Music & Audio',
            'Games', 'Lifestyle', 'Social & Community', 'News & Reading',
        ],
        'Platforms' => [
            'Browser Extensions', 'Mac Apps', 'Windows Apps', 'iOS Apps', 'Android Apps',
            'Slack & Discord Bots', 'Crypto & Web3', 'Hardware', 'Other',
        ],
    ];

    public function run(): void
    {
        $sort = 0;
        foreach (self::GROUPS as $group => $names) {
            foreach ($names as $name) {
                $category = Category::firstOrNew(['slug' => Str::slug($name)]);
                $category->name = $category->exists ? $category->name : $name;
                $category->group = $group;
                $category->sort = $sort++;
                $category->save();
            }
        }
    }
}
