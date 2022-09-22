<?php

namespace App\Http\Controllers;

use App\Models\Summary;
use App\Models\Summary_send;
use App\Models\User;
use App\Models\Vacancy;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Http\Request;
use function Symfony\Component\String\length;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MainController extends Controller
{

    public function vacancy_all(){
        try {
            if (empty(auth()->user()->company)) return json_encode(['errors' => ['message' => 'Вы не работодатель']]);
            $db=Vacancy::where('employer->id',auth()->user()->id)->get();
            return json_encode($db);
        }catch (Exception $exception){
            return json_encode(['errors' => $exception->getMessage()]);
        }
    }

    public function vacancy_change(Request $request, $summary)
    {
        try {
            if (empty(auth()->user()->company))
            return response(['errors' => 'Вы не работодатель'],200)->header('Content-Type', 'application/json');
            if(!isset($request->status)||($request->status!=false&&$request->status!=true))
            return response(['errors' => 'Статус не указан или указан не верно'],200)->header('Content-Type', 'application/json');
            if (Summary_send::find($summary)->vacancy()->where('employer->id',auth()->user()->id)->count()>0){
                $status=$request->status==true?'Одобрена':'Отклонена';
                Summary_send::where('id', $summary)->update([
                    'status'=>$status,
                ]);
                $db=Vacancy::with(['summary'=>function($query){
                    $query->where("status","!=","Отклонена");
                }])->where("employer->id",auth()->user()->id)->get();
                return response(['data' => $db],200)->header('Content-Type', 'application/json');
            } else {
                return response(['errors' => 'Вы не можете изменить статус этого резюме'],200)->header('Content-Type', 'application/json');
            }
        } catch (Exception $exception) {
            return response(['errors' => $exception->getMessage()],200)->header('Content-Type', 'application/json');
        }
    }

    public function vacancy_delete($vacancy = null)
    {

    }

    public function summaries_vacancy($vacancy=null)
    {
        try {
            if (empty(auth()->user()->company)) return json_encode(['errors' => ['message' => 'Вы не работодатель']]);
            if(empty($vacancy)){
                $db=Vacancy::with(['summary'=>function($query){
                    $query->where("status","!=","Отклонена");
                }])->where('employer->id', auth()->user()->id)->get();
                return response(['data' => $db],200)->header('Content-Type', 'application/json');
            }
            else if (json_decode(Vacancy::where('id', $vacancy)->first()->employer)->id == auth()->user()->id) {
                $db = Summary_send::where('id_vacancy', 'vacancy')->get();
                return json_encode($db);
            } else {
                 return response(['errors' => 'Вы не можете узнать отправленные резюме к этой вакансии'],200)->header('Content-Type', 'application/json');
            }
        } catch (Exception $exception) {
            return response(['errors' => $exception->getMessage()],200)->header('Content-Type', 'application/json');
        }
    }

    public function summary_user()
    {
        try {
            if (!empty(auth()->user()->company)) return json_encode(['errors' => ['message' => 'У вас не может быть резюме']]);
            $db = Summary::where('id_user', auth()->user()->id)->first();
            // return json_encode($db);
            if(empty($db))$db="{}";

          return response($db,200)->header('Content-Type', 'application/json');

        } catch (Exception $exception) {
            // 'errors' => $exception->getMessage()
            return response(['errors' => $exception->errorInfo[2]],200)->header('Content-Type', 'application/json');
        }
    }
    public function summary_user_all()
    {
        try {
            if (!empty(auth()->user()->company)) return json_encode(['errors' => ['message' => 'У вас не может быть резюме']]);
            $db = Summary_send::with("vacancy")->where('id_user', auth()->user()->id)->get(["status","id_vacancy","updated_at","id"]);
            return response(['data' => $db],200)->header('Content-Type', 'application/json');
        } catch (Exception $exception) {
            return response(['errors' => $exception->getMessage()],200)->header('Content-Type', 'application/json');
        }
    }
    public function summary_user_delete($id=null)
    {
        try {
            if (!empty(auth()->user()->company)) return json_encode(['errors' => ['message' => 'У вас не может быть резюме']]);
            if(empty($id)){
                Summary_send::where(['id_user'=>auth()->user()->id])->delete();
            }
            else{
                Summary_send::where(['id_user'=>auth()->user()->id,'id'=>$id])->delete();
            }
            $db = Summary_send::with("vacancy")->where('id_user', auth()->user()->id)->get(["status","id_vacancy","updated_at","id"]);
            return response(['data' => $db],200)->header('Content-Type', 'application/json');
        } catch (Exception $exception) {
           return response(['errors' => $exception->getMessage()],200)->header('Content-Type', 'application/json');
        }
    }

    public function summary_send($vacancy)
    {
        try {
            if (!empty(auth()->user()->company)) return json_encode(['errors' => ['message' => 'У вас не может быть резюме']]);
            if (Summary::where('id_user', auth()->user()->id)->count()<1) return json_encode(['errors' => ['message' => 'У вас нет резюме']]);
            $db = Summary::where('id_user', auth()->user()->id)->first();
            $id=Summary_send::updateOrCreate([
                'id_user' => $db->id_user,
                'id_vacancy' => $vacancy
            ],[
                'name' => $db->name,
                'surname' => $db->surname,
                'email' => $db->email,
                'phone' => $db->phone,
                'education' => $db->education,
                'about_myself' => $db->about_myself,
                ]);
            return response(['id' => $id],200)->header('Content-Type', 'application/json');
        } catch (Exception $exception) {
            return response(['errors' => $exception->getMessage()],200)->header('Content-Type', 'application/json');
        }
    }

    public function summary_create(Request $request)
    {
        try {
            if (User::where('id', auth()->user()->id)->get()->count() < 1) return json_encode(['errors' => ['message' => 'Работодатель не может создать резюме']]);
            $name = $request->name ?? null;
            $surname = $request->surname ?? null;
            $email = $request->email ?? null;
            $phone = $request->phone ?? null;
            $education = $request->education ?? null;
            $about_myself = $request->about_myself ?? null;
            $id_user = auth()->user()->id ?? null;

            $db = Summary::updateOrCreate([

                'id_user' => $id_user,
            ],[
                'name' => $name,
                'surname' => $surname,
                'email' => $email,
                'phone' => $phone,
                'education' => $education,
                'about_myself' => $about_myself,
                'updated_at'=>now()
                ]);

            return response(['id' => $db->id],200)->header('Content-Type', 'application/json');
            // return json_encode(['id' => $db->id]);

        } catch (Exception $exception) {
            // 'errors' => $exception->getMessage()
            return response(['errors' => $exception->errorInfo[2]],200)->header('Content-Type', 'application/json');
            // return json_encode(['errors' => $exception]);
        }

    }

    public function filters(){
        $dictionaries = "https://api.hh.ru/dictionaries";
        $areas="https://api.hh.ru/areas";
        $specializations="https://api.hh.ru/specializations";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $dictionaries);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        $dictionaries_request = curl_exec($ch);

        curl_setopt($ch, CURLOPT_URL, $areas);
        $areas_request=curl_exec($ch);

        curl_setopt($ch, CURLOPT_URL, $specializations);
        $specializations_request=curl_exec($ch);
        curl_close($ch);

        $filter['experience']=json_decode($dictionaries_request)->experience;
        $filter['schedule']=json_decode($dictionaries_request)->schedule;
        $filter['area']=json_decode($areas_request);
        $filter['specializations']=json_decode($specializations_request);
        $filter['date_from']=[['id'=>1,'name'=>'За всё время'],
            ['id'=>2,'name'=>'За месяц'],
            ['id'=>3,'name'=>'За неделю'],
            ['id'=>4,'name'=>'За 3 дня'],
            ['id'=>5,'name'=>'За сутки']];
        $filter['currency'] = json_decode($dictionaries_request)->currency;
        // dd($filter);
        // return json_encode($filter);
        return response($filter,200)->header('Content-Type', 'application/json');
    }

    public function vac(Request $request,$id=null){
        $start = microtime( true );
        if (!empty($request->id)) {
            return json_encode(Vacancy::where('id', $request->id)->first());
        }
//        $url = "https://api.hh.ru/vacancies?page={$page}&";
        $url = "https://api.hh.ru/vacancies?";

        $db=new Vacancy;

        $page=(int)($request->page??1);
        $salary=$request->salary;
        $area=$request->area;
        $specialization=$request->specializations;
        $experience=$request->experience;
        $schedule=$request->schedule;
        $date_from=$request->date_from;
        $only_with_salary=$request->only_with_salary;
        $currency=$request->currency;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

        if(!empty($salary)){
            foreach($salary as $value){
            $url .= "salary={$value}&";
            $db=$db->where('salary->to','>=',$value);
            }
        }
        if(!empty($currency)){
            foreach($currency as $value){
            $url.= "currency={$value}&";
            }
        }
        if(!empty($only_with_salary)){
            foreach($only_with_salary as $value){
            $url .= "only_with_salary={$value}&";
            if((boolean)$only_with_salary==true)
            $db=$db->where('salary','!=',null);
            }
        }
        if(!empty($area)){
            foreach($area as $value){
            $url .= "area={$value}&";

            curl_setopt($ch, CURLOPT_URL, "https://api.hh.ru/areas/{$value}");
            $request = curl_exec($ch);

            $db=$db->where('area->name',json_decode($request)->name);
            }
        }
        if(!empty($specialization)){
            foreach($specialization as $value){
            $url .= "specialization={$value}&";

            curl_setopt($ch, CURLOPT_URL, "https://api.hh.ru/specializations");
            $request = curl_exec($ch);

            $spec=collect(json_decode($request))->where('id',$value)->pluck('name');
            if(!empty($spec[0]))
            $db=$db->where('name',$spec[0]);
            }
        }
        if(!empty($experience)){
            foreach($experience as $value){
            $url .= "experience={$value}&";

            curl_setopt($ch, CURLOPT_URL, "https://api.hh.ru/dictionaries");
            $request = curl_exec($ch);

            $exp=collect((object)json_decode($request)->experience)->where('id',$value)->pluck('name');
            if(!empty($exp[0]))
                $db=$db->where('experience->name',$exp[0]);
            }
        }
        if(!empty($schedule)){
            foreach($schedule as $value){
            $url .= "schedule={$value}&";

            curl_setopt($ch, CURLOPT_URL, "https://api.hh.ru/dictionaries");
            $request = curl_exec($ch);

            $sch=collect((object)json_decode($request)->schedule)->where('id',$value)->pluck('name');
            if(!empty($sch[0]))
                $db=$db->where('employment->name',$sch[0]);
            }
        }
        if(!empty($date_from)) {
            foreach($date_from as $value){
            switch ($value) :
                case(2) :
                    $time = Carbon::now()->subMonth()->format('Y-m-d');
                    $url .= "date_from={$time}&";
                    $db=$db->where('created_at','>',$time);
                    break;

                case(3):
                    $time = Carbon::now()->subWeek()->format('Y-m-d');
                    $url .= "date_from={$time}&";
                    $db=$db->where('created_at','>',$time);
                    break;

                case(4):
                    $time = Carbon::now()->subDays(3)->format('Y-m-d');
                    $url .= "date_from={$time}&";
                    $db=$db->where('created_at','>',$time);
                    break;

                case(5):
                    $time = Carbon::now()->subDay()->format('Y-m-d');
                    $url .= "date_from={$time}&";
                    $db=$db->where('created_at','>',$time);
                    break;
                default :
                    break;
            endswitch;
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        $request = curl_exec($ch);
        if(json_decode($request)->found>2000)
            $all['found']=2000+$db->count();
        else $all['found']=json_decode($request)->found+$db->count();

        $all['per_page']=20;
        $all['page']=$page;

        $all['pages']=$db->paginate(20)->lastPage()+json_decode($request)->pages-1;

        if($page<1||$page>$all['pages']) return json_encode(['error'=>'Неверно указанна страница']);

        $db_vac=$db->paginate(20)->items();

        for ($i=0; $i<count($db_vac);$i++){
            $db_vac[$i]['url']=route('vac',$db_vac[$i]['id']);
        }

        if($db->paginate(20)->count()==20){
            $all['items']=$db_vac;
        }
        else{
            $url.="page=".abs($page-$db->paginate(20)->lastPage());
            curl_setopt($ch, CURLOPT_URL, $url);
            $request = curl_exec($ch);
            $all['items']=array_slice(array_merge_recursive($db_vac,json_decode($request)->items),0,20);
        }
        curl_close($ch);

        // dd($all);
        // return json_encode($all);
        dd(microtime( true ) - $start);
        return response($all,200)->header('Content-Type', 'application/json');
    }

    public function vacancies(Request $request, $page = 1)
    {
        // $page=(int)($request->page??$page);
        // return json_encode($request->page);
//        if(!empty($request->hh)){
//            $hhUrl = "https://api.hh.ru/vacancies/{$request->hh}";
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
//            curl_setopt($ch, CURLOPT_URL, $hhUrl);
//            curl_setopt($ch, CURLOPT_HEADER, 0);
//            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
//            $hhVacancy = curl_exec($ch);
//            curl_close($ch);
//            return json_decode($hhVacancy);
//        }
        if (!empty($request->id)) {
            return json_encode(Vacancy::where('id', $request->id)->first());
        }
        $db = Vacancy::all();
        $dbCount = $db->count();
        $hhVacancies = '';
        $hhVacanciesBefore = '';

        if ($page < ceil($dbCount / 20)) {
            $hhUrl = "https://api.hh.ru/vacancies";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_URL, $hhUrl);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            $hhVacancies = curl_exec($ch);
            curl_close($ch);
        } else {
            if ($dbCount % 20 == 0) $hhPage = $page - ceil($dbCount / 20 + 1);
            else $hhPage = $page - ceil($dbCount / 20);
            if ($hhPage < 100) {
                $hhUrl = "https://api.hh.ru/vacancies?page={$hhPage}";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_URL, $hhUrl);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
                $hhVacancies = curl_exec($ch);
                curl_close($ch);
            }

            if ($dbCount % 20 !== 0 && $page > ceil($dbCount / 20)) {
                $hhPage -= 1;
                $hhUrl = "https://api.hh.ru/vacancies?page={$hhPage}";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_URL, $hhUrl);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
                $hhVacanciesBefore = curl_exec($ch);
                curl_close($ch);
            }
        }

        if (empty(json_decode($hhVacancies)->pages) && empty(json_decode($hhVacanciesBefore)->pages)) return json_encode(['errors' => ['Page not found']]);
        $give = [];
        $pages = ceil($dbCount / 20 + (json_decode($hhVacancies)->pages ?? json_decode($hhVacanciesBefore)->pages));

        $give['pages'] = (integer)$pages;
        $give['per_page'] = 20;
        $give['page'] = (integer)$page;
        if ((json_decode($hhVacancies)->found ?? json_decode($hhVacanciesBefore)->found) > 2000)
            $give['found'] = 2000 + $dbCount;
        else
            $give['found'] = json_decode($hhVacancies)->found ?? json_decode($hhVacanciesBefore)->found + $dbCount;

        if (empty($hhVacanciesBefore))
            $all = array_merge_recursive(json_decode($db), json_decode($hhVacancies)->items);
        else if (!empty($hhVacancies) && !empty($hhVacanciesBefore)) $all = array_merge_recursive(json_decode($db), json_decode($hhVacanciesBefore)->items, json_decode($hhVacancies)->items);
        else $all = array_merge_recursive(json_decode($db), json_decode($hhVacanciesBefore)->items);

        for ($i = 0; $i < $dbCount; $i++) {
            if (is_string($all[$i]->employer) &&
                is_string($all[$i]->area) &&
                is_string($all[$i]->salary) &&
                is_string($all[$i]->snippet) &&
                is_string($all[$i]->experience) &&
                is_string($all[$i]->employment) &&
                is_string($all[$i]->description) &&
                is_string($all[$i]->contacts) &&
                is_string($all[$i]->schedule)
            ) {
                $all[$i]->employer = json_decode($all[$i]->employer);
                $all[$i]->area = json_decode($all[$i]->area);
                $all[$i]->salary = json_decode($all[$i]->salary);
                $all[$i]->snippet = json_decode($all[$i]->snippet);
                $all[$i]->experience = json_decode($all[$i]->experience);
                $all[$i]->employment = json_decode($all[$i]->employment);
                $all[$i]->description = json_decode($all[$i]->description);
                $all[$i]->contacts = json_decode($all[$i]->contacts);
                $all[$i]->schedule = json_decode($all[$i]->schedule);
            }
        }

        for ($i = 0; $i < 20; $i++) {

            if ($dbCount == 0) {
                $give['items'][$i] = $all[$i];
            } else if ($page <= ceil($dbCount / 20)) {
                $give['items'][$i] = $page == 1 ? $all[$i] : $all[20 * ($page - 1) + $i];
                if (empty($give['items'][$i]->url)) $give['items'][$i]->url = route('vacancy', ['id' => $give['items'][$i]->id]);
            } else if ($page > ceil($dbCount / 20)) {
                if ($dbCount % 20 == 0) $give['items'][$i] = $all[$dbCount + $i];
                else {
                    $give['items'][$i] = $all[$dbCount + (20 - $dbCount % 20) + $i];
                    if (empty($give['items'][$i]->url)) $give['items'][$i]->url = route('vacancy', ['id' => $give['items'][$i]->id]);
                    if (empty($all[$dbCount + (20 - $dbCount % 20) + $i + 1])) break;
                }
            }
        }
        return response($give,200)->header('Content-Type', 'application/json');
    }

    public function new(Request $request)
    {
        //area id
        //schedule id
        //specializations id
        //salary_from
        //contacts_phones
        //salary_to
        //name
        //contacts_name
        //description
        //contacts_email
        //currency id
        //experience id
        //image

        if (empty($request->user()->company)) return json_encode(['errors' => ['message' => 'Аккаунт не работодателя']]);
        $name = $request->name ?? null;
        $employer['id'] = $request->user()->id ?? null;
        $employer['name'] = $request->user()->company ?? null;
        if(!empty($request->image)){
            $name_image = Str::random(24) . "." . $request->image->extension();
            Storage::putFileAs('images', $request->image, $name_image);
            $employer['logo_urls']['original']=route('/').'/images/'.$name_image;
        }
        else $employer['logo_urls'] = null;
        // is_null($request->employer_logo_urls) ? $employer['logo_urls'] = null : $employer['logo_urls']['original'] = $request->employer_logo_urls;
        $area['name'] = $request->area ?? null;
        $salary['to'] = $request->salary_to ?? null;
        $salary['from'] = $request->salary_from ?? null;
        $salary['currency'] = $request->currency ?? null;
        if (is_null($salary['to']) && is_null($salary['from']))
            $salary = null;
//        $created_at=$request->created_at;
        $snippet['responsibility'] = $request->snippet_responsibility ?? null;
        $snippet['requirement'] = $request->snippet_requirement ?? null;
        $experience['name'] = $request->experience ?? null;
        $employment['name'] = $request->employment ?? null;
        $description = $request->description ?? null;
        $contacts['name'] = $request->contacts_name ?? null;
        $contacts['email'] = $request->contacts_email ?? null;
        $contacts['phones'] = $request->contacts_phones ?? null;
        $schedule['name'] = $request->schedule_name ?? null;

        try {
            // $db = Vacancy::updateOrCreate([
            //     'name' => $name,
            //     'employer' => json_encode($employer),
            //     'area' => json_encode($area),
            //     'salary' => json_encode($salary) ?? null,
            //     'snippet' => json_encode($snippet),
            //     'experience' => json_encode($experience),
            //     'employment' => json_encode($employment),
            //     'description' => json_encode($description),
            //     'contacts' => json_encode($contacts),
            //     'schedule' => json_encode($schedule),
            // ],
            // $db = Vacancy::updateOrCreate([
            //     'name' => $name,
            //     'employer' => collect($employer)->toJson(),
            //     'area' => collect($area),
            //     'salary' => collect($salary)->toJson() ?? null,
            //     'snippet' => collect($snippet)->toJson(),
            //     'experience' => collect($experience)->toJson(),
            //     'employment' => collect($employment)->toJson(),
            //     'description' => collect($description)->toJson(),
            //     'contacts' => collect($contacts)->toJson(),
            //     'schedule' => collect($schedule)->toJson(),
            // ],
            $db = Vacancy::updateOrCreate(['id'=>$request->id],[
                'name' => $name,
                'employer' => $employer,
                'area' => $area,
                'salary' => $salary ?? null,
                'snippet' => $snippet,
                'experience' => $experience,
                'employment' => $employment,
                'description' => $description,
                'contacts' => $contacts,
                'schedule' => $schedule,
            ]);
            return response(['id' => $db->id],200)->header('Content-Type', 'application/json');
        } catch (Exception $exception) {
            return response(['errors' => $exception],200)->header('Content-Type', 'application/json');
        }
    }


    public function Avito()
    {
        print(Vacancy::all()->count());
        print ('<br>');
        for ($j = 1; $j > 0; $j++) {
//            $url = 'https://hh.ru/search/vacancy?page=38';
            $url = "https://www.avito.ru/rossiya/vakansii?p={$j}";
            $ch = curl_init();
            $user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36';
            curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_REFERER, 'https://www.google.com/');
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);

