<?php

use Livewire\Volt\Volt;


// Route to manage frontend
Volt::route('/', 'front-end.home')->name('front-end.home');
// Volt::route('home', 'front-end.home')->name('front-end.home');
Volt::route('about', 'front-end.about')->name('front-end.about');
Volt::route('courses', 'front-end.courses')->name('front-end.courses');
Volt::route('contact', 'front-end.contact')->name('front-end.contact');
Volt::route('course-application/{course_id?}', 'front-end.course-application')->name('front-end.course-application');
Volt::route('faqs', 'front-end.faqs')->name('front-end.faqs');
Volt::route('news', 'front-end.news')->name('front-end.news');
