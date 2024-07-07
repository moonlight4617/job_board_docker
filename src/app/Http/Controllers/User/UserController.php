<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadImageRequest;
use App\Models\Tag;
use App\Models\TagToUser;
use App\Models\User;
use App\Models\UserPictures;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use InterventionImage;

final class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:users');
    }

    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('user.mypage.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'catch' => ['required', 'string', 'max:255'],
            'intro' => ['required', 'string'],
            'license' => ['nullable', 'string'],
            'career' => ['nullable', 'string'],
            'hobby' => ['nullable', 'string'],
            'pro_image' => ['nullable', 'file', 'max:1024'],
            'portfolio1' => ['nullable', 'file', 'max:1024'],
        ]);

        $user = User::findOrFail(Auth::id());
        $user->catch = $request->catch;
        $user->intro = $request->intro;
        $user->license = $request->license;
        $user->career = $request->career;
        $user->hobby = $request->hobby;

        // dd($request);
        if ($request->pro_image) {
            $imageFile = $request->pro_image;
            $fileName = uniqid(rand() . '_');
            $extension = $imageFile->extension();
            $fileNameToStore = $fileName . '.'  . $extension;
            $resizedImage = InterventionImage::make($imageFile)->orientate()->fit(1920, 1080)->encode($extension);
            Storage::put('public/users/' . $fileNameToStore, (string)$resizedImage);
            $user->pro_image = $fileNameToStore;
        } else {
            $fileNameToStore1 = null;
        }
        $user->save();

        if ($request->portfolio1) {
            $imagePortfolio = $request->portfolio1;
            $portfolioName = uniqid(rand() . '_');
            $extension = $imagePortfolio->extension();
            $portfolioToStore = $portfolioName . '.'  . $extension;
            $resizedPortfolio = InterventionImage::make($imagePortfolio)->orientate()->fit(200, 200)->encode($extension);
            Storage::put('public/users/portfolio/' . $portfolioToStore, (string)$resizedPortfolio);

            // 新たにUserPicturesに画像登録
            UserPictures::create(['users_id' => $user->id, 'filename' => $portfolioToStore]);
        }

        return redirect()->route('user.user.show', compact('user'))->with(['message' => 'ユーザー情報を登録しました。', 'status' => 'info']);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        $pictures = UserPictures::where('users_id', '=', $id)->get();
        $tags = $user->Tags;

        return view('user.mypage.show', compact('user', 'pictures', 'tags'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        $pictures = UserPictures::where('users_id', '=', $id)->get();
        $tags = Tag::where('subject', '=', '0')->get();
        $userTags = $user->Tags;

        return view('user.mypage.edit', compact('user', 'pictures', 'tags', 'userTags'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\UploadImageRequest $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UploadImageRequest $request, $id)
    {
        $request->validate([
            'catch' => ['required', 'string', 'max:255'],
            'intro' => ['required', 'string'],
            'license' => ['nullable', 'string'],
            'career' => ['nullable', 'string'],
            'hobby' => ['nullable', 'string'],
            'twitter' => ['nullable', 'string'],
            'youtube' => ['nullable', 'string'],
            'insta' => ['nullable', 'string'],
            'blog' => ['nullable', 'string'],
        ]);

        $user = User::findOrFail($id);

        // 画像が既に登録ずみであれば削除
        if ($request->pro_image) {
            $filePath = 'public/users/' . $user->pro_image;
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }
            // 改めて画像登録
            $imageFile = $request->pro_image;
            $fileName = uniqid(rand() . '_');
            $extension = $imageFile->extension();
            $fileNameToStore = $fileName . '.'  . $extension;
            $resizedImage = InterventionImage::make($imageFile)->orientate()->fit(1920, 1080)->encode($extension);
            Storage::put('public/users/' . $fileNameToStore, (string)$resizedImage);
            $user->pro_image = $fileNameToStore;
        }

        // タグを登録から外した場合
        $requestTags = $request->tag;
        $userTags = TagToUser::where('users_id', $id)->pluck('tags_id');
        if ($requestTags && $userTags->isNotEmpty()) {
            foreach ($userTags as $tag) {
                if (!in_array($tag, $requestTags)) {
                    $user->tags()->detach($tag);
                }
            }
            foreach ($requestTags as $tag) {
                if (!$userTags->contains($tag)) {
                    $user->tags()->attach($tag);
                }
            }
        } elseif ($requestTags) {
            foreach ($requestTags as $tag) {
                $user->tags()->attach($tag);
            }
        } elseif ($userTags->isNotEmpty()) {
            $user->tags()->detach();
        }

        if ($request->portfolio) {
            foreach ($request->portfolio as $userPic) {
                // storageに画像登録
                $userPicName = uniqid(rand() . '_');
                $extension = $userPic->extension();
                $userPicNameToStore = $userPicName . '.'  . $extension;
                $resizedUserPic = InterventionImage::make($userPic)->orientate()->fit(200, 200)->encode($extension);
                Storage::put('public/users/portfolio/' . $userPicNameToStore, (string)$resizedUserPic);

                // UserPicturesに画像登録
                UserPictures::create(['users_id' => $id, 'filename' => $userPicNameToStore]);
            }
        }

        $user->catch = $request->catch;
        $user->intro = $request->intro;
        $user->license = $request->license;
        $user->career = $request->career;
        $user->hobby = $request->hobby;
        $user->twitter = $request->twitter;
        $user->youtube = $request->youtube;
        $user->insta = $request->insta;
        $user->blog = $request->blog;
        $user->save();

        return redirect()->route('user.user.show', compact('user'))->with(['message' => '更新しました。', 'status' => 'info']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return redirect()->route('user.register')->with(['message' => 'ユーザー情報を削除しました。', 'status' => 'alert']);
    }

    public function pictureAdd(UploadImageRequest $request)
    {
        $imagePortfolio = $request->portfolio;
        $portfolioName = uniqid(rand() . '_');
        $extension = $imagePortfolio->extension();
        $portfolioToStore = $portfolioName . '.'  . $extension;
        $resizedPortfolio = InterventionImage::make($imagePortfolio)->orientate()->fit(200, 200)->encode($extension);
        Storage::put('public/users/portfolio/' . $portfolioToStore, (string)$resizedPortfolio);

        // 新たにUserPicturesに画像登録
        UserPictures::create(['users_id' => Auth::id(), 'filename' => $portfolioToStore]);
    }

    public function pictureDestroy(Request $request)
    {
        $picture_id = $request->pic_id;
        $picture = UserPictures::findOrFail($picture_id);
        $filePath = 'public/users/portfolio/' . $picture->filename;
        if (Storage::exists($filePath)) {
            Storage::delete($filePath);
        }
        $picture->delete();
    }

    public function test()
    {
        if (Auth::guard('companies')->check()) {
            return view('company.welcome');
        } else {
            return view('user.welcome');
        }
    }
}
