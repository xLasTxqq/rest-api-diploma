<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FilterController extends Controller
{
    private function sendCurl($ch, $url)
    {

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);

        return json_decode(curl_exec($ch));
    }

    public function index()
    {
//        $start = microtime( true );

        $ch = curl_init();
        $dictionaries_request = $this->sendCurl($ch, "https://api.hh.ru/dictionaries");
        $areas_request = $this->sendCurl($ch, "https://api.hh.ru/areas");
        $specializations_request = $this->sendCurl($ch, "https://api.hh.ru/specializations");
        curl_close($ch);

        $filter['experience'] = $dictionaries_request->experience;
        $filter['schedule'] = $dictionaries_request->schedule;
        $filter['area'] = $areas_request;
        $filter['specializations'] = $specializations_request;
        $filter['date_from'] = [['id' => 1, 'name' => 'За всё время'],
            ['id' => 2, 'name' => 'За месяц'],
            ['id' => 3, 'name' => 'За неделю'],
            ['id' => 4, 'name' => 'За 3 дня'],
            ['id' => 5, 'name' => 'За сутки']];
        $filter['currency'] = $dictionaries_request->currency;

//        dd(microtime( true ) - $start);
        return response()->json($filter);
    }

    public function search(Request $request)
    {
//        $start = microtime( true );

        $ch = curl_init();
        $dictionaries_request = $this->sendCurl($ch, "https://api.hh.ru/dictionaries");
        $areas_request = $this->sendCurl($ch, "https://api.hh.ru/areas");
        $specializations_request = $this->sendCurl($ch, "https://api.hh.ru/specializations");
        curl_close($ch);

        $filter['experience'] = $dictionaries_request->experience;
        $filter['schedule'] = $dictionaries_request->schedule;
        $filter['area'] = $areas_request;
        $filter['specializations'] = $specializations_request;
        $filter['date_from'] = [['id' => 1, 'name' => 'За всё время'],
            ['id' => 2, 'name' => 'За месяц'],
            ['id' => 3, 'name' => 'За неделю'],
            ['id' => 4, 'name' => 'За 3 дня'],
            ['id' => 5, 'name' => 'За сутки']];
        $filter['currency'] = $dictionaries_request->currency;

        if ($request->search === "area") {
            $result = [];
            $key = 0;
            foreach ($filter[$request->search] as $value1) {
                if (!empty($value1->areas)) {
                    foreach ($value1->areas as $value2) {
                        if (!empty($value2->areas)) {
                            foreach ($value2->areas as $value3) {
                                if (stripos($value3->name, $request->text) !== false) {
                                    $result[$key]['name'] = $value3->name;
                                    $result[$key]['id'] = $value3->id;
                                    $key++;
                                }
                            }
                        }
                        if (stripos($value2->name, $request->text) !== false) {
                            $result[$key]['name'] = $value2->name;
                            $result[$key]['id'] = $value2->id;
                            $key++;
                        }
                    }
                }
                if (stripos($value1->name, $request->text) !== false) {
                    $result[$key]['name'] = $value1->name;
                    $result[$key]['id'] = $value1->id;
                    $key++;
                }
            }
        }
        if ($request->search === "specializations") {
            $result = [];
            $key = 0;
            foreach ($filter[$request->search] as $value1) {
                if (!empty($value1->specializations)) {
                    foreach ($value1->specializations as $value2) {
                        if (stripos($value2->name, $request->text) !== false) {
                            $result[$key]['name'] = $value2->name;
                            $result[$key]['id'] = $value2->id;
                            $key++;
                        }
                    }
                }
                if (stripos($value1->name, $request->text) !== false) {
                    $result[$key]['name'] = $value1->name;
                    $result[$key]['id'] = $value1->id;
                    $key++;
                }
            }
        }
        $result2[$request->search] = $result;
//        dd(microtime( true ) - $start);
        return response()->json(empty($request->text) ? $filter : $result2);
    }

}
