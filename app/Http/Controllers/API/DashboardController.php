<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use DateTime;
use DB;

class DashboardController extends Controller
{
    public function index(Request $request){
      $id_user = $request->input('id_user');

      //A.1 Hitung Target Replacement Ratio
      $res = DB::table('variabel_kuisioner_target_rr_answer')
        ->select("answer")
        ->where([
            ['id_user','=',$id_user],
            ['flag','=',1],
            ['kode_kuisioner','=',"TARGET_RR"],
        ])
        ->get()[0];
      $target_replacement_ratio = round($res->answer,2);

      //B.1 Hitung usia diangkat
      $res = User::select('tgl_lahir','tgl_diangkat_pegawai')->where('id',$id_user)->get();
      $date1=date_create("1992-06-24"); //Read tanggal lahir
      $date2=date_create("2018-02-06"); //Read tanggal diangkat
      $diff=date_diff($date1,$date2);

      die(var_dump($res));
      
      return response()->json([
        "status" =>true,
        "message"=>"Testing Hitung Awal!",
        "data_testing" => $res
      ],200);
    }
}
