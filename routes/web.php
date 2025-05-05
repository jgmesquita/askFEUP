<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\QuestionPostController;
use App\Http\Controllers\AnswerPostController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\PostReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StaticController;
use App\Http\Controllers\PostReportReasonController;
use App\Http\Controllers\CommentPostController;
use App\Http\Controllers\MailController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Home
Route::redirect('/', '/home'); 

// Question
Route::controller(QuestionPostController::class)->group(function () {
    Route::get('/home', 'index')->name('home'); 
    Route::get('/questions/{id}', 'show')->name('questions.show'); 
    Route::get('/new-question', 'create')->name('questions.create'); 
    Route::post('/new-question', 'store')->name('questions.store'); 
    Route::post('/api/questions/{id}/delete', 'delete')->name('question.delete'); 
    Route::post('/api/questions/{id}/edit', 'update')->name('questions.edit'); 
    Route::post('/api/questions/{id}/editTag', 'updateTag')->name('questions.editTag'); 
    Route::get('/api/home/{section}', 'renderSection')->name('render.section'); 
    Route::get('/api/questions/{id}/answers/{page?}', 'getAnswers'); 
    Route::post('/api/questions/{id}/toggle-like', 'likeQuestion');
    Route::post('/questions/{id}/follow', 'followQuestion')->name('questions.follow');
    Route::post('/questions/{id}/unfollow', 'unfollowQuestion')->name('questions.unfollow');
    Route::post('/question/report/{id}', 'report')->name('report.question');
});

// Authentication
Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'showLoginForm')->name('login'); 
    Route::post('/login', 'authenticate'); 
    Route::get('/logout', 'logout')->name('logout'); 
    Route::get('/reset-password', 'showResetPassword')->name('show.reset-password'); 
    Route::post('/reset-password', 'resetPassword')->name('reset-password'); 
    Route::get('/new-password/{token}/{email}', 'showNewPassword')->name('show.new-password');
    Route::post('/new-password', 'newPassword')->name('new-password');
});

Route::controller(RegisterController::class)->group(function () {
    Route::get('/register', 'showRegistrationForm')->name('register'); 
    Route::post('/register', 'register'); 
});


Route::controller(MailController::class)->group(function () {
    Route::post('/send', 'send');
    Route::post('/contact', 'contact');
});


// Profile
Route::controller(UserController::class)->group(function() {
    Route::get('/profile/{id?}', 'show')->name('profile'); 
    Route::get('/api/profile/{id}/tags', 'userTags')->name('profile.tags'); 
    Route::get('/api/profile/load/{section}', 'profileSection')->name('profile.section');
    Route::post('/api/profile/dark-mode', 'toggleDarkMode')->name('profile.darkmode');
    Route::get('/edit-profile/{id?}','showEdit')->name('edit-profile'); 
    Route::post('/edit-profile/name/{id}', 'updateName')->name('edit-profile.name'); 
    Route::post('/edit-profile/tagname/{id}', 'updateTagName')->name('edit-profile.tagname'); 
    Route::post('/edit-profile/email/{id}', 'updateEmail')->name('edit-profile.email'); 
    Route::post('/edit-profile/password/{id}', 'updatePassword')->name('edit-profile.password'); 
    Route::post('/edit-profile/age/{id}', 'updateAge')->name('edit-profile.age'); 
    Route::post('/edit-profile/country/{id}', 'updateCountry')->name('edit-profile.country'); 
    Route::post('/edit-profile/degree/{id}', 'updateDegree')->name('edit-profile.degree'); 
    Route::post('/edit-profile/profilepicture/{id}', 'updateProfilePic')->name('edit-profile.profilepicture'); 
    Route::get('/api/users', 'getAll')->name('users.show'); 
    Route::get('/admin-center','showAdmin')->name('admin-center'); 
    Route::post('/api/admin-center/ban/{id}', 'banUser')->name('ban-user'); 
    Route::post('/api/admin-center/revoke-ban/{id}', 'revokeBan')->name('revoke-ban'); 
    Route::post('/api/admin-center/moderator/{id}', 'makeModerator')->name('make-moderator'); 
    Route::post('/api/admin-center/remove-moderator/{id}', 'removeModerator')->name('remove-moderator'); 
    Route::get('/api/admin-center/actions/{id}', 'getAdminActions')->name('admin.actions'); 
    Route::get('/contacts', 'showContacts')->name('contacts');
    Route::post('/profile/delete/{id?}', 'delete')->name('delete');
    Route::get('/leaderboard', 'showLeaderboard')->name('leaderboard');
    Route::get('/api/leaderboard', 'filterLeaderboard')->name('filter.leaderboard');
    Route::get('/questions-followed', 'showFollowed')->name('questions.followed');
    Route::get('/api/manager/load/{section}', 'managerSection')->name('manager.section');
    Route::get('/question/showmorefollowed', 'showmorefollowed');
    Route::get('/api/admin-center/loadMoreTagsadmin', 'loadMoreTagsadmin');
    Route::get('/api/admin-center/loadMorePostsadmin', 'loadMorePostsadmin');
    Route::get('/api/admin-center/getfooter', 'getfooter');
    Route::get('/api/admin-center/admintagscount', 'admintagscount');
    Route::get('/api/admin-center/countreports', 'countreports');
});