//            print_r($response);

            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($response, 'HTML-ENTITIES', 'UTF-8'));
//        print_r(file_get_contents('https://www.google.com/'));
            $xpath = new DOMXPath($dom);
            $i = 1;
//        foreach ($xpath->query("//div[@class='iva-item-content-UnQQ4']") as $res){
            foreach ($xpath->query("//div[@data-marker='item']") as $res) {
                set_time_limit(0);
//                print ($i . "\n");
                $i++;
                $img = $xpath->query(".//img[@itemprop='image']", $res);
                $title = $xpath->query(".//div[@class='iva-item-titleStep-_CxvN']", $res);
                $price = $xpath->query(".//div[@class='iva-item-priceStep-QN8Kl']", $res);
                $geo = $xpath->query(".//div[@class='geo-root-H3eWU iva-item-geo-g3iIJ']", $res);
                $time = $xpath->query(".//div[@data-marker='item-date']", $res);
                $description = $xpath->query(".//meta[@itemprop='description']", $res);
                $img1 = null;
                $title1 = null;
                $price1 = null;
                $geo1 = null;
                $time1 = null;
                $description1 = null;
                if ($img->length > 0) {
//                    print ('<strong>Картинка: </strong>');
//                    print_r($img->item(0)->attributes->getNamedItem('src')->nodeValue . "\n");
                    $img1 = $img->item(0)->attributes->getNamedItem('src')->nodeValue;
                }
                if ($title->length > 0) {
//                    print ('<strong>Название: </strong>');
//                    print_r($title->item(0)->textContent . "\n");
                    $title1 = $title->item(0)->textContent;
                }
                if ($price->length > 0) {
//                    print ('<strong>Цена: </strong>');
//                    print_r($price->item(0)->textContent . "\n");
                    $price1 = $price->item(0)->textContent;
                }
                if ($geo->length > 0) {
//                    print ('<strong>Местоположение: </strong>');
//                    print_r($geo->item(0)->textContent . "\n");
                    $geo1 = $geo->item(0)->textContent;
                }
                if ($time->length > 0) {
//                    print ('<strong>Время: </strong>');
//                    print_r($time->item(0)->textContent . "\n");
                    $time1 = $time->item(0)->textContent;
                }
//              if($op->length>0)
                if ($description->length > 0) {
//                    print ('<strong>Описание: </strong>');
//                    print_r($description->item(0)->attributes->getNamedItem('content')->nodeValue . "\n");
                    $description1 = $description->item(0)->attributes->getNamedItem('content')->nodeValue;
                }
//                print ('<br>');
                if ($title->length > 0 && $description->length > 0 && $time->length > 0 && $geo->length > 0) {
//                    Vacancy::updateOrInsert([
//                        'title'=>$title1,
//                        'description'=>$description1,
//                        'geo'=>$geo1
//                    ],[
//                        'price'=>$price1,
//                        'img'=>$img1,
//                        'time'=>$time1,
//                    ]);
                    $res = $this->new((object)[
                        'name' => $title1,
                        'employer_name' => 'Avito',
                        'employer_logo_urls' => $img1,
                        'area' => $geo1,
                        'salary_to' => $price1,
                        'created_at' => $time1,
                        'snippet_responsibility' => $description1,
                        'snippet_requirement' => $description1,
                        'description' => $description1,
                        'experience' => 'Нет',
                        'employment' => 'Полный день',
                    ]);
                    print_r($res);
                }
            }
            if ($xpath->query("//span[@data-marker='pagination-button/next']")->length < 1) break;
        }
        print(Vacancy::all()->count());
        print ('<br>');
    }

    public function parsing()
    {
        Vacancy::truncate();
        $this->Avito();
        print(Vacancy::all()->count());
        print ('<br>');
        for ($j = 1; $j > 0; $j++) {
            $url = "https://hh.ru/search/vacancy?page={$j}";
//            $url = "https://hh.ru/search/vacancy";
            $ch = curl_init();
            $user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36';
            curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_REFERER, 'https://www.google.com/');
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);

