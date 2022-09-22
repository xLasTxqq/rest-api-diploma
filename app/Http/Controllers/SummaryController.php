<?php

namespace App\Http\Controllers;

use App\Models\Summary;
use App\Models\Summary_send;
use Exception;
use Illuminate\Http\Request;

class SummaryController extends Controller
{

    public function index()
    {
        try {
            if (!empty(auth()->user()->company)) return response()->json(['errors' => 'У вас не может быть резюме']);
            $db = Summary::where('id_user', auth()->user()->id)->get();
            return response()->json($db);
        } catch (Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()]);
        }
    }

    public function create($id)
    {
        try {
            if (!empty(auth()->user()->company)) return response()->json(['errors' => 'У вас не может быть резюме']);
            if (Summary::where('id_user', auth()->user()->id)->count()<1) return response()->json(['errors' => 'У вас нет резюме']);
            $summary = Summary::where('id_user', auth()->user()->id)->first();
            $db=Summary_send::updateOrCreate([
                'id_user' => $summary->id_user,
                'id_vacancy' => $id
            ],[
                'name' => $summary->name,
                'surname' => $summary->surname,
                'email' => $summary->email,
                'phone' => $summary->phone,
                'education' => $summary->education,
                'about_myself' => $summary->about_myself,
            ]);
            return response()->json(['id' => $db]);
        } catch (Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            if (!empty(auth()->user()->company)) return response()->json(['errors' => 'У вас не может быть резюме']);

            $db = Summary::updateOrCreate([
                'id_user' => auth()->user()->id,
            ],[
                'name' => $request->name,
                'surname' => $request->surname,
                'email' => $request->email,
                'phone' => $request->phone,
                'education' => $request->education,
                'about_myself' => $request->about_myself ?? null,
                'updated_at'=>now()
            ]);

            return response()->json(['id'=>$db]);

        } catch (Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()]);
        }
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
