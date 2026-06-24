<?php

namespace App\Service\Gx;

class KirimService implements GxInterface
{

    public function getKirimList()
    {
         // get list subscribers form Kirim
         $time = time();
         $generated_token = hash_hmac("sha256","globalexpansion_en"."::"."lysW2r6P8Agq59dkaQhFSVfbEw3LcDxtglobalexpansion_en"."::".$time,"lysW2r6P8Agq59dkaQhFSVfbEw3LcDxtglobalexpansion_en");

         try {
         $client = new \GuzzleHttp\Client();
                     $request = $client->get('https://api.kirim.email/v3/list', [
                         'headers' => [
                             'Accept' => 'application/json',
                             'Content-Type'=> 'application/json',
                             'Auth-Id'=> 'globalexpansion_en',
                             'Auth-Token'=> $generated_token,
                             'Timestamp'=> $time,
                             'Offset'=>0
                         ]
                     ]);
             $response = $request->getBody()->getContents();
             $json = json_decode($response, true);
             } catch (\Exception $e)
             {
             $response = ['status' => false, 'message' => $e->getMessage()];
             }
             return $json['data'];
    }

    public function getKirimListCount()
    {
        $time = time();
        $generated_token = hash_hmac("sha256","globalexpansion_en"."::"."lysW2r6P8Agq59dkaQhFSVfbEw3LcDxtglobalexpansion_en"."::".$time,"lysW2r6P8Agq59dkaQhFSVfbEw3LcDxtglobalexpansion_en");

            $count = 0;
             foreach($this->getKirimList() as $list)
             {


                try {
                    $client = new \GuzzleHttp\Client();
                                $request = $client->get('https://api.kirim.email/v3/subscriber', [
                                    'headers' => [
                                        'Accept' => 'application/json',
                                        'Content-Type'=> 'application/json',
                                        'Auth-Id'=> 'globalexpansion_en',
                                        'Auth-Token'=> $generated_token,
                                        'Timestamp'=> $time,
                                        'Offset'=>0,
                                        'List-Id'=>$list['id']
                                    ]
                                ]);
                        $response = $request->getBody()->getContents();
                        $json = json_decode($response, true);
                        } catch (\Exception $e)
                        {
                        $response = ['status' => false, 'message' => $e->getMessage()];
                        }


                        $count = $count + $json['total'];




             }
             return $count;


    }

    public function deleteEmail($email)
    {

        // get list subscribers form Kirim
        $time = time();
        $generated_token = hash_hmac("sha256","globalexpansion_en"."::"."lysW2r6P8Agq59dkaQhFSVfbEw3LcDxtglobalexpansion_en"."::".$time,"lysW2r6P8Agq59dkaQhFSVfbEw3LcDxtglobalexpansion_en");
        foreach($this->getKirimList() as $list)
        {
        try {
        $client = new \GuzzleHttp\Client();
                    $request = $client->delete('https://api.kirim.email/v3/subscriber/email/'.$email.'',
                    [
                        'headers' => [
                            'Accept' => 'application/json',
                            'Content-Type'=> 'application/json',
                            'Auth-Id'=> 'globalexpansion_en',
                            'Auth-Token'=> $generated_token,
                            'Timestamp'=> $time,
                            'Offset'=>0,
                            'List-id'=>$list['id']
                        ]
                    ]);
            $response = $request->getBody()->getContents();
            $json = json_decode($response, true);
            } catch (\Exception $e)
            {
            $response = ['status' => false, 'message' => $e->getMessage()];
            }
        }
            return $response;
    }



}
