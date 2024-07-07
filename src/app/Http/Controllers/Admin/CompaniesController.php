<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadImageRequest;
use App\Models\Companies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use InterventionImage;

final class CompaniesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $companies = Companies::select('id', 'name', 'email', 'created_at', 'updated_at')->paginate(50);

        return view('admin.company.index', compact('companies'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.company.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(UploadImageRequest $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'not_regex:/^(\s|　)|(\s|　)$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:companies'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'intro' => ['required', 'string'],
            'tel' => ['nullable', 'string'],
            'post_code' => ['nullable', 'integer'],
            'address' => ['nullable', 'string', 'max:255'],
            'homepage' => ['nullable', 'string', 'max:255'],
        ]);

        $company = Companies::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'intro' => $request->intro,
            'tel' => $request->tel,
            'post_code' => $request->post_code,
            'address' => $request->address,
            'homepage' => $request->homepage,
        ]);

        // image1,2,3がparamsにあれば、一旦削除した後に、登録
        if ($request->imgpath1) {
            $imageFile = $request->imgpath1;
            $fileName = uniqid(rand() . '_');
            $extension = $imageFile->extension();
            $fileNameToStore1 = $fileName . '.'  . $extension;
            // TODO:後ほど確認
            // $resizedImage1 = InterventionImage::make($imageFile)->orientate()->fit(1920, 1080)->encode();
            $resizedImage1 = InterventionImage::make($imageFile)->orientate()->fit(1920, 1080)->encode($extension);
            // Storage::put('public/companies/' . $fileNameToStore1, $resizedImage1);
            Storage::put('public/companies/' . $fileNameToStore1, (string)$resizedImage1);
        } else {
            $fileNameToStore1 = null;
        }
        if ($request->imgpath2) {
            $imageFile = $request->imgpath2;
            $fileName = uniqid(rand() . '_');
            $extension = $imageFile->extension();
            $fileNameToStore2 = $fileName . '.'  . $extension;
            $resizedImage2 = InterventionImage::make($imageFile)->orientate()->fit(1920, 1080)->encode($extension);
            Storage::put('public/companies/' . $fileNameToStore2, (string)$resizedImage2);
        } else {
            $fileNameToStore2 = null;
        }
        if ($request->imgpath3) {
            $imageFile = $request->imgpath3;
            $fileName = uniqid(rand() . '_');
            $extension = $imageFile->extension();
            $fileNameToStore3 = $fileName . '.'  . $extension;
            $resizedImage3 = InterventionImage::make($imageFile)->orientate()->fit(1920, 1080)->encode($extension);
            Storage::put('public/companies/' . $fileNameToStore3, (string)$resizedImage3);
        } else {
            $fileNameToStore3 = null;
        }

        $company->image1 = $fileNameToStore1;
        $company->image2 = $fileNameToStore2;
        $company->image3 = $fileNameToStore3;

        $company->save();

        return redirect()->route('admin.companies.index')->with(['message' => '企業登録しました。', 'status' => 'info']);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $company = Companies::findOrFail($id);

        return view('admin.company.show', compact('company'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $company = Companies::findOrFail($id);

        return view('admin.company.edit', compact('company'));
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
        $company = Companies::findOrFail($id);
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'not_regex:/^(\s|　)|(\s|　)$/'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('companies')->ignore($company->id)],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'intro' => ['required', 'string'],
            'tel' => ['nullable', 'string'],
            'post_code' => ['nullable', 'integer'],
            'address' => ['nullable', 'string', 'max:255'],
            'homepage' => ['nullable', 'string', 'max:255'],
        ]);

        $company->name = $request->name;
        $company->email = $request->email;
        // パスワードが再入力されていればパスワード更新
        if ($request->password) {
            $company->password = Hash::make($request->password);
        }
        $company->intro = $request->intro;
        $company->tel = $request->tel;
        $company->post_code = $request->post_code;
        $company->address = $request->address;
        $company->homepage = $request->homepage;

        // image1,2,3がparamsにあれば、一旦削除した後に、登録
        if ($request->imgpath1) {
            // 画像が既に登録ずみであれば削除
            $filePath1 = 'public/companies/' . $company->image1;
            if (Storage::exists($filePath1)) {
                Storage::delete($filePath1);
            }
            // 改めて画像登録
            $imageFile = $request->imgpath1;
            $fileName = uniqid(rand() . '_');
            $extension = $imageFile->extension();
            $fileNameToStore1 = $fileName . '.'  . $extension;
            $resizedImage1 = InterventionImage::make($imageFile)->orientate()->fit(1920, 1080)->encode($extension);
            Storage::put('public/companies/' . $fileNameToStore1, (string)$resizedImage1);
            $company->image1 = $fileNameToStore1;
        }
        if ($request->imgpath2) {
            // 画像が既に登録ずみであれば削除
            $filePath2 = 'public/companies/' . $company->image2;
            if (Storage::exists($filePath2)) {
                Storage::delete($filePath2);
            }
            // 改めて画像登録
            $imageFile = $request->imgpath2;
            $fileName = uniqid(rand() . '_');
            $extension = $imageFile->extension();
            $fileNameToStore2 = $fileName . '.'  . $extension;
            $resizedImage2 = InterventionImage::make($imageFile)->orientate()->fit(1920, 1080)->encode($extension);
            Storage::put('public/companies/' . $fileNameToStore2, (string)$resizedImage2);
            $company->image2 = $fileNameToStore2;
        }
        if ($request->imgpath3) {
            // 画像が既に登録ずみであれば削除
            $filePath3 = 'public/companies/' . $company->image3;
            if (Storage::exists($filePath3)) {
                Storage::delete($filePath3);
            }
            // 改めて画像登録
            $imageFile = $request->imgpath3;
            $fileName = uniqid(rand() . '_');
            $extension = $imageFile->extension();
            $fileNameToStore3 = $fileName . '.'  . $extension;
            $resizedImage3 = InterventionImage::make($imageFile)->orientate()->fit(1920, 1080)->encode($extension);
            Storage::put('public/companies/' . $fileNameToStore3, (string)$resizedImage3);
            $company->image3 = $fileNameToStore3;
        }

        $company->save();

        return redirect()->route('admin.companies.index')->with(['message' => '企業を更新しました。', 'status' => 'info']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        Companies::findOrFail($id)->delete();

        return redirect()->route('admin.companies.index')->with(['message' => '企業を削除しました。', 'status' => 'alert']);
    }

    public function query(Request $request)
    {
        // dd($request);
        $requestName = $request->name;
        $requestEmail = $request->email;

        $companies = Companies::where('deleted_at', null)
            ->when($requestName, function ($query, $requestName) {
                $spaceConversion = mb_convert_kana($requestName, 's');
                $wordArraySearched = preg_split('/[\s,]+/', $spaceConversion, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($wordArraySearched as $word) {
                    return $query->where(function ($query) use ($word) {
                        $query->where('name', 'like', '%' . $word . '%');
                    });
                }
            })
            ->when($requestEmail, function ($query, $requestEmail) {
                $spaceConversion = mb_convert_kana($requestEmail, 's');
                $wordArraySearched = preg_split('/[\s,]+/', $spaceConversion, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($wordArraySearched as $word) {
                    return $query->where(function ($query) use ($word) {
                        $query->where('email', 'like', '%' . $word . '%');
                    });
                }
            })
            ->paginate(50);

        return view('admin.company.index', compact('companies'));
    }
}
