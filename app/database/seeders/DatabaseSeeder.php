<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([SqlCourseSeeder::class, PhpBasicsCourseSeeder::class, PhpOopCourseSeeder::class, AdvancedSqlCourseSeeder::class, AdvancedPhpOopCourseSeeder::class, PhpDesignPatternsCourseSeeder::class, PhpAlgorithmsCourseSeeder::class, FunctionalPhpCourseSeeder::class, PhpTestingCourseSeeder::class, PhpCleanCodeCourseSeeder::class, PhpSecurityCourseSeeder::class, LaravelBasicsCourseSeeder::class, LaravelAdvancedCourseSeeder::class, DockerCourseSeeder::class, HtmlCssCourseSeeder::class, JsBasicsCourseSeeder::class, JsAsyncCourseSeeder::class, JsDomCourseSeeder::class, TypeScriptCourseSeeder::class, RegexCourseSeeder::class, GitCourseSeeder::class, LaravelTestingCourseSeeder::class, RestApisCourseSeeder::class, CachingAndRedisCourseSeeder::class, DeployingCourseSeeder::class, TrackSeeder::class]);
    }
}
