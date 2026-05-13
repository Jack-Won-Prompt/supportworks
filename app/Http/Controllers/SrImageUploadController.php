<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SrImageUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|file|image|max:10240',
        ]);

        $file = $request->file('image');
        $name = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $dir = public_path('sr-images');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file->move($dir, $name);

        return response()->json([
            'url' => asset('sr-images/' . $name),
        ]);
    }
}
