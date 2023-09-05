<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class NotificationController extends Controller
{
    //
    public function __construct()
    {
        DB::table('notifications')->where('created_at', '<', Carbon::now()->subDays(15))
            ->delete();
    }

    public function index(Request $request)
    {
        return Inertia::render('Notification/Index');
    }

    public function show($notification)
    {
        $notification = auth()->user()->notifications()->where('id', $notification)->first();

        if ($notification) {
            $notification->markAsRead();
        }
        return redirect()->back();
    }

    public function destroy($notification)
    {
        DB::table('notifications')->where('id', $notification)->delete();

        return redirect()->back()->with('message', [
            'type' => 'success',
            'text' => 'Noficação eliminada com sucesso',
        ]);
    }
}
