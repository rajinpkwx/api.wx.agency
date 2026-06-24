<?php

namespace App\Console\Commands\Gx;

use Illuminate\Console\Command;
use App\Service\Gx\KirimService;
use Session;


class KirimHubspotCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kirim:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(KirimService $kirim)
    {

        $bar = $this->output->createProgressBar($kirim->getKirimListCount());
        $bar->start();
        $lists = $kirim->getKirimList();
        foreach($kirim->getKirimList() as $list)
        {


                Session::put('offset',0);
                while(Session::get('offset')!=-1)
                {
                $time = time();
                $generated_token = hash_hmac("sha256","globalexpansion_en"."::"."lysW2r6P8Agq59dkaQhFSVfbEw3LcDxtglobalexpansion_en"."::".$time,"lysW2r6P8Agq59dkaQhFSVfbEw3LcDxtglobalexpansion_en");
                try {
                $client = new \GuzzleHttp\Client();
                            $request = $client->get('https://api.kirim.email/v3/subscriber', [
                                'headers' => [
                                    'Accept' => 'application/json',
                                    'Content-Type'=> 'application/json',
                                    'Auth-Id'=> 'globalexpansion_en',
                                    'Auth-Token'=> $generated_token,
                                    'Timestamp'=> $time,
                                    'Offset'=>(int)Session::get('offset'),
                                    'List-Id'=>$list['id']
                                ]
                            ]);
                    $response = $request->getBody()->getContents();
                    $json = json_decode($response, true);
                    } catch (\Exception $e)
                    {
                    $response = ['status' => false, 'message' => $e->getMessage()];
                    }

                    if ($json['offset']<$json['total'])
                    {
                        $offset = $json['offset'] + 100;
                        Session::put('offset',$offset);
                    }
                    else
                    {
                        Session::put('offset',-1);
                    }


                    foreach($json['data'] as $jsons)
                    {
                        $bar->advance();

                        try
                        {
                        $query['hapikey'] = '133c6406-8fea-4358-a36a-e78a1555336c';
                        $client = new \GuzzleHttp\Client();
                        $request = $client->get('https://api.hubapi.com/contacts/v1/contact/email/'.$jsons['email'].'/profile', [
                            'headers' => [
                                'Accept' => 'application/json',
                                'Content-type' => 'application/x-www-form-urlencoded'
                                ],
                            'query' => $query,

                        ]);


                        $response = $request->getBody()->getContents();
                        $json = json_decode($response, true);

                        } catch (\Exception $e) {

                        $response = ['status' => false, 'message' => $e->getMessage()];

                        }
                            if(!isset($response['status']))
                            {
                                if (array_key_exists('unsubscribe_from_kirim', $json['properties']))
                                {

                                    if($json['properties']['unsubscribe_from_kirim']['value']=='true')
                                    {

                                        //echo $jsons['email']; echo PHP_EOL;
                                        $kirim->deleteEmail($jsons['email']);

                                    }
                                }
                            }

                    }

                }


            }

            $bar->finish();
            $this->info('All sorted contacs are synced');

        }
}
