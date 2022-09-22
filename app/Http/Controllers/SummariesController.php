<?php

namespace App\Http\Controllers;

use App\Models\Summary_send;
use App\Models\Vacancy;
use Exception;
use Illuminate\Http\Request;

class SummariesController extends Controller
{

    public function index()
    {
        try {
            if (!empty(auth()->user()->company)) return response()->json(['errors' => 'У вас не может быть резюме']);
            $db = Summary_send::with("vacancy")->where('id_user', auth()->user()->id)->get(["status","id_vacancy","updated_at","id"]);
            return response()->json(['items' => $db]);
        } catch (Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()]);
        }
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function edit(Summary_send $summary)
    {
        try {
            if (empty(auth()->user()->company))
                return response()->json(['errors' => 'Вы не работодатель']);
            if (!isset($request->status) || ($request->status !== false && $request->status !== true))
                return response()->json(['errors' => 'Статус не указан или указан не верно']);
            if ($summary->vacancy()->where('employer->id', auth()->user()->id)->count() > 0) {
                $summary->update([
                    'status' => $request->status ? 'Одобрена' : 'Отклонена',
                ]);
                $summary->save();
                $db = Vacancy::with(['summary' => function ($query) {
                    $query->where("status", "!=", "Отклонена");
                }])->where("employer->id", auth()->user()->id)->get();
                return response()->json(['items' => $db]);
            } else return response()->json(['errors' => 'Вы не можете изменить статус этого резюме']);
        }catch (Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id=null)
    {
        try {
            if (!empty(auth()->user()->company)) return response()->json(['errors' => 'У вас не может быть резюме']);
            if(empty($id)) Summary_send::where(['id_user'=>auth()->user()->id])->delete();
            else Summary_send::where(['id_user'=>auth()->user()->id,'id'=>$id])->delete();

            $db = Summary_send::with("vacancy")->where('id_user', auth()->user()->id)->get(["status","id_vacancy","updated_at","id"]);
            return response()->json(['items' => $db]);
        } catch (Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()]);
        }
    }
}
