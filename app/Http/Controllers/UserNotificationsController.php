<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Notification;
use App\User;

class UserNotificationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(User $user)
    {
        return auth()->user()->unreadNotifications;
    }

    public function destroy(User $user, $notificationId)
    {
        return $user->notifications()->findOrFail($notificationId)->markAsRead();
    }
}
