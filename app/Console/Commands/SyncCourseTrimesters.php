<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Course;
use App\Services\CourseTrimesterService;

class SyncCourseTrimesters extends Command
{
    protected $signature = 'courses:sync-trimesters';
    protected $description = 'Create course trimesters for existing courses';

    public function handle()
    {
        $this->info('Syncing course trimesters...');

        Course::chunk(100, function ($courses) {
            foreach ($courses as $course) {
                if ($course->number_of_trimesters > 0) {
                    CourseTrimesterService::syncCourseTrimesters($course);
                    $this->line("✔ Course {$course->id} synced");
                }
            }
        });

        $this->info('All courses processed successfully.');
        return 0;
    }
}
