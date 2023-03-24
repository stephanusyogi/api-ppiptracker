<?php

namespace App\Http\Controllers\API;

use App\Models\Question;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\QuestionResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
            'id_question'     => 'required',
            'id_user'     => 'required',
            'answer'     => 'required',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::table('question_answers')->insert([
            'id_question' => $request->id_question,
            'id_user' => $request->id_user,
            'answer' => $request->answer,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Answer Submit Success'
        ],200);

    }

    public function user_questions($id){
        $questions = DB::table('question_answers')
        ->leftJoin('questions', 'question_answers.id_question', '=', 'questions.id')
        // ->rightJoin('users', 'question_answers.id_user', '=', 'users.id')
        ->where('id_user','=',$id)
        ->get();
        
        return response()->json([
            'status' => true,
            'message' => 'List Questions User',
            'data' => $questions
        ],200);
    }

    
    public function get_kuisioner(){
        $kuisioner = DB::table('variabel_kuisioner_target_rr')
                ->where('saat_pensiun', '=', 0)
                ->get()->toArray();

        return response()->json([
            'status' => true,
            'message' => 'List Questions',
            'data' => $kuisioner
        ],200);
    }

}

