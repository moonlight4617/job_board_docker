<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppStatus;
use App\Models\Companies;
use App\Models\ContactUsers;
use App\Models\Jobs;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

//use App\Mail\SendMassegeMail;

final class MessageController extends Controller
{
    public function userMessageIndex($id)
    {
        $user = User::findOrFail($id);
        $companies = ContactUsers::where('users_id', $id)->with('companies')->with('messages')->paginate(50);
        $jobsId = AppStatus::where('users_id', $id)->where('app_flag', 1)->pluck('jobs_id');
        $jobs = Jobs::whereIn('id', $jobsId)->get();

        return view('admin.message.index', compact(['companies', 'jobs', 'user']));
    }

    public function companyMessageIndex($id)
    {
        $users = ContactUsers::where('companies_id', $id)->with('users')->with('messages')->paginate(50);
        $jobs = Companies::findOrFail($id)->jobs()->where('rec_status', '<>', '2')->get();

        return view('admin.message.index', compact(['users', 'jobs']));
    }

    public function show($id, Request $request)
    {
        // $carbon = new Carbon('now');
        // dd(Carbon::now());
        // dd($id, $request);
        $user = User::findOrFail($id);
        $company = Companies::findOrFail($request->company);
        $contactUsersId = ContactUsers::where('users_id', $id)->where('companies_id', $company->id)->select('id')->get();
        if ($contactUsersId->first() == null) {
            return back()->with(['message' => 'まだチャットルームがありません', 'status' => 'alert']);
        }
        $messages = Message::whereIn('contact_users_id', $contactUsersId)->orderBy('sent_time', 'asc')->get();

        return view('admin.message.show', compact(['contactUsersId', 'messages', 'company', 'user']));
    }

    public function delete($id, Request $request)
    {
        $user = User::findOrFail($id);
        $company = Companies::findOrFail($request->company);

        $contactUsersId = ContactUsers::where('users_id', $id)->where('companies_id', $company->id)->select('id')->get();
        if ($contactUsersId->first() == null) {
            return back()->with(['message' => 'まだチャットルームがありません', 'status' => 'alert']);
        }

        foreach ($request->messages as $messageId) {
            Message::findOrFail($messageId)->delete();
        }

        $messages = Message::whereIn('contact_users_id', $contactUsersId)->orderBy('sent_time', 'asc')->get();
        return redirect()->route('admin.users.messageShow', compact(['contactUsersId', 'messages', 'company', 'user']))->with(['message' => 'メッセージを削除しました', 'status' => 'info']);
    }
}