// Answer
Route::controller(AnswerPostController::class)->group(function() {
    Route::post('/questions/{id}/answers', 'store')->name('answers.store'); 
    Route::post('/answers/{id}', 'delete')->name('answer.delete'); 
    Route::post('/api/answers/{id}/edit', 'update')->name('answer.edit'); 
    Route::post('/api/answers/{id}/toggle-like', 'likeAnswer');
    Route::post('/questions/answers/{id}/mark-correct', 'markAnswerAsCorrect')->name('answers.mark-correct');
    Route::post('/questions/answers/{id}/revoke-correct', 'revokeAnswerAsCorrect')->name('answers.revoke-correct');
    Route::post('/answer/report/{id}', 'report')->name('report.answer');
    Route::get('/load-coms/{answerID}', 'loadComs')->name('loadComs');
    Route::get('/load-coms/getCom/{answerID}', 'getCom')->name('getCom');
});

// Comment
Route::controller(CommentPostController::class)->group(function() {
    Route::post('/questions/answers/{id}/comments', 'store')->name('comments.store'); 
    Route::post('/comments/{id}', 'delete')->name('comment.delete'); 
    Route::post('/api/comments/{id}/edit', 'update')->name('comment.edit'); 
    Route::post('/api/comments/{id}/toggle-like', 'likeComment');
    Route::post('/comment/report/{id}', 'report')->name('report.comment');
});

// Tags
Route::controller(TagController::class)->group(function() {
    Route::get('/api/tags', 'index'); 
    Route::get('/show-tags', 'showTags')->name('show-tags');
    Route::post('/show-tags/{id}/follow', 'followTag')->name('follow-tag');
    Route::post('/show-tags/{id}/unfollow', 'unfollowTag')->name('unfollow-tag');
    Route::post('/api/admin-center/tag', 'createTag')->name('create-tag');
    Route::post('/admin-center/tag/{id}/update', 'updateTag')->name('update-tag');
    Route::post('/api/admin-center/delete/{id}', 'deleteTag')->name('delete-tag');
    Route::get('/api/showMoreTags', 'showMoreTags');
    Route::get('/api/getTagCount', 'getTagCount')->name('getTagCount');
    Route::get('/api/contadora', 'contadora')->name('contadora');
});

// Notifications
Route::controller(NotificationController::class)->group(function() {
    Route::get('notifications', 'renderPage')->name('notifications');
    Route::get('/api/moreNotifications', 'moreNotifications');
    Route::get('/api/notifications/unread-count', 'getUnreadNotificationsCount');
    Route::post('/api/notifications/mark-read', 'markAsRead')->name('notifications.markAsRead');
});

// Report Reasons
Route::controller(PostReportReasonController::class)->group(function() {
    Route::get('/api/report-reasons', 'index');
});

// Reports
Route::controller(PostReportController::class)->group(function() {
    Route::get('manage-reports', 'renderPage')->name('manage-reports');
    Route::post('/api/admin-center/resolve-report', 'resolveReport')->name('resolve-report');
});

// Static Pages
Route::controller(StaticController::class)->group(function() {
    Route::get('about', 'showAboutUs')->name('about');
});