//            print_r($response);

            $dom = new DOMDocument();
//            print_r(file_get_contents($url));
            @$dom->loadHTML(mb_convert_encoding($response, 'HTML-ENTITIES', 'UTF-8'));
//            @$dom->loadHTML(mb_convert_encoding(file_get_contents($url), 'HTML-ENTITIES', 'UTF-8'));
//        print_r(file_get_contents('https://www.google.com/'));
            $xpath = new DOMXPath($dom);
            $i = 1;
//        print_r($xpath->query("//div[@class='vacancy-serp']")->item(0)->childNodes);
//        foreach ($xpath->query("//div[@class='iva-item-content-UnQQ4']") as $res){
            foreach ($xpath->query("//div[@class='vacancy-serp']")->item(0)->childNodes as $res) {
                set_time_limit(0);
//                print ($i . "\n");
                $i++;
                $img = $xpath->query(".//img[@class='vacancy-serp-item-logo']", $res);
                $title = $xpath->query(".//a[@data-qa='vacancy-serp__vacancy-title']", $res);
                $price = $xpath->query(".//span[@data-qa='vacancy-serp__vacancy-compensation']", $res);
                $geo = $xpath->query(".//div[@data-qa='vacancy-serp__vacancy-address']", $res);
                $time = $xpath->query(".//span[@data-qa='vacancy-serp__vacancy-date']", $res);
                $description = $xpath->query(".//div[@class='g-user-content']", $res);
                $img1 = null;
                $title1 = null;
                $price1 = null;
                $geo1 = null;
                $time1 = null;
                $description1 = null;
                if ($img->length > 0) {
//                    print ('<strong>Картинка: </strong>');
//                    print_r($img->item(0)->attributes->getNamedItem('src')->nodeValue . "\n");
                    $img1 = $img->item(0)->attributes->getNamedItem('src')->nodeValue;
                }
                if ($title->length > 0) {
//                    print ('<strong>Название: </strong>');
                    $title1 = $title->item(0)->textContent;
                }
                if ($price->length > 0) {
//                    print ('<strong>Цена: </strong>');
//                    print_r($price->item(0)->textContent . "\n");
                    $price1 = $price->item(0)->textContent;
                }
                if ($geo->length > 0) {
//                    print ('<strong>Местоположение: </strong>');
//                    print_r($geo->item(0)->textContent . "\n");
                    $geo1 = $geo->item(0)->textContent;
                }
                if ($time->length > 0) {
//                    print ('<strong>Время: </strong>');
//                    print_r($time->item(0)->textContent . "\n");
                    $time1 = $time->item(0)->firstChild->textContent;
                }
                if ($description->length > 0) {
//                    print ('<strong>Описание: </strong>');
//                    print_r($description->item(0)->textContent . "\n");
                    $description1 = $description->item(0)->textContent;
                }
//                print ('<br>');
                if ($title->length > 0 && $description->length > 0 && $time->length > 0 && $geo->length > 0) {
//                    Vacancy::updateOrInsert([
//                        'title'=>$title1,
//                        'description'=>$description1,
//                        'geo'=>$geo1
//                    ],[
//                        'price'=>$price1,
//                        'img'=>$img1,
//                        'time'=>$time1,
//                    ]);
//
                    $res = $this->new((object)[
                        'name' => $title1,
                        'employer_name' => 'Avito',
                        'employer_logo_urls' => $img1,
                        'area' => $geo1,
                        'salary_to' => $price1,
                        'created_at' => $time1,
                        'snippet_responsibility' => $description1,
                        'snippet_requirement' => $description1,
                        'description' => $description1,
                        'experience' => 'Нет',
                        'employment' => 'Полный день',
                    ]);

                    print_r($res);
                    print ('<br>');
//                    dd(json_decode($res));
//                    print_r($res);
                }
            }
            if ($xpath->query("//a[@data-qa='pager-next']")->length < 1) break;
        }
        print(Vacancy::all()->count());
        print ('<br>');
    }
}
