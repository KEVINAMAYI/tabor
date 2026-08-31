<?php

namespace App\Actions\Student;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateStudentAccountAction
{
    /**
     * Create a User + Student pair and assign the 'student' role.
     *
     * Expects plain field values and already-stored file paths (not
     * UploadedFile instances) — callers are responsible for storing any
     * uploaded files themselves before calling this action.
     */
    public function execute(array $data): Student
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => trim($data['first_name'] . ' ' . ($data['last_name'] ?? '')),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'],
                'password' => Hash::make($data['phone_number']),
                'active' => true,
            ]);

            $student = Student::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'admission_number' => $data['admission_number'] ?? Student::generateAdmissionNumber(),
                'email' => $data['email'],
                'phone' => $data['phone_number'],
                'id_number' => $data['id_number'] ?? null,
                'dob' => $data['date_of_birth'] ?? null,
                'address' => $data['address'] ?? null,
                'country' => $data['country'] ?? null,
                'highest_level_of_education' => $data['highest_level_of_education'] ?? null,
                'id_url' => $data['id_url'] ?? null,
                'kcse_certificate' => $data['kcse_certificate'] ?? null,
                'passport_size_url' => $data['passport_size_url'] ?? null,
                'user_id' => $user->id,
            ]);

            $user->assignRole('student');

            return $student->fresh('user');
        });
    }
}
