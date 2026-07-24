<?php

use App\Http\Controllers\ConfigSystemController;
use App\Http\Controllers\PromptBackgroundController;
use App\Http\Controllers\PromptOptimizerController;
use App\Http\Controllers\UserAccountTiktokController;
use App\Http\Controllers\UserCoinController;
use App\Http\Controllers\UserMediaController;
use App\Http\Controllers\UserPromptTemplateController;
use App\Http\Controllers\UserTemplatePresetController;
use Illuminate\Support\Facades\Route;

return 'user route';

// Prompt Optimizer endpoints
// Route::prefix('prompt-optimizer')->group(function () {
//     // Background management
//     Route::controller(PromptBackgroundController::class)->group(function () {
//         Route::get('backgrounds', 'backgrounds')->name('user.prompt-optimizer.backgrounds');
//         Route::post('user-backgrounds', 'storeUserBackground')->name('user.prompt-optimizer.user-backgrounds.store');
//         Route::match(['put', 'patch'], 'user-backgrounds/{userBackground}', 'updateUserBackground')->name('user.prompt-optimizer.user-backgrounds.update');
//         Route::delete('user-backgrounds/{userBackground}', 'destroyUserBackground')->name('user.prompt-optimizer.user-backgrounds.destroy');
//     });

//     // Configuration / settings
//     Route::controller(ConfigSystemController::class)->group(function () {
//         Route::get('prompt-templates', 'promptTemplates')->name('user.prompt-optimizer.prompt-templates');
//         Route::get('categories', 'categories')->name('user.prompt-optimizer.categories');
//         Route::get('ai-models', 'aiModels')->name('user.prompt-optimizer.ai-models');
//         Route::get('my-balance', 'myBalance')->name('user.prompt-optimizer.my-balance');
//         Route::get('config', 'config')->name('user.prompt-optimizer.config');
//     });

//     // Main optimization pipeline
//     Route::controller(PromptOptimizerController::class)->group(function () {
//         Route::post('generate', 'generate')->name('user.prompt-optimizer.generate');
//         Route::post('refine', 'refine')->name('user.prompt-optimizer.refine');
//         Route::post('save', 'save')->name('user.prompt-optimizer.save');
//         Route::get('history', 'history')->name('user.prompt-optimizer.history');
//         Route::get('history/{uuid}', 'historyDetail')->name('user.prompt-optimizer.history-detail');
//         Route::get('usage-logs', 'usageLogs')->name('user.prompt-optimizer.usage-logs');
//         Route::get('generation-progress/{sessionId}', 'generationProgress')->name('user.prompt-optimizer.generation-progress');
//     });
// });