<?php

namespace App\Http\Controllers;

use App\Models\Summary_send;
use App\Models\Vacancy;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VacancyController extends Controller
{

    public function index(Request $request, $id = null)
    {
//        $start = microtime( true );
        if (!empty($id))
            return response()->json(Vacancy::find($id));

        $url = "https://api.hh.ru/vacancies";
        $urlDictionaries = "https://api.hh.ru/dictionaries";

        $response = [];

        if (isset($request->experience)) $response['experience'] = $request->experience;
        if (isset($request->schedule)) $response['schedule'] = $request->schedule;
        if (isset($request->area)) $response["area"] = $request->area;
        if (isset($request->specializations)) $response['specialization'] = $request->specializations;
        if (isset($request->date_from))
            switch ($request->date_from):
                case(2):
                    $response['date_from'] = Carbon::now()->subMonth()->format('Y-m-d');
                    break;
                case(3):
                    $response['date_from'] = Carbon::now()->subWeek()->format('Y-m-d');
                    break;
                case(4):
                    $response['date_from'] = Carbon::now()->subDays(3)->format('Y-m-d');
                    break;
                case(5):
                    $response['date_from'] = Carbon::now()->subDay()->format('Y-m-d');
                    break;
            endswitch;
        if (isset($request->only_with_salary)) $response['only_with_salary'] = $request->only_with_salary;
        if (isset($request->salary)) $response['salary'] = $request->salary;
        if (isset($request->currency)) $response['currency'] = $request->currency;

        $dictionaries = Http::get($urlDictionaries)->json();

        $db = new Vacancy();

        foreach ($response as $id => $value) {
            switch ($id):
                case ('experience') :
                    $db = $db->where('experience->id', '=', $value);
                    break;
                case ('schedule') :
                    $db = $db->whereIn('schedule->id', (array)$value);
                    break;
                case ('area') :
                    $db = $db->whereIn('area->id', (array)$value);
                    break;
                case ('specialization') :
                    $db = $db->whereIn('specialization->id', (array)$value);
                    break;
                case ('date_from') :
                    $db = $db->where('created_at', '>', $value);
                    break;
                case ('only_with_salary') :
                    if ($value)
                        $db = $db->where('salary->from', '!=', null)->orWhere('salary->to', '!=', null);
                    break;
                case ('salary') :
                    $currency = Arr::first(Arr::where($dictionaries['currency'], function ($value) use ($response) {
                        return $value['code'] == ($response['currency'] ?? 'RUR');
                    }));

                    $db = $db->where(function ($queryOne) use ($response, $currency, $dictionaries) {
                        foreach ($dictionaries['currency'] as $idCurrency => $valueCurrency)
                            if ($idCurrency == 0)
                                $queryOne->where('salary->currency', $valueCurrency['code'])->where(function ($query) use ($currency, $response, $valueCurrency) {
                                    $query
                                        ->where('salary->from', '>=', (float)$response['salary'] / (float)$currency['rate'] * (float)$valueCurrency['rate'])
                                        ->orWhere('salary->to', '>=', (float)$response['salary'] / (float)$currency['rate'] * (float)$valueCurrency['rate']);
                                });
                            else
                                $queryOne->orWhere(function ($query) use ($currency, $response, $valueCurrency) {
                                    $query
                                        ->where('salary->from', '>=', (float)$response['salary'] / (float)$currency['rate'] * (float)$valueCurrency['rate'])
                                        ->orWhere('salary->to', '>=', (float)$response['salary'] / (float)$currency['rate'] * (float)$valueCurrency['rate']);
                                })->where('salary->currency', $valueCurrency['code']);
                    });
                    break;
            endswitch;
        }

        $db = $db->paginate(20);

        $page = (int)($request->page ?? 1);
        $vacanciesResponse = [];
        $vacanciesResponse['per_page'] = 20;
        $vacanciesResponse['page'] = $page;


        if (sizeof($db->items()) >= 20) {
            $vacanciesResponse['items'] = $db->items();
            $responseApi = Http::get($url, $response)->object();
        } else {
            if (sizeof($db->items()) == 0)
                if($db->total()!=0)
                    $response['page'] = $page - 1 - $db->lastPage();
                else $response['page'] = $page - $db->lastPage();
            else $response['page'] = $page - $db->lastPage();
            $responseApi = Http::get($url, $response)->object();
            if (sizeof($db->items()) == 0 && $db->total() % 20) {
                if ($responseApi->pages - 1 > $response['page']) {
                    $response['page'] += 1;
                    $responseApi2 = Http::get($url, $response)->object();
                    $vacanciesResponse['items'] = array_slice(array_merge_recursive($responseApi->items, $responseApi2->items), 20 - $db->total() % 20, 20);
                } else {
                    $vacanciesResponse['items'] = array_slice($responseApi->items, 20 - $db->total() % 20, $db->total() % 20);
                }
            } else $vacanciesResponse['items'] = array_slice(array_merge_recursive(collect($db->items())->toArray(), $responseApi->items), 0, 20);
        }

        if ($responseApi->found >= 2000) $vacanciesResponse['found'] = 2000 + $db->total();
        else $vacanciesResponse['found'] = $responseApi->found + $db->total();

        $vacanciesResponse['pages'] = (int)ceil($vacanciesResponse['found'] / 20);

//        dd(microtime( true ) - $start);
        return response()->json($vacanciesResponse);
    }

    public function indexEmployer(){
        try {
            if (empty(auth()->user()->company)) return response()->json(['errors' => 'Вы не работодатель']);
                $db=Vacancy::with(['summary'=>function($query){
                    $query->where("status","!=","Отклонена");
                }])->where('employer->id', auth()->user()->id)->get();
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
        $dictionaries = Http::get("https://api.hh.ru/dictionaries");

        if (empty($request->user()->company)) return response()->json(['errors' => 'Аккаунт не работодателя']);

        $name = $request->name ?? null;

        $employer = [];
        $employer['id'] = $request->user()->id;
        $employer['name'] = $request->user()->company;

        if (!empty($request->image)) {
            $name_image = Str::random(24) . "." . $request->image->extension();
            Storage::putFileAs('images', $request->image, $name_image);
            $employer['logo_urls']['original'] = route('/') . '/images/' . $name_image;
            $employer['logo_urls']['240'] = null;
            $employer['logo_urls']['90'] = null;
        } else $employer['logo_urls'] = null;

        $area = [];
        $area['id'] = $request->area;
        $area['name'] = Http::get("https://api.hh.ru/area/" . $area['id'])['name'];

        if (isset($request->salary_to) || isset($request->salary_from) || isset($request->currency)) {
            $salary['to'] = $request->salary_to ?? null;
            $salary['from'] = $request->salary_from ?? null;
            $salary['currency'] = $request->currency ?? null;
        } else $salary = null;

        $experience['id'] = $request->experience;
        $experience['name'] = collect($dictionaries->object()->experience)->where("id", $experience['id'])->name;

        $description = $request->description ?? null;

        $specialization['id'] = $request->specialization;

        foreach ()

        $first=Http::get("https://api.hh.ru/specializations")->object()->first(function ($value) use ($specialization) {
            if(collect($value['specializations'])->firstWhere('id', $specialization['id'])!=null) {
                $specialization['name'] = collect($value['specializations'])->firstWhere('id', $specialization['id'])->name;
            }
            return $value['id'] == $specialization['id'];
        });
        Http::get("https://api.hh.ru/specializations")->collect()
        ->each(function ($item) use ($specialization) {
            if(collect($item['specializations'])->firstWhere('id', $specialization['id'])!=null)
                $specialization['name'] = collect($item['specializations'])->firstWhere('id', $specialization['id'])->name;
            if($item['id'] == $specialization['id']) $specialization['name'] = $item['name'];
        });

        $specialization['name'] = Http::get("https://api.hh.ru/specializations")->collect()->first(function ($item, $key) use ($specialization) {
            $specialization['name'] = collect($item['specializations'])->firstWhere('id', $specialization['id'])->name;
            return $item['id'] == $specialization['id'];
        })->name;

        if (isset($request->contacts_name) || isset($request->contacts_email) || isset($request->contacts_phone)) {
            $contacts['name'] = $request->contacts_name ?? null;
            $contacts['email'] = $request->contacts_email ?? null;
            $contacts['phones'] = $request->contacts_phones ?? null;
        } else $contacts = null;

        $schedule['id'] = $request->schedule_name;
        $schedule['name'] = collect($dictionaries->object()->schedule)->where("id", $schedule['id'])->name;

        try {
            $db = Vacancy::updateOrCreate(
                [
                    'id' => $request->id??null
                ],
                [
                    'name' => $name,
                    'employer' => $employer,
                    'area' => $area,
                    'salary' => $salary,
                    'experience' => $experience,
                    'description' => $description,
                    'specialization' => $specialization,
                    'contacts' => $contacts,
                    'schedule' => $schedule,
                ]);
            return response()->json(['id' => $db]);
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

    public function destroy($id = null)
    {
        try {
            if (empty(auth()->user()->company)) return response()->json(['errors' => 'Вы не работодатель']);
            if (!empty($id)) {
                if (Vacancy::find($id)->employer["id"] == auth()->user()->id) {
                    Summary_send::where('id_vacancy', $id)->delete();
                    Vacancy::where('id', $id)->delete();
                    $db = Vacancy::with(['summary' => function ($query) {
                        $query->where("status", "!=", "Отклонена");
                    }])->where("employer->id", auth()->user()->id)->get();
                    return response()->json(['data' => $db]);
                } else {
                    return response()->json(['errors' => 'Вы не можете удалить эту вакансию']);
                }
            } else {
                $vacancies = Vacancy::where("employer->id", auth()->user()->id)->get()->pluck('id');
                Summary_send::whereIn('id_vacancy', $vacancies)->delete();
                Vacancy::where("employer->id", auth()->user()->id)->delete();
                return response()->json(['data' => []]);
            }
        } catch (Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()]);
        }
    }
}
