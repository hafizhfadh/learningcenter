<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $institutions = [
            [
                'name' => 'Learning Center Academy',
                'slug' => 'learning-center-academy',
                'domain' => 'learning-center-academy.edu',
                'settings' => json_encode([
                    'theme' => 'blue',
                    'allow_public_registration' => true,
                    'logo_url' => 'https://example.com/logo.png',
                    'contact_email' => 'contact@learningcenter.com',
                    'social_links' => [
                        'facebook' => 'https://facebook.com/learningcenter',
                        'twitter' => 'https://twitter.com/learningcenter',
                        'linkedin' => 'https://linkedin.com/company/learningcenter',
                    ],
                ]),
            ],
            [
                'name' => 'Professional Development Institute',
                'slug' => 'professional-development-institute',
                'domain' => 'pdi-learning.com',
                'settings' => json_encode([
                    'theme' => 'green',
                    'allow_public_registration' => false,
                    'logo_url' => 'https://example.com/pdi-logo.png',
                    'contact_email' => 'info@pdi-learning.com',
                    'social_links' => [
                        'facebook' => 'https://facebook.com/pdi',
                        'twitter' => 'https://twitter.com/pdi',
                        'linkedin' => 'https://linkedin.com/company/pdi',
                    ],
                ]),
            ],
            [
                'name' => 'Tech Skills Academy',
                'slug' => 'tech-skills-academy',
                'domain' => 'techskills.edu',
                'settings' => json_encode([
                    'theme' => 'dark',
                    'allow_public_registration' => true,
                    'logo_url' => 'https://example.com/tech-logo.png',
                    'contact_email' => 'hello@techskills.edu',
                    'social_links' => [
                        'facebook' => 'https://facebook.com/techskills',
                        'twitter' => 'https://twitter.com/techskills',
                        'linkedin' => 'https://linkedin.com/company/techskills',
                        'github' => 'https://github.com/techskills',
                    ],
                ]),
            ],
        ];
        
        foreach ($institutions as $institution) {
            Institution::create($institution);
        }
    }
}
