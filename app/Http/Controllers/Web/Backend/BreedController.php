<?php

namespace App\Http\Controllers\Web\Backend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Breed;
use App\Models\Characteristic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class BreedController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Breed::latest()->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('image', function ($data) {
                    $url = $data->image ? asset($data->image) : asset('backend/assets/images/image-not.png');
                    return '<img src="' . $url . '" alt="image" class="img-fluid" style="height:50px; width:50px">';
                })
                ->addColumn('content', function ($data) {
                    // Strip HTML tags and truncate the content
                    $content = strip_tags($data->content);
                    return strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content;
                })
                ->addColumn('type', function ($data) {
                    // Strip HTML tags and truncate the content
                    $type = ucfirst($data->type);
                    return $type;
                })
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
                   <a href="' . route('admin.breed.edit', $data->id) . '" class="btn btn-primary text-white" title="View">
                       <i class="bx bxs-pencil"></i>
                   </a>
                   <a href="#" onclick="deleteAlert(' . $data->id . ')" class="btn btn-danger text-white" title="Delete">
                       <i class="bx bxs-trash-alt"></i>
                   </a>
               </div>';
                })
                ->rawColumns(['action','content','image', 'type'])
                ->make();
        }
        return view('backend.layouts.breeds.index');
    }


    public function create()
    {
        return view('backend.layouts.breeds.create');
    }


    public function store(Request $request)
    {
        // ✅ Validate the incoming request
        $request->validate([
            'title'   => 'required|string|max:255',
            'sub_title'   => 'required|string',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg',
            'content' => 'required|string',
            'type'    => 'required|in:dog,cat',
        ]);

        // 🗂️ Prepare data for insertion
        $data = [
            'title'   => $request->title,
            'sub_title'   => $request->sub_title,
            'content' => $request->content,
            'type'    => $request->type,
        ];

        try{
            // 📤 Handle image upload if present
            $file = 'image';
            if ($request->hasFile($file)) {
                // Upload the new file
                $randomString = Str::random(10);
                $data[$file]  = Helper::fileUpload($request->file($file), 'breed', $randomString);
            }

            // 💾 Save the data to the database
            Breed::create($data);

            // ✅ Redirect back with a success message
            return redirect()->route('admin.breed.index')->with('t-success', 'Breed added successfully!');
        }catch(\Exception $e){
            return redirect()->route('admin.breed.index')->with('t-error', $e->getMessage());
        }
    }


    public function edit($id)
    {
        $data = Breed::findOrFail($id);
        return view('backend.layouts.breeds.edit', compact('data'));
    }




    public function update(Request $request, $id)
    {
        // ✅ Validate the incoming request
        $request->validate([
            'title'   => 'required|string|max:255',
            'sub_title'   => 'required|string',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg',
            'content' => 'required|string',
            'type'    => 'required|in:dog,cat',
        ]);

        // 🔍 Find the existing breed
        $breed = Breed::findOrFail($id);

        // 🗂️ Prepare data for update
        $data = [
            'title'   => $request->title,
            'sub_title'   => $request->sub_title,
            'content' => $request->content,
            'type'    => $request->type,
        ];

        try {
            // 📤 Handle image upload if present
            $file = 'image';
            if ($request->hasFile($file)) {
                // Delete the old image if it exists
                if ($breed->$file && file_exists(public_path($breed->$file))) {
                    unlink(public_path($breed->$file));
                }

                // Upload the new image
                $randomString = Str::random(10);
                $data[$file]  = Helper::fileUpload($request->file($file), 'breed', $randomString);
            }

            // 💾 Update the breed in the database
            $breed->update($data);

            // ✅ Redirect back with a success message
            return redirect()->back()->with('t-success', 'Breed updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('t-error', $e->getMessage());
        }
    }






    public function destroy($id)
    {
        try {
            // 🔍 Find the existing record
            $breed = Breed::findOrFail($id);
            $characteristics = $breed->characteristics;

            // 🗑️ Delete the associated image if it exists
            if ($breed->image && file_exists(public_path($breed->image))) {
                Helper::fileDelete($breed->image);
            }

            // Delete characteristics and their images
            if ($characteristics->count() > 0) {
                foreach ($characteristics as $characteristic) {
                    if ($characteristic->image && file_exists(public_path($characteristic->image))) {
                        Helper::fileDelete($characteristic->image);
                    }
                    $characteristic->delete();
                }
            }

            // ❌ Delete the record from the database
            $breed->delete();

            // ✅ Redirect back with a success message
            return response()->json(['success' => true, 'message' => 'Data deleted successfully.']);
        } catch (\Exception $e) {
            // ⚠️ Handle errors gracefully
            return response()->json(['errors' => true, 'message' => 'Data failed to delete']);
        }
    }
}
