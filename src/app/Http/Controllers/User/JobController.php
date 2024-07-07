<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyApply;
use App\Mail\AppliedMail;
use App\Mail\ApplyMail;
use App\Models\AppStatus;
use App\Models\Jobs;
use App\Models\Occupation;
use App\Models\Prefecture;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

final class JobController extends Controller
{
    public function index()
    {
        $jobs = Jobs::where('rec_status', 0)->paginate(50);
        $prefectures = Prefecture::all();
        $occupations = Occupation::all();
        $tags = Tag::where('subject', 1)->get();

        return view('user.job.index', compact(['jobs', 'prefectures', 'occupations', 'tags']));
    }

    public function show($id)
    {
        $job = Jobs::findOrFail($id);

        return view('user.job.show', compact('job'));
    }

    public function application($id)
    {
        $job = Jobs::findOrFail($id);
        $user = User::findOrFail(Auth::id());
        $company = $job->companies;
        // まだAppStatusesテーブルにデータなければ
        $app = AppStatus::where('jobs_id', $id)->where('users_id', Auth::id())->first();
        if (!$app) {
            // dd($user, $company, $job, $id);
            $app = new AppStatus();
            $app->users_id = Auth::id();
            $app->jobs_id = $id;
            $app->app_flag = 1;
            $app->save();

            // 非同期でのメール送信
            // NotifyApply::dispatch($user, $company, $job, $id);

            // 同期処理でメール送信
            // Mail::to($user->email)->send(new ApplyMail($job, route('user.jobs.show', ['job' => $id])));
            // Mail::to($company->email)->send(new AppliedMail($job, route('company.jobs.show', ['job' => $id])));
        } else {
            // 既に応募済み
            if ($app->app_flag == 1) {  // 後の改修ポイントそもそもtrue, falseで代入、比較を行えないか
                return back()->with(['message' => '既に応募済みです', 'status' => 'info']);
            } else {
                $app->app_flag = 1;
                $app->save();
                // 非同期でのメール送信
                // NotifyApply::dispatch($user, $company, $job, $id);
            }
        }

        // return view('user.job.show', compact('job'))->with(['message' => '応募しました', 'status' => 'info']);
        return redirect()->route('user.jobs.show', ['job' => $id])->with(['message' => '応募しました', 'status' => 'info']);
    }

    public function favorite(Request $request)
    {
        $user_id = Auth::user()->id;
        $job_id = $request->job_id;
        $exist = AppStatus::where('users_id', $user_id)->where('jobs_id', $job_id)->first();
        $already_favorite = AppStatus::where('users_id', $user_id)->where('jobs_id', $job_id)->where('favorite', true)->first();

        // 既にappStatusesテーブルにデータあれば
        if ($exist) {
            // まだお気に入りしてなければ
            if (!$already_favorite) {
                $exist->favorite = true;
                $exist->save();
                // 既にお気に入りしてれば
            } else {
                $already_favorite->favorite = false;
                $already_favorite->save();
            }
            // まだappStatusesテーブルにデータなければ
        } else {
            $favorite = new AppStatus();
            $favorite->jobs_id = $job_id;
            $favorite->users_id = $user_id;
            $favorite->favorite = true;
            $favorite->save();
        }
    }

    public function query(Request $request)
    {
        $requestPrefs = $request->prefectures;
        $requestOccupations = $request->occupations;
        $requestLowSalary = $request->low_salary;
        // $requestHighSalary = $request->high_salary;
        $requestTags = $request->tags;
        $requestSearch = $request->search;
        // dd($requestSearch);

        // $jobs = DB::table('jobs')
        $jobs = Jobs::where('rec_status', 0)
            ->when($requestPrefs, function ($query, $requestPrefs) {
                return $query->leftJoin('job_locations', 'jobs.id', '=', 'job_locations.jobs_id')
                    ->whereIn('job_locations.prefectures_id', $requestPrefs);
            })
            ->when($requestOccupations, function ($query, $requestOccupations) {
                return $query->leftJoin('job_occupations', 'jobs.id', '=', 'job_occupations.jobs_id')
                    ->whereIn('job_occupations.occupations_id', $requestOccupations);
            })
            // 条件変更。上限廃止、下限のみに修正
            ->when($requestLowSalary, function ($query, $requestLowSalary) {
                return $query->where('jobs.low_salary', '>=', $requestLowSalary)
                    ->orWhere('jobs.high_salary', '>=', $requestLowSalary);
            })
            // ->when($requestHighSalary, function ($query, $requestHighSalary) {
            //     return $query->where('jobs.high_salary', '<=', $requestHighSalary);
            // })
            ->when($requestTags, function ($query, $requestTags) {
                return $query->leftJoin('tag_to_jobs', 'jobs.id', '=', 'tag_to_jobs.jobs_id')
                    ->whereIn('tag_to_jobs.tags_id', $requestTags);
            })
            ->when($requestSearch, function ($query, $requestSearch) {
                $spaceConversion = mb_convert_kana($requestSearch, 's');
                $wordArraySearched = preg_split('/[\s,]+/', $spaceConversion, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($wordArraySearched as $word) {
                    return $query->where(function ($query) use ($word) {
                        $query->where('job_name', 'like', '%' . $word . '%')
                            ->orWhere('detail', 'like', '%' . $word . '%')
                            ->orWhere('catch', 'like', '%' . $word . '%');
                    });
                }
            })
            ->paginate(12, ['jobs.*']);

        $prefectures = Prefecture::all();
        $occupations = Occupation::all();
        $tags = Tag::where('subject', 1)->get();

        return view('user.job.search', compact(['jobs', 'prefectures', 'occupations', 'tags', 'requestPrefs', 'requestOccupations', 'requestLowSalary', 'requestTags', 'requestSearch']));
    }

    public function favoriteIndex()
    {
        $user = User::findOrFail(Auth::id());
        $jobsId = $user->appStatus()->where('favorite', 1)->pluck('jobs_id');
        $jobs = Jobs::whereIn('id', $jobsId)->where('rec_status', 0)->paginate(50);

        return view('user.job.favoriteIndex', compact('jobs'));
    }

    public function appliedIndex()
    {
        $user = User::findOrFail(Auth::id());
        $jobsId = $user->appStatus()->where('app_flag', 1)->pluck('jobs_id');
        $jobs = Jobs::whereIn('id', $jobsId)->where('rec_status', 0)->paginate(50);

        return view('user.job.appliedIndex', compact('jobs'));
    }
}
