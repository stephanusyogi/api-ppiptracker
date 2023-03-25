<?php

namespace App\Http\Controllers\API;

use App\Models\Question;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\QuestionResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $query = Question::query();
        $sort_field = $request->input('sort_field');
        $sort_order = $request->input('sort_order');
        $search = $request->input('search');
        $per_page = $request->input('per_page');

        if ($sort_field && $sort_order) {
            $query->orderBy($sort_field, $sort_order);
        }

        if($search){
            $query->where('question','LIKE','%'.$search.'%')
            ->orWhere('status_pensiun','LIKE','%'.$search.'%');
        }

        $questions = $query->latest()->paginate($per_page ? $per_page : 2);

        return new QuestionResource(true, 'List Data Questions!', $questions);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question'     => 'string|required',
            'status_pensiun'     => 'string|required',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $question = Question::create([
            'question'     => $request->question,
            'status_pensiun'     => $request->status_pensiun,
        ]);
        
        //return response
        return new QuestionResource(true, 'Data Question Berhasil Ditambahkan!', $question);
    }
    
    public function show(Question $question)
    {
        return new QuestionResource(true, 'Data Question Ditemukan!', $question);
    }
    
    public function destroy(Question $question)
    {
        //delete post
        $question->delete();

        //return response
        return new QuestionResource(true, 'Data Question Berhasil Dihapus!', null);
    }

    
    public function update(Request $request, Question $question)
    {
        //define validation rules
        $validator = Validator::make($request->all(),[
            'question' => 'string',
            'status_pensiun' => 'string'
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $question->update([
            'question' => $request->question,
            'status_pensiun' => $request->status_pensiun,
        ]);


        //return response
        return new QuestionResource(true, 'Data Question Berhasil Diubah!', $question);
    }

    public function answer_question(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode_kuisioner'     => 'required',
            'id_user'     => 'required',
            'answer'     => 'required',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        $check = DB::table('variabel_kuisioner_target_rr_answer')
                ->where([
                    ['id_user', '=', $request->id_user],
                    ['kode_kuisioner', '=', $request->kode_kuisioner],
                    ])
                ->get()->toArray();

        if (count($check) > 0) {
            DB::table('variabel_kuisioner_target_rr_answer')->where('kode_kuisioner',$request->kode_kuisioner)->update([
                'flag' => 0,
            ]);

            DB::table('variabel_kuisioner_target_rr_answer')->insert([
                'id'=> (string) Str::uuid(),
                'id_user' => $request->id_user,
                'kode_kuisioner' => $request->kode_kuisioner,
                'answer' => $request->answer,
                'flag' => 1,
            ]);
        } else {
            DB::table('variabel_kuisioner_target_rr_answer')->insert([
                'id'=> (string) Str::uuid(),
                'id_user' => $request->id_user,
                'kode_kuisioner' => $request->kode_kuisioner,
                'answer' => $request->answer,
                'flag' => 1,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Answer Submit Success'
        ],200);

    }

    public function user_questions($id){
        function getData($query, $id_user){
            $questions = DB::table('variabel_kuisioner_target_rr_answer')
            ->select("answer")
            ->where([
                ['id_user','=',$id_user],
                ['flag','=',1],
                ['kode_kuisioner','=',$query],
            ])
            ->get()->toArray();

            return $questions[0]["answer"]."%";
            
        }

        $user_answer = array(
            "GAJI" => getData("GAJI", $id),
            "BEKERJA_TOTAL_PENGELUARAN" => getData("BEKERJA_TOTAL_PENGELUARAN", $id),
            "PENSIUN_TOTAL_PENGELUARAN" => getData("PENSIUN_TOTAL_PENGELUARAN", $id),
            "TARGET_RR" => getData("TARGET_RR", $id),
            "FREE_CASHFLOW" => getData("FREE_CASHFLOW", $id),
            "KONSUMSI" => array(
                "BEKERJA_KONSUMSI" => getData("BEKERJA_KONSUMSI", $id),
                "PENSIUN_KONSUMSI" => getData("PENSIUN_KONSUMSI", $id),
            ),
            "UTILITIES" => array(
                "BEKERJA_UTILITIES" => getData("BEKERJA_UTILITIES", $id),
                "PENSIUN_UTILITIES" => getData("PENSIUN_UTILITIES", $id),
            ),
            "TRANSPORTASI" => array(
                "BEKERJA_TRANSPORTASI" => getData("BEKERJA_TRANSPORTASI", $id),
                "PENSIUN_TRANSPORTASI" => getData("PENSIUN_TRANSPORTASI", $id),
            ),
            "CICILAN" => array(
                "BEKERJA_CICILAN" => getData("BEKERJA_CICILAN", $id),
                "PENSIUN_CICILAN" => getData("PENSIUN_CICILAN", $id),
            ),
            "IBADAH" => array(
                "BEKERJA_IBADAH" => getData("BEKERJA_IBADAH", $id),
                "PENSIUN_IBADAH" => getData("PENSIUN_IBADAH", $id),
            ),
            "PENDIDIKAN" =>array(
                "BEKERJA_PENDIDIKAN" => getData("BEKERJA_PENDIDIKAN", $id),
                "PENSIUN_PENDIDIKAN" => getData("PENSIUN_PENDIDIKAN", $id),
            ),
            "KESEHATAN" =>array(
                "BEKERJA_KESEHATAN" => getData("BEKERJA_KESEHATAN", $id),
                "PENSIUN_KESEHATAN" => getData("PENSIUN_KESEHATAN", $id),
            ),
            "HIBURAN" => array(
                "BEKERJA_HIBURAN" => getData("BEKERJA_HIBURAN", $id),
                "PENSIUN_HIBURAN" => getData("PENSIUN_HIBURAN", $id),
            ),
            "INVESTASI" => array(
                "BEKERJA_INVESTASI" => getData("BEKERJA_INVESTASI", $id),
                "PENSIUN_INVESTASI" => getData("PENSIUN_INVESTASI", $id),
            ),
            "LAIN" => array(
                "BEKERJA_LAIN" => getData("BEKERJA_LAIN", $id),
                "PENSIUN_LAIN" => getData("PENSIUN_LAIN", $id),
            ),
            
        );
        
        return response()->json([
            'status' => true,
            'message' => 'List User Answers',
            'data' => $user_answer
        ],200);
    }

    
    public function get_kuisioner(){
        $kuisioner = DB::table('variabel_kuisioner_target_rr')
                ->select('kuisioner', 'kode_kuisioner')
                ->where([
                    ['saat_pensiun', '=', 0],
                    ['general_kuisioner', '=', 1],
                ])
                ->get()->toArray();

        return response()->json([
            'status' => true,
            'message' => 'List Questions',
            'data' => $kuisioner
        ],200);
    }

}

