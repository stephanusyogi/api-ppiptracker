<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PengumumanController extends Controller
{
  public function index(Request $request){
    $id = $request->input('id');
    if ($id) {
      $pengumuman = DB::table('pengumuman')
      ->select('*')->where('id', $id)->get();
  
      return response()->json([
          "status" =>true,
          "message"=>"Lists Pengumuman!",
          "data" => $pengumuman
      ],200);
    } else {
      $pengumuman = DB::table('pengumuman')
      ->select('*')->get();
  
      return response()->json([
          "status" =>true,
          "message"=>"Lists Pengumuman!",
          "data" => $pengumuman
      ],200);
    }
  }

  public function store(Request $request){
    DB::table('pengumuman')->insert([
      'id' => (string) Str::uuid(),
      'id_admin' => $request->id_admin,
      'judul' => $request->judul,
      'deskripsi' => $request->deskripsi,
    ]);
    
    return response()->json([
        "status" =>true,
        "message"=>"Pengumuman Berhasil Ditambahkan!",
    ],200);
  }

  public function update(Request $request){
    DB::table('pengumuman')
      ->where('id', $request->id)
      ->update([
        'id_admin' => $request->id_admin,
        'judul' => $request->judul,
        'deskripsi' => $request->deskripsi,
    ]);
    
    return response()->json([
        "status" =>true,
        "message"=>"Pengumuman Berhasil Ditambahkan!",
    ],200);
  }

  public function delete(Request $request){
    DB::table('pengumuman')->where('id', $request->id)->delete();

    return response()->json([
      "status" =>true,
      "message"=>"Pengumuman Berhasil Ditambahkan!",
    ],200); 
  }
}